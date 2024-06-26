<?php
/*=========================================================
// File:        funct_etab.php
// Description: user functions of EvalSMSI
// Created:     2009-01-01
// Licence:     GPL-3.0-or-later
// Copyright 2009-2019 Michel Dubois

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.
=========================================================*/




function isThereObjectives() {
	global $cheminDATA;
	$id_etab = $_SESSION['id_etab'];
	$id_quiz = $_SESSION['quiz'];
	$base = dbConnect();
	$request = sprintf("SELECT objectifs FROM etablissement WHERE id='%d' LIMIT 1", $id_etab);
	$result = mysqli_query($base, $request);
	$row = mysqli_fetch_object($result);
	$objectives = json_decode($row->objectifs, true);
	if (!array_key_exists($id_quiz, $objectives)) {
		$notes = array();
		$request = sprintf("SELECT filename FROM quiz WHERE id='%d' LIMIT 1", $id_quiz);
		$result = mysqli_query($base, $request);
		$row = mysqli_fetch_object($result);
		$jsonFile = sprintf("%s%s", $cheminDATA, $row->filename);
		$jsonSource = file_get_contents($jsonFile);
		$jsonQuiz = json_decode($jsonSource, true);
		for ($i=0; $i<count($jsonQuiz); $i++) {
			$objCurr = sprintf("obj_%d", $jsonQuiz[$i]['numero']);
			$notes[$objCurr] = 4;
		}
	}
	$objectives[$id_quiz] = $notes;
	$output = json_encode($objectives);
	$request = sprintf("UPDATE etablissement SET objectifs='%s' WHERE id='%d'", $output, $id_etab);
	mysqli_query($base, $request);
	dbDisconnect($base);
}


function createAssessment() {
	genSyslog(__FUNCTION__);
	$json = array('previous'=>false, 'successful'=>false);
	isThereObjectives();
	$base = dbConnect();
	$request = sprintf("SELECT * FROM assess WHERE etablissement='%d' AND annee='%d' AND quiz='%d' LIMIT 1", $_SESSION['id_etab'], $_SESSION['annee']-1, $_SESSION['quiz']);
	$result = mysqli_query($base, $request);
	if ($result->num_rows) {
		$row = mysqli_fetch_object($result);
		$request = sprintf("INSERT INTO assess (etablissement, annee, quiz, reponses, comments) VALUES ('%d', '%d', '%d', '%s', '%s')", $_SESSION['id_etab'], $_SESSION['annee'], $_SESSION['quiz'], $row->reponses, $row->comments);
		$json['previous'] = true;
	} else {
		$request = sprintf("INSERT INTO assess (etablissement, annee, quiz) VALUES ('%d', '%d', '%d')", $_SESSION['id_etab'], $_SESSION['annee'], $_SESSION['quiz']);
		$json['previous'] = false;
	}
	if (mysqli_query($base, $request)) {
		dbDisconnect($base);
		$json['successful'] = true;
	} else {
		dbDisconnect($base);
		$json['successful'] = false;
	}
	return(json_encode($json));
}


function displayAssessment() {
	genSyslog(__FUNCTION__);
	$numQuestion = questionsCount();
	$nonce = $_SESSION['nonce'];
	$annee = $_SESSION['annee'];
	$id_quiz = $_SESSION['quiz'];
	$quiz = getJsonFile();
	$reponses = getAnswers();
	$base = dbConnect();
	$request = sprintf("SELECT * FROM assess WHERE etablissement='%d' AND annee='%d' AND quiz='%d' LIMIT 1", $_SESSION['id_etab'], $annee, $id_quiz);
	$result = mysqli_query($base, $request);
	dbDisconnect($base);
	printf("<h1>Evaluation pour l'année %s</h1>", $annee);
	// un enregistrement a déjà été fait
	if ($result->num_rows) {
		$row = mysqli_fetch_object($result);
		$assessment = unserialize($row->reponses);
		$final_c = $row->comments;
	}
		if($row->valide) {
			linkMsg("etab.php", "L'évaluation pour ".$annee." est complète et validée par les évaluateurs. Vous ne pouvez plus la modifier.", "alert.png");
			footPage();
	} else {
		# affichage de la barre de progression
		printf("<div id='a'><div id='b'><div id='c'></div></div></div>");
		# affichage du formulaire
		printf("<div class='row'>");
		printf("<div class='column largeleft'>");
		printf("<h3>Cette évaluation comprend %s questions</h3>", $numQuestion);
		printf("<div class='assess'>");
		printf("<form method='post' id='make_assess' action='etab.php?action=make_assess' novalidate>");
		printf("<p><input type='hidden' id='nbr_questions' value='%s'></p>", $numQuestion);
		if (!empty($reponses[$annee])) {
			$notesDom = array_values(calculNotes($reponses[$annee]));
		}
		$dom_complete = domainComplete($assessment);
		for ($d=0; $d<count($quiz); $d++) {
			$num_dom = $quiz[$d]['numero'];
			$subDom = $quiz[$d]['subdomains'];
			$fond = getColorButton($dom_complete, $num_dom);
			if (!empty($reponses[$annee])) {
				$table = extractSubDomRep($num_dom, $reponses[$annee]);
				$notesSubDom = array_values(calculNotesDetail($table, $num_dom.'1'));
				$note = round($notesDom[$num_dom-1] * 20 / 7, 1);
				printf("<p>%s<b>%s</b>&nbsp;%s&nbsp;-&nbsp;<b>%s/20</b>&nbsp;<input type='button' value='+' id='ti%s'></p>", $fond, $num_dom, $quiz[$d]['libelle'], $note, $num_dom);
			} else {
				printf("<p>%s<b>%s</b>&nbsp;%s&nbsp;&nbsp;<input type='button' value='+' id='ti%s'></p>", $fond, $num_dom, $quiz[$d]['libelle'], $num_dom);
			}
			printf("<script nonce='%s'>document.getElementById('ti%s').addEventListener('click', function(){display('ti%s');});</script>", $nonce, $num_dom, $num_dom);
			printf("<dl class='none' id='dl%s'>", $num_dom);
			for ($sd=0; $sd<count($subDom); $sd++) {
				$num_sub_dom = $subDom[$sd]['numero'];
				$questions = $subDom[$sd]['questions'];
				$id = $num_dom.'-'.$num_sub_dom;
				$subdom_complete = subDomainComplete($assessment, $num_dom, $num_sub_dom);
				$fond = getColorButton($subdom_complete, $num_sub_dom);
				if (!empty($reponses[$annee])) {
					$note = round($notesSubDom[$num_sub_dom-1] * 20 / 7, 1);
					printf("<dt>%s<b>%s.%s</b>&nbsp;&nbsp;%s&nbsp;-&nbsp;<b>%s/20</b>&nbsp;<input type='button' value='+' id='dt%s'></dt>", $fond, $num_dom, $num_sub_dom, $subDom[$sd]['libelle'], $note, $id);
				} else {
					printf("<dt>%s<b>%s.%s</b>&nbsp;&nbsp;%s&nbsp;&nbsp;<input type='button' value='+' id='dt%s'></dt>", $fond, $num_dom, $num_sub_dom, $subDom[$sd]['libelle'], $id);
				}
				printf("<script nonce='%s'>document.getElementById('dt%s').addEventListener('click', function(){display('dt%s');});</script>", $nonce, $id, $id);
				if ($subDom[$sd]['comment'] != '') {
					printf("<dd class='comment'>%s</dd>", $subDom[$sd]['comment']);
				}
				printf("<dd class='none' id='dd%s'>", $id);
				for ($q=0; $q<count($questions); $q++) {
					$num_question = $questions[$q]['numero'];
					printf("<p><b>%s.%s.%s</b> %s</p>", $num_dom, $num_sub_dom, $num_question, $questions[$q]['libelle']);
					$mesure = $questions[$q]['mesure'];
					printf("<div class='reco_parent'>");
					printf("<div class='reco_child'>");
					if (isset($assessment)) {
						printSelect($num_dom, $num_sub_dom, $num_question, $assessment);
					} else {
						printSelect($num_dom, $num_sub_dom, $num_question);
					}
					printf("</div><div class='reco_child'>");
					if ($mesure !== 'Néant') {
						printf("<span class='reco'>%s</span>", $mesure);
					} else {
						printf("<span class='reco'>Pas de recommandation spécifique</span>");
					}
					printf("</div></div>");
					$commentID = 'comment'.$num_dom.'_'.$num_sub_dom.'_'.$num_question;
					$errorID = 'error'.$num_dom.'_'.$num_sub_dom.'_'.$num_question;
					if (isset($assessment)) {
						printf("<textarea placeholder='Commentaire' name='%s' id='%s' cols='80' rows='4'>%s</textarea>", $commentID, $commentID, traiteStringFromBDD($assessment[$commentID]));
					} else {
						printf("<textarea placeholder='Commentaire' name='%s' id='%s' cols='80' rows='4'></textarea>", $commentID, $commentID);
					}
					printf("<span class='error' id='%s'></span>", $errorID);
					printf("<script nonce='%s'>document.getElementById('%s').addEventListener('keyup', function(){progresse();});</script>", $nonce, $commentID);
					printf("<p class='separation'>&nbsp;</p>");
				}
				printf("</dd>");
			}
			printf("</dl>");
		}
		printf("<textarea placeholder='Commentaire final' name='final_comment' id='final_comment' cols='68' rows='5' class='none'>%s</textarea>", traiteStringFromBDD($final_c));
		printf("<span class='error' id='final_comment_error'></span>");
		printf("<script nonce='%s'>document.getElementById('final_comment').addEventListener('keyup', function(){progresse();});</script>", $nonce);
		validForms('Enregistrer', 'etab.php', $back=False);
		printf("</form>");
		printf("</div>");
		printf("</div>");
		afficheNotesExplanation();
		printf("</div>");
		printf("<script nonce='%s'>document.body.addEventListener('load', progresse());</script>", $nonce);
		printf("<script nonce='%s'>document.getElementById('make_assess').addEventListener('submit', function(){assessFormValidity(event);});</script>", $nonce);
	}
}


function writeAssessment() {
	genSyslog(__FUNCTION__);
	recordLog();
	$comment = isset($_POST['final_comment']) ? traiteStringToBDD($_POST['final_comment']) : NULL;
	$record = controlAssessment($_POST);
	$request = sprintf("UPDATE assess SET reponses='%s', comments='%s' WHERE etablissement='%d' AND annee='%d' AND quiz='%d' ", $record, $comment, $_SESSION['id_etab'], $_SESSION['annee'], $_SESSION['quiz']);
	$base = dbConnect();
	if (isset($_SESSION['token'])) {
		unset($_SESSION['token']);
		if (mysqli_query($base, $request)){
			dbDisconnect($base);
			return true;
		} else {
			dbDisconnect($base);
			return false;
		}
	} else {
		dbDisconnect($base);
		return false;
	}
}


function exportRapport($annee) {
	genSyslog(__FUNCTION__);
	if (isset($_SESSION['token'])) {
		unset($_SESSION['token']);
	}
	$xlsFile = generateExcellRapport($annee);
	$msg = sprintf("Télécharger le plan d'actions %s (Excel)", $annee);
	printf("<div class='row'>");
	printf("<div class='column left'>");
	generateRapport($annee);
	printf("</div>");
	printf("<div class='column right'>");
	linkMsg($xlsFile, $msg, "xlsx.png", 'menu');
	printf("</div></div>");
}


function selectYearRapport() {
	genSyslog(__FUNCTION__);
	$base = dbConnect();
	$request = sprintf("SELECT * FROM assess WHERE etablissement='%d' AND quiz='%d' ORDER BY annee DESC", $_SESSION['id_etab'], $_SESSION['quiz']);
	$result = mysqli_query($base, $request);
	dbDisconnect($base);
	$list = array();
	while($row=mysqli_fetch_object($result)) {
		if ($row->valide) {
				$list[] = $row->annee;
		}
	}
	if (count($list)) {
		printf("<form method='post' id='select_print' action='etab.php?action=do_print'>");
		printf("<fieldset><legend>Choix d'une année</legend>");
		printf("<table><tr><td>");
		printf("Année:&nbsp;<select name='year' id='year' required>");
		printf("<option selected='selected' value=''>&nbsp;</option>");
		foreach($list as $annee) {
			printf("<option value='%d'>%d</option>", $annee, $annee);
		}
		printf("</select>");
		printf("</td></tr></table>");
		printf("</fieldset>");
		validForms('Afficher le rapport', 'etab.php');
		printf("</form>");
	} else {
		linkMsg("etab.php", "Il n'y a pas d'évaluation validée pour cet établissement.", "alert.png");
	}
}


?>
