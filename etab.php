<?php
/*=========================================================
// File:        etab.php
// Description: user page of EvalSMSI
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

ob_start(); // Démarre la mise en tampon de sortie

include("functions.php");
include("funct_etab.php");
startSession();
$authorizedRole = array('3', '4', '5');
isSessionValid($authorizedRole);
headPage($appli_titre);
purgeRapportsFiles();

if (isset($_GET['action'])) {
	switch ($_GET['action']) {

	case 'continue_assess':
		isAuthorized(array('4', '5'));
		if (isThereAssessForEtab()) {
			displayAssessment();
		} else {
			$result = json_decode(createAssessment(), true);
			if ($result['successful']) {
				if ($result['previous']) {
					$msg = sprintf("Une évaluation a été initialisée pour %s à partir des données d'évaluation de %s. Cliquer pour continuer...", $_SESSION['annee'], $_SESSION['annee']-1);
				} else {
					$msg = sprintf("Une évaluation vierge a été initialisée pour %s. Cliquer pour continuer...", $_SESSION['annee']);
				}
				linkMsg("etab.php?action=continue_assess", $msg, "ok.png");
			} else {
				linkMsg($_SESSION['curr_script'], "Aucune évaluation disponible.", "alert.png");
			}
		}
		footPage();
		break;

	case 'make_assess':
		isAuthorized(array('4', '5'));
		if (writeAssessment()) {
			linkMsg($_SESSION['curr_script'], "Evaluation mise à jour.", "ok.png");
		} else {
			linkMsg($_SESSION['curr_script'], "Erreur de mise à jour.", "alert.png");
		}
		footPage();
		break;

	case 'graph':
		isAuthorized(array('3', '4'));
		if (isThereAssessForEtab()) {
			displayEtablissmentGraphs();
			footPage($_SESSION['curr_script'], "Accueil");
		} else {
			$msg = sprintf("L'évaluation pour %d n'a pas été créée.", $_SESSION['annee']);
			linkMsg($_SESSION['curr_script'], $msg, "alert.png");
			footPage();
		}
		break;

	case 'print':
		isAuthorized(array('3', '4'));
		selectYearRapport();
		footPage();
		break;

	case 'do_print':
		isAuthorized(array('3', '4'));
		exportRapport(intval($_POST['year']));
		footPage($_SESSION['curr_script'], "Accueil");
		break;

	case 'office':
		isAuthorized(array('3', '4', '5'));
		exportEval();
		footPage($_SESSION['curr_script'], "Accueil");
		break;

	case 'rules':
		isAuthorized(array('3', '4', '5'));
		exportRules();
		footPage($_SESSION['curr_script'], "Accueil");
		break;

	case 'password':
		changePassword();
		footPage();
		break;

	case 'chg_password':
		if (recordNewPassword($_POST['new1'])) {
			linkMsg($_SESSION['curr_script'], "Mot de passe changé avec succès", "ok.png");
		} else {
			linkMsg($_SESSION['curr_script'], "Erreur de changement de mot de passe", "alert.png");
		}
		footPage();
		break;

	case 'choose_quiz':
		chooseQuiz();
		footPage();
		break;

	case 'set_quiz':
		if (setRightQuiz($_POST['id_quiz'])) {
			header("Location: ".$_SESSION['curr_script']);
		} else {
			linkMsg($_SESSION['curr_script'], "Erreur de référentiel", "alert.png");
			footPage();
		}
		break;

	case 'regwebauthn':
		registerWebauthnCred();
		footPage();
		break;

	case 'authentication':
		menuAuthentication();
		footPage($_SESSION['curr_script'], "Accueil");
		break;

	case 'rm_token':
		if (isset($_SESSION['token'])) {
			unset($_SESSION['token']);
		}
		if (isset($_SESSION['quiz'])) {
			menuEtab();
			footPage();
		} else {
			destroySession();
			header('Location: evalsmsi.php');
		}
		break;

	default:
		if (isset($_SESSION['token'])) {
			unset($_SESSION['token']);
		}
		menuEtab();
		footPage();
	}
} else {
	menuEtab();
	footPage();
}


ob_end_flush(); // Envoie le tampon de sortie et désactive la mise en tampon



?>
