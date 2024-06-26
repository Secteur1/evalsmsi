<?php
/*=========================================================
// File:        functions.php
// Description: global functions of EvalSMSI
// Created:     2009-01-01
// Licence:     GPL-3.0-or-later
// Copyright 2009-2020 Michel Dubois

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




// --------------------
// Définition des variables internes à l'application
// Ne pas modifier ces variables !
date_default_timezone_set('Europe/Paris');
setlocale(LC_ALL, 'fr_FR.utf8');
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('xdebug.var_display_max_depth', 8);
ini_set('xdebug.var_display_max_children ', 3);
ini_set('session.gc_maxlifetime', 86400); // 24h00
ini_set('session.gc_probability', 1);
ini_set('session.name', '__SECURE-PHPSESSID');
ini_set('session.use_trans_sid', 0);
ini_set('session.use_strict_mode', 1);
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cache_limiter', 'nocache');
ini_set('session.sid_length', 48);
ini_set('session.sid_bits_per_character', 6);
ini_set('session.entropy_length', 32);
ini_set('session.entropy_file', '/dev/urandom');
ini_set('session.hash_function', 'sha256');
ini_set('filter.default', 'full_special_chars');
ini_set('filter.default_flags', 0);

$configs = include('config.php');

$servername = $configs['servername'];
$dbname = $configs['dbname'];
$login = $configs['login'];
$passwd = $configs['passwd'];
$appli_titre = $configs['appli_titre'];
$appli_titre_short = $configs['appli_titre_short'];
$cssTheme = $configs['cssTheme'];
$auhtPict = $configs['auhtPict'];
$rapportPicts = $configs['rapportPicts'];
$captchaMode = $configs['captchaMode'];
$attestationMode = $configs['attestationMode'];
$sessionDuration = $configs['sessionDuration'];

$noteMax = 7;
$progVersion = '5.2.0';
$progDate = '02 janvier 2021';
$cspReport = "csp_parser.php";
$server_path = dirname($_SERVER['SCRIPT_FILENAME']);
$cheminRAP = sprintf("%s/rapports/", $server_path);
$cheminDATA = sprintf("%s/data/", $server_path);

require_once ('phpoffice/bootstrap.php');

use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\SimpleType\DocProtect;
use PhpOffice\PhpWord\Style\Language;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

Settings::loadConfig();

$largeur=798; // Largeur graphe
$hauteur=532; // Hauteur graphe

$colors = array('darkslateblue', 'darkorange', 'darkorchid', 'bisque4', 'aquamarine4', 'azure4', 'brown', 'cadetblue', 'chartreuse', 'chocolate', 'coral', 'cornflowerblue', 'darkgoldenrod', 'darkmagenta', 'darkolivegreen4', 'darksalmon', 'darkseagreen', 'darkslateblue', 'darkturquoise', 'deeppink', 'deepskyblue', 'goldenrod', 'indianred');
// --------------------


function menuAdmin() {
	genSyslog(__FUNCTION__);
	$_SESSION['curr_script'] = 'admin.php';
	printf("<div class='row'>");
	printf("<div class='column left'>");
	linkMsg("admin.php?action=new_user", "Ajouter un utilisateur", "add_user.png", 'menu');
	linkMsg("admin.php?action=select_user", "Modifier un utilisateur", "modif_user.png", 'menu');
	linkMsg("admin.php?action=delete_user", "Supprimer un utilisateur", "modif_user.png", 'menu');
	linkMsg("admin.php?action=authentication", "Gestion de l'authentification", "fingerprint.png", 'menu');
	printf("<br>");	
	linkMsg("admin.php?action=select_quiz", "Visualiser un questionnaire", "eval_continue.png", 'menu');
	linkMsg("admin.php?action=create_quiz", "Créer un questionnaire", "eval_continue.png", 'menu');
	linkMsg("admin.php?action=edit_quiz", "Éditer un questionnaire", "eval_continue.png", 'menu');
	linkMsg("admin.php?action=delete_quiz", "Supprimer un questionnaire", "eval_continue.png", 'menu');
	printf("</div><div class='column right'>");
	linkMsg("admin.php?action=new_etab", "Créer un établissement", "add_etab.png", 'menu');
	linkMsg("admin.php?action=delete_etab", "Supprimer un établissement", "add_etab.png", 'menu');
	linkMsg("admin.php?action=select_etab", "Modifier un établissement", "modif_etab.png", 'menu');
	linkMsg("admin.php?action=new_regroup", "Créer un établissement de regroupement", "add_regroup.png", 'menu');
	linkMsg("admin.php?action=bilan_etab", "Bilan global des établissements", "bilan.png", 'menu');
	printf("<br>");	
	linkMsg("admin.php?action=maintenance", "Maintenance de la Base de Données", "bdd.png", 'menu');
	printf("</div></div>");
}


function menuEtab() {
	genSyslog(__FUNCTION__);
	$_SESSION['curr_script'] = 'etab.php';
	printf("<div class='row'>");
	printf("<div class='column left'>");
	if (in_array($_SESSION['role'], array('4', '5'))) {
		linkMsg("etab.php?action=continue_assess", "Compléter l'évaluation", "eval_continue.png", 'menu');
	}
	if (in_array($_SESSION['role'], array('3', '4'))) {
		linkMsg("etab.php?action=print", "Imprimer les rapports et plans d'actions", "print.png", 'menu');
	}
	linkMsg("etab.php?action=authentication", "Gestion de l'authentification", "fingerprint.png", 'menu');
	linkMsg("aide.php", "Aide et documentation", "help.png", 'menu');
	printf("</div><div class='column right'>");
	linkMsg("etab.php?action=choose_quiz", "Choisir un référentiel", "quiz.png", 'menu');
	if (isset($_SESSION['quiz'])) {
		if (in_array($_SESSION['role'], array('3', '4'))) {
			linkMsg("etab.php?action=graph", "Graphes établissement", "piechart.png", 'menu');
		}
		if (in_array($_SESSION['role'], array('3', '4', '5'))) {
			linkMsg("etab.php?action=office", "Exporter l'évaluation", "docx.png", 'menu');
			linkMsg("etab.php?action=rules", "Exporter le référentiel", "pdf.png", 'menu');
		}
	}
	printf("</div></div>");
}


function menuAudit() {
	genSyslog(__FUNCTION__);
	$_SESSION['curr_script'] = 'audit.php';
	printf("<div class='row'>");
	printf("<div class='column left'>");
	linkMsg("audit.php?action=office", "Exporter une évaluation", "docx.png", 'menu');
	linkMsg("audit.php?action=graph", "Graphes par établissement", "piechart.png", 'menu');
	linkMsg("audit.php?action=objectif", "Gestion des objectifs", "objectifs.png", 'menu');
	linkMsg("audit.php?action=review", "Bilan des évaluations", "review.png", 'menu');
	linkMsg("audit.php?action=authentication", "Gestion de l'authentification", "fingerprint.png", 'menu');
	printf("</div><div class='column right'>");
	linkMsg("audit.php?action=audit", "Evaluation auditeur", "audit.png", 'menu');
	linkMsg("audit.php?action=rap_etab", "Rapport par établissement", "rapport.png", 'menu');
	linkMsg("audit.php?action=delete", "Supprimer une évaluation", "remove.png", 'menu');
	linkMsg("audit.php?action=journal", "Journalisation", "journal.png", 'menu');
	linkMsg("aide.php", "Aide et documentation", "help.png", 'menu');
	printf("</div></div>");
}


function menuAuthentication() {
	printf("<div class='row'>");
	printf("<div class='column left'>");
	linkMsg($_SESSION['curr_script']."?action=password", "Changer de mot de passe", "cadenas.png", 'menu');
	printf("</div><div class='column right'>");
	linkMsg($_SESSION['curr_script']."?action=regwebauthn", "Enregistrer une clef d'authentification", "fido2key.png", 'menu');
	printf("</div></div>");
}


function dbConnect() {
	global $servername, $dbname, $login, $passwd;
	$link = mysqli_connect($servername, $login, $passwd, $dbname);
	if (!$link) {
		$msg = sprintf("Erreur de connexion: %d (%s)", mysqli_connect_errno(),  mysqli_connect_error());
		linkMsg("evalsmsi.php", $msg, "alert.png");
		footPage();
	} else {
		mysqli_set_charset($link , 'utf8');
		return $link;
	}
}


function dbDisconnect($dbh) {
	mysqli_close($dbh);
	$dbh=0;
}


function base64UrlEncode($data) {
	$b64 = base64_encode($data);
	$url = strtr($b64, '+/', '-_');
	return rtrim($url, '=');
}


function base64UrlDecode($data) {
	$b64 = strtr($data, '-_', '+/');
	$end = str_repeat('=', 3 - (3 + strlen($data)) % 4);
	return base64_decode($b64.$end);
}


function getJsonFile() {
	global $cheminDATA;
	$base = dbConnect();
	$request = sprintf("SELECT filename FROM quiz WHERE id='%d' LIMIT 1", $_SESSION['quiz']);
	$result = mysqli_query($base, $request);
	$row = mysqli_fetch_object($result);
	dbDisconnect($base);
	$jsonFile = sprintf("%s%s", $cheminDATA, $row->filename);
	$jsonSource = file_get_contents($jsonFile);
	return json_decode($jsonSource, true);
}


function getQuizNameById($id_quiz) {
	$base = dbConnect();
	$request = sprintf("SELECT nom FROM quiz WHERE id='%d' LIMIT 1", $id_quiz);
	$result = mysqli_query($base, $request);
	$row = mysqli_fetch_object($result);
	dbDisconnect($base);
	return $row->nom;
}


function getQuizName() {
	$base = dbConnect();
	$request = sprintf("SELECT nom FROM quiz WHERE id='%d' LIMIT 1", $_SESSION['quiz']);
	$result = mysqli_query($base, $request);
	$row = mysqli_fetch_object($result);
	dbDisconnect($base);
	return $row->nom;
}


function startSession() {
	session_set_cookie_params([
		'lifetime' => 0,
		'path' => '/',
		'domain' => "",
		'secure' => true,
		'httponly' => true,
		'samesite' => "Strict"
	]);
	session_start();
}


function destroySession() {
	genSyslog(__FUNCTION__);
	session_unset();
	session_destroy();
	session_write_close();
	setcookie(session_name(),'',0,'/');
	header('Location: evalsmsi.php');
}


function isSessionValid($role) {
	genSyslog(__FUNCTION__);
	$now = time();
	if ($now > $_SESSION['expire']) {
		destroySession();
		exit();
	} else {
		if ($_SESSION['expire'] - $now <= 1800) {
			// Il reste moins d'une demi-heure
			$_SESSION['expire'] += 1800;
		}
	}
	if (!isset($_SESSION['uid']) OR (!in_array($_SESSION['role'], $role))) {
		destroySession();
		exit();
	}
}


function isAuthorized($roles) {
	genSyslog(__FUNCTION__);
	if (!in_array($_SESSION['role'], $roles)) {
		header("Location: ".$_SESSION['curr_script']);
	}
}


function infoSession() {
	$timeLeft = intdiv($_SESSION['expire'] - time(), 60);
	$_SESSION['rand'] = genNonce(16);
	$infoDay = sprintf("%s - %s", $_SESSION['day'], $_SESSION['hour']);
	$infoNav = sprintf("%s - %s - %s", $_SESSION['os'], $_SESSION['browser'], $_SESSION['ipaddr']);
	$infoUser = sprintf("Connecté en tant que <b>%s %s</b> (%s)", $_SESSION['prenom'], $_SESSION['nom'], getRole($_SESSION['role']));
	$infoSession = sprintf("%s minutes restantes", $timeLeft);
	$logoff = sprintf("<a href='evalsmsi.php?rand=%s&action=disconnect'>Déconnexion&nbsp;<img alt='logoff' src='pict/turnoff.png' width='10'></a>", $_SESSION['rand']);
	return sprintf("Powered by EvalSMSI - %s - %s - %s - %s - %s", $infoDay, $infoNav, $infoUser, $infoSession, $logoff);
}


function detectIP() {
	if (isset($_SERVER)) {
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$usedIP = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
			$usedIP = $_SERVER['HTTP_CLIENT_IP'];
		} else {
			$usedIP = $_SERVER['REMOTE_ADDR'];
		}
	} else {
		if (getenv('HTTP_X_FORWARDED_FOR'))
			$usedIP = getenv('HTTP_X_FORWARDED_FOR');
		elseif (getenv('HTTP_CLIENT_IP'))
			$usedIP = getenv('HTTP_CLIENT_IP');
		else
			$usedIP = getenv('REMOTE_ADDR');
	}
	return $usedIP;
}


function detectBrowser() {
	$BrowserList = array (
		'Firefox' => '/Firefox/',
		'Chrome' => '/Chrome/',
		'Opera' => '/Opera/',
		'Safari' => '/Safari/',
		'Internet Explorer V6' => '/MSIE 6/',
		'Internet Explorer V7' => '/MSIE 7/',
		'Internet Explorer V8' => '/MSIE 8/',
		'Internet Explorer' => '/MSIE/'
	);
	foreach($BrowserList as $CurrBrowser=>$Match) {
		if (preg_match($Match, $_SERVER['HTTP_USER_AGENT'])) {
			break;
		}
	}
	return $CurrBrowser;
}


function detectOS() {
	$txt = $_SERVER['HTTP_USER_AGENT'];
	$OSList = array (
		'Windows 3.11' => '/Win16/',
		'Windows 95' => '/(Windows 95)|(Win95)|(Windows_95)/',
		'Windows 98' => '/(Windows 98)|(Win98)/',
		'Windows 2000' => '/(Windows NT 5.0)|(Windows 2000)/',
 		'Windows XP' => '/(Windows NT 5.1)|(Windows XP)/',
		'Windows Server 2003' => '/(Windows NT 5.2)/',
		'Windows Vista' => '/(Windows NT 6.0)/',
		'Windows 7' => '/(Windows NT 6.1)/',
		'Windows NT 4.0' => '/(Windows NT 4.0)|(WinNT4.0)|(WinNT)|(Windows NT)/',
		'Windows ME' => '/Windows ME/',
		'Open BSD' => '/OpenBSD/',
		'Sun OS' => '/SunOS/',
		'iOS' => '/(iPhone)|(iPad)/',
		'Android' => '/(Android)/',
		'Linux' => '/(Linux)|(X11)/',
		'Mac OSX' => '/(Mac_PowerPC)|(Macintosh)/',
		'QNX' => '/QNX/',
		'BeOS' => '/BeOS/',
		'Search Bot'=>'/(nuhk)|(Googlebot)|(Yammybot)|(Openbot)|(Slurp)|(MSNBot)|(Ask Jeeves/Teoma)|(ia_archiver)/'
	);
	foreach($OSList as $currOS=>$match) {
		if (preg_match($match, $txt)) {
			break;
		}
	}
	return $currOS;
}


function genNonce($length) {
	$nonce = random_bytes($length);
	$b64 = base64_encode($nonce);
	$url = strtr($b64, '+/', '-_');
	return rtrim($url, '=');
}


function genCspPolicy() {
	global $cspReport;
	$_SESSION['nonce'] = genNonce(8);
	$cspPolicy = "Content-Security-Policy: ";
	$cspPolicy .= "default-src 'none' ; ";
	$cspPolicy .= sprintf("script-src 'nonce-%s' ; ", $_SESSION['nonce']);
	$cspPolicy .= sprintf("style-src 'nonce-%s' ; ", $_SESSION['nonce']);
	$cspPolicy .= sprintf("style-src-elem 'nonce-%s' ; ", $_SESSION['nonce']);
	$cspPolicy .= "img-src 'self' ; ";
	$cspPolicy .= "font-src 'self' ; ";
	$cspPolicy .= "connect-src 'self' ; ";
	$cspPolicy .= "frame-ancestors 'none' ; ";
	$cspPolicy .= "base-uri 'none' ; ";
	$cspPolicy .= sprintf("report-uri %s ; ", $cspReport);
	return $cspPolicy;
}


function genSyslog($caller, $msg='') {
	global $progVersion;
	$log = array();
	$log['program'] = 'evalsmsi';
	$log['version'] = $progVersion;
	if (isset($_SESSION['os'])) {
		$log['os'] = $_SESSION['os'];
	}
	if (isset($_SESSION['ipaddr'])) {
		$log['ipaddr'] = $_SESSION['ipaddr'];
	}
	if (isset($_SESSION['browser'])) {
		$log['browser'] = $_SESSION['browser'];
	}
	$log['file'] = basename($_SERVER['PHP_SELF']);
	$log['function'] = $caller;
	if (isset($_SESSION['login'])) {
		$log['login'] = $_SESSION['login'];
	}
	if (isset($_SESSION['id_etab'])) {
		$log['etablissement'] = $_SESSION['id_etab'];
	}
	if (isset($_SESSION['quiz'])) {
		$log['quiz'] = $_SESSION['quiz'];
	}
	if (!empty($msg)) {
		$log['message'] = $msg;
	}
	openlog("evalsmsi", LOG_PID, LOG_SYSLOG);
	syslog(LOG_INFO, json_encode($log));
	closelog();
}


function headPage($titre, $sousTitre='') {
	genSyslog(__FUNCTION__);
	$cspPolicy = genCspPolicy();
	$nonce = $_SESSION['nonce'];
	header("cache-control: no-cache, must-revalidate");
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Content-type: text/html; charset=utf-8");
	header("X-Content-Type-Options: nosniff");
	header("X-XSS-Protection: 1; mode=block;");
	header("X-Frame-Options: deny");
	header($cspPolicy);
	ini_set('default_charset', 'UTF-8');
	printf("<!DOCTYPE html><html lang='fr-FR'><head>");
	printf("<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>");
	printf("<meta name='author' content='Michel Dubois'>");
	printf("<title>%s</title>", $titre);
	printf("<link rel='apple-touch-icon' href='pict/logoArchoadApple.png'>");
	printf("<link rel='icon' type='image/png' href='pict/favicon.png'>");
	printf("<link nonce='%s' href='styles/style.%s.css' rel='StyleSheet' type='text/css' media='all'>", $nonce, $_SESSION['theme']);
	printf("<link nonce='%s' href='styles/style.base.css' rel='StyleSheet' type='text/css' media='all'>", $nonce);
	if (isset($_SESSION['curr_script'])) {
		$script = $_SESSION['curr_script'];
		if ($script === 'etab.php') {
			printf("<link nonce='%s' href='js/chart.min.css' rel='stylesheet' type='text/css' media='all'>", $nonce);
			printf("<script nonce='%s' src='js/chart.min.js'></script>", $nonce);
			printf("<script nonce='%s' src='js/evalsmsi.js'></script>", $nonce);
			printf("<script nonce='%s' src='js/mfa.js'></script>", $nonce);
			printf("<script nonce='%s' src='js/graphs.js'></script>", $nonce);
			printf("<script nonce='%s'>var timeout=%d; setInterval(alertSession, 10000);</script>", $nonce, $_SESSION['expire']);
		}
		if ($script === 'audit.php') {
			printf("<link nonce='%s' href='js/chart.min.css' rel='stylesheet' type='text/css' media='all'>", $nonce);
			printf("<script nonce='%s' src='js/chart.min.js'></script>", $nonce);
			printf("<link nonce='%s' href='js/vis.min.css' rel='stylesheet' type='text/css' media='all'>", $nonce);
			printf("<script nonce='%s' src='js/vis.min.js'></script>", $nonce);
			printf("<script nonce='%s' src='js/evalsmsi.js'></script>", $nonce);
			printf("<script nonce='%s' src='js/mfa.js'></script>", $nonce);
			printf("<script nonce='%s' src='js/graphs.js'></script>", $nonce);
			printf("<script nonce='%s'>var timeout=%d; setInterval(alertSession, 10000);</script>", $nonce, $_SESSION['expire']);
		}
		if ($script === 'admin.php') {
			printf("<script nonce='%s' src='js/evalsmsi.js'></script>", $nonce);
			printf("<script nonce='%s' src='js/mfa.js'></script>", $nonce);
		}
	}
	printf("</head><body><h1>%s</h1>", $titre);
	if ($sousTitre !== '') {
		printf("<h2>%s</h2>", $sousTitre);
	} else {
		printf("<h2>%s</h2>", uidToEtbs());
	}
	if (isset($_SESSION['quiz']) && !in_array(intval($_SESSION['role']), array(1, 2))) {
		printf("<h4>%s</h4>", getQuizName());
	}
}


function footPage($link='', $msg=''){
	genSyslog(__FUNCTION__);
	if ($_SESSION['role']==='100') {
		printf("<div class='footer'>");
		printf("Aide en ligne - Retour à la page d'accueil <a href='evalsmsi.php' class='btnWarning'>cliquer ici</a>");
		printf("</div>");
		printf("</body></html>");
	} else {
		if (strlen($link) AND strlen($msg)) {
			printf("<div class='foot'>");
			printf("<a href='%s'>%s</a>", $link, $msg);
			printf("</div>");
			printf("<p>&nbsp;</p>");
		}
		printf("<div class='footer'>");
		printf("%s", infoSession());
		printf("</div>");
		printf("</body></html>");
	}
}


function validForms($msg, $url, $back=True) {
	if (isset($_SESSION['token'])) { unset($_SESSION['token']); }
	$_SESSION['token'] = generateToken();
	printf("<fieldset><legend>Validation</legend>");
	printf("<table><tr><td>");
	printf("<input type='submit' value='%s'>", $msg);
	if ($back) {
		printf("<input type='reset' value='Effacer'>");
	}
	printf("<a class='valid' href='%s?action=rm_token'>Revenir</a>", $url);
	printf("</td></tr></table></fieldset>");
}


function linkMsg($link, $msg, $img, $class='msg') {
	printf("<div class='%s'>", $class);
	printf("<div><img src='pict/%s' alt='info'></div>", $img);
	if ($link==='#') {
		printf("<div><p>%s</p></div>", $msg);
	} else {
		printf("<div><a href='%s'>%s</a></div>", $link, $msg);
	}
	printf("</div>");
}


function recordLog() {
	genSyslog(__FUNCTION__);
	$id_etab = $_SESSION['id_etab'];
	$annee = $_SESSION['annee'];
	$id_quiz = $_SESSION['quiz'];
	$base = dbConnect();
	$request = sprintf("SELECT reponses FROM assess WHERE annee='%s' AND etablissement='%d' AND quiz='%d' LIMIT 1", $annee, $id_etab, $id_quiz);
	$result = mysqli_query($base, $request);
	$row = mysqli_fetch_object($result);
	$rep = unserialize($row->reponses);
	if (empty($rep)) {
		$tabdiff = array();
		foreach($_POST as $key => $val) {
			if (($val != '') && ($val != 0))
				$tabdiff[$key]=$val;
		}
	} else {
		$tabdiff = array_diff_assoc($_POST, $rep);
	}
	$tabstr = mysqli_real_escape_string($base, serialize($tabdiff));
	$request=sprintf("INSERT INTO journal (ip, etablissement, quiz, navigateur, os, user, action) VALUES ('%s', '%d', '%d', '%s', '%s', '%s', '%s')", $_SESSION['ipaddr'], $id_etab, $id_quiz, $_SESSION['browser'], $_SESSION['os'], $_SESSION['login'], $tabstr);
	mysqli_query($base, $request);
	dbDisconnect($base);
}


function traiteStringToBDD($str) {
	$str = str_split($str);
	$temp = '';
	for($i=0; $i<count($str); $i++) {
		switch ($str[$i]) {
			case '+':
			case '=':
			case '|':
				$temp .= ' ';
				break;
			default:
				$temp .= $str[$i];
				break;
		}
	}
	$temp = str_split($temp);
	$output = '';
	for($i=0; $i<count($temp); $i++) {
		if (isset($temp[$i+1])) {
			$chrNum = sprintf("%d%d", ord($temp[$i]), ord($temp[$i+1]));
			switch ($chrNum) {
				case '4039': // remove ('
				case '3941': // remove ')
				case '4041': // remove ()
				case '4747': // remove //
					$output .= ' ';
					$i += 1;
					break;
				default:
					$output .= $temp[$i];
					break;
			}
		} else {
			$output .= $temp[$i];
		}
	}
	$output = strip_tags($output);
	return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
}


function traiteStringFromBDD($str){
	return htmlspecialchars_decode($str, ENT_QUOTES);
}


function generateToken() {
	$token = hash('sha3-256', random_bytes(32));
	return $token;
}


function setRightQuiz($id) {
	$base = dbConnect();
	$request = sprintf("SELECT * FROM quiz WHERE id='%d' LIMIT 1", intval($id));
	$result = mysqli_query($base, $request);
	dbDisconnect($base);
	if (mysqli_num_rows($result)) {
		$row = mysqli_fetch_object($result);
		$_SESSION['quiz'] = $row->id;
		return true;
	} else {
		if (isset($_SESSION['quiz'])) {
			unset($_SESSION['quiz']);
		}
		return false;
	}
}


function chooseQuiz() {
	$script = $_SESSION['curr_script'];
	$base = dbConnect();
	$request = sprintf("SELECT * FROM quiz");
	$result = mysqli_query($base, $request);
	dbDisconnect($base);
	printf("<form method='post' id='quiz' action='%s?action=set_quiz'>", $script);
	printf("<fieldset><legend>Choix d'un questionnaire d'audit</legend>");
	printf("<table><tr><td>");
	printf("Questionnaire:&nbsp;");
	printf("<select name='id_quiz' id='id_quiz' required>");
	printf("<option selected='selected' value=''>&nbsp;</option>");
	while($row = mysqli_fetch_object($result)) {
		printf("<option value='%s'>%s</option>", $row->id, $row->nom);
	}
	printf("</select>");
	printf("</td></tr></table></fieldset>");
	validForms('Continuer', $script);
	printf("</form>");
}


function controlAssessment($answer) {
	global $noteMax;
	foreach ($answer as $key => $value){
		if (substr($key, 0, 7) === 'comment') {
			$answer[$key] = traiteStringToBDD($value);
		}
		if (substr($key, 0, 8) === 'question') {
			$tmp = intval($value);
			if ($tmp<0 || $tmp>$noteMax) {
				$tmp = 0;
			}
			$answer[$key] = $tmp;
		}
		if (substr($key, 0, 4) === 'eval') {
			$answer[$key] = traiteStringToBDD($value);
		}
	}
	$base = dbConnect();
	$answer = mysqli_real_escape_string($base, serialize($answer));
	dbDisconnect($base);
	return $answer;
}


function uidToEtbs() {
	$base = dbConnect();
	$request = sprintf("SELECT nom FROM etablissement WHERE id='%d' LIMIT 1", intval($_SESSION['id_etab']));
	$result = mysqli_query($base, $request);
	dbDisconnect($base);
	if ($result->num_rows) {
		$row = mysqli_fetch_object($result);
		return $row->nom;
	} else {
		return false;
	}
}


function getRole($id) {
	$base = dbConnect();
	$request = sprintf("SELECT intitule FROM role WHERE id='%d' LIMIT 1", intval($id));
	$result = mysqli_query($base, $request);
	$row = mysqli_fetch_object($result);
	dbDisconnect($base);
	return $row->intitule;
}


function getDomLibelle($id_quiz) {
	global $cheminDATA;
	$base = dbConnect();
	$request = sprintf("SELECT filename FROM quiz WHERE id='%d' LIMIT 1", $id_quiz);
	$result = mysqli_query($base, $request);
	$row = mysqli_fetch_object($result);
	dbDisconnect($base);
	$jsonFile = sprintf("%s%s", $cheminDATA, $row->filename);
	$jsonSource = file_get_contents($jsonFile);
	$jsonQuiz = json_decode($jsonSource, true);
	$domLibelle = array();
	for ($d=0; $d<count($jsonQuiz); $d++) {
		$domLibelle[$jsonQuiz[$d]['numero']] = $jsonQuiz[$d]['libelle'];
	}
	return $domLibelle;
}


function getAllDomAbstract() {
	$quiz = getJsonFile();
	$domAbstract = array();
	for ($d=0; $d<count($quiz); $d++) {
		$domAbstract[] = $quiz[$d]['abrege'];
	}
	return $domAbstract;
}


function getSubParLibelle($num) {
	$quiz = getJsonFile();
	for ($i=0; $i<count($quiz); $i++) {
		if ($quiz[$i]['numero'] === $num) {
			$subDom = $quiz[$i]['subdomains'];
		}
	}
	$subDomLibelle = array();
	for ($i=0; $i<count($subDom); $i++) {
		$subDomLibelle[] = $subDom[$i]['libelle'];
	}
	return $subDomLibelle;
}


function getEtablissement($id_etab=0, $abrege=0) {
	if ($id_etab<>0) {
		$base = dbConnect();
		$request = sprintf("SELECT id, nom, abrege FROM etablissement WHERE id='%d' LIMIT 1", $id_etab);
		$result=mysqli_query($base, $request);
		dbDisconnect($base);
		$row = mysqli_fetch_object($result);
		if ($abrege) {
			return traiteStringFromBDD($row->abrege);
		} else {
			return traiteStringFromBDD($row->nom);
		}
	} else {
		$base = dbConnect();
		if (intval($_SESSION['role']) === 2) {
			$request = sprintf("SELECT id, nom, abrege FROM etablissement WHERE id IN (%s) ORDER BY nom", $_SESSION['audit_etab']);
		} else {
			$request = "SELECT * FROM etablissement ORDER BY nom";
		}
		$result = mysqli_query($base, $request);
		dbDisconnect($base);
		return $result;
	}
}


function isRegroupEtab() {
	$id_etab = $_SESSION['id_etab'];
	$base = dbConnect();
	$request = sprintf("SELECT abrege FROM etablissement WHERE id='%d' LIMIT 1", $id_etab);
	$result = mysqli_query($base, $request);
	dbDisconnect($base);
	$row = mysqli_fetch_object($result);
	if (stripos($row->abrege, "_TEAM") === false) {
		return false;
	} else {
		return true;
	}
}


function isRegroupEtabbyId($id_etab) {
	$base = dbConnect();
	$request = sprintf("SELECT abrege FROM etablissement WHERE id='%d' LIMIT 1", $id_etab);
	$result = mysqli_query($base, $request);
	dbDisconnect($base);
	$row = mysqli_fetch_object($result);
	if (stripos($row->abrege, "_TEAM") === false) {
		return false;
	} else {
		return true;
	}
}


function getCredentialFromDb() {
	$base = dbConnect();
	$request = sprintf("SELECT credential_id, public_key, sign_count FROM users WHERE login='%s' LIMIT 1", $_SESSION['login']);
	$result = mysqli_query($base, $request);
	$row = mysqli_fetch_object($result);
	dbDisconnect($base);
	if (isset($row->credential_id)) {
		$_SESSION['registration']['credentialId'] = $row->credential_id;
		$_SESSION['registration']['credentialPublicKeyPEM'] = $row->public_key;
		$_SESSION['registration']['signCount'] = $row->sign_count;
	}
	return($result);
}


function registerWebauthnCred() {
	printf("<div class='msg'><div><img id='registerImg' src='pict/fido2key.png' alt='info'></div><div><p id='registerMsg'></p></div></div>");
	printf("<div class='none' id='pubKey'><div><img src='pict/public_key.png' alt='pubkey'></div><div id='msgPubKey'></div></div>");
	printf("<script nonce='%s'>document.body.addEventListener('load', newRegistration());</script>", $_SESSION['nonce']);
}


function changePassword() {
	genSyslog(__FUNCTION__);
	$script = $_SESSION['curr_script'];
	$nonce = $_SESSION['nonce'];
	$base = dbConnect();
	$request = sprintf("SELECT * FROM users WHERE login='%s' LIMIT 1", $_SESSION['login']);
	$result = mysqli_query($base, $request);
	dbDisconnect($base);
	if (mysqli_num_rows($result)) {
		$row = mysqli_fetch_array($result);
		printf("<form method='post' id='chg_password' action='%s?action=chg_password'>", $script);
		printf("<fieldset><legend>Changement de mot de passe</legend>");
		printf("<table><tr><td>");
		printf("<input type='password' size='30' maxlength='30' name='new1' id='new1' placeholder='Nouveau mot de passe' autocomplete='new-password' required>");
		printf("</td></tr><tr><td>");
		printf("<input type='password' size='30' maxlength='30' name='new2' id='new2' placeholder='Saisissez à nouveau le mot de passe' autocomplete='new-password' required>");
		printf("</td></tr></table>");
		printf("</fieldset>");
		validForms('Enregistrer', $script);
		printf("</form>");
		printf("<script nonce='%s'>document.getElementById('new1').addEventListener('change', function() {validatePattern();});</script>", $nonce);
		printf("<script nonce='%s'>document.getElementById('new2').addEventListener('change', function() {validatePassword();});</script>", $nonce);
	} else {
		linkMsg("#", "Erreur de compte.", "alert.png");
		footPage($script, "Accueil");
	}
}


function recordNewPassword($passwd) {
	genSyslog(__FUNCTION__);
	$base = dbConnect();
	$passwd = password_hash($passwd, PASSWORD_BCRYPT);
	$request = sprintf("UPDATE users SET password='%s' WHERE login='%s'", $passwd, $_SESSION['login']);
	if (isset($_SESSION['token'])) {
		unset($_SESSION['token']);
		if (mysqli_query($base, $request)) {
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
	return false;
}


function getAuditor() {
	$id_etab = $_SESSION['id_etab'];
	$auditor = '';
	$base = dbConnect();
	$request = sprintf("SELECT nom, prenom, etablissement FROM users WHERE role='2'");
	$result = mysqli_query($base, $request);
	while($row=mysqli_fetch_object($result)) {
		$etabs = explode(',', $row->etablissement);
		if (in_array($id_etab, $etabs)) {
			$auditor = sprintf("%s %s", traiteStringFromBDD($row->prenom), traiteStringFromBDD($row->nom));
		}
	}
	dbDisconnect($base);
	return $auditor;
}


function getRSSI($id_etab) {
	$base = dbConnect();
	$request = sprintf("SELECT nom,prenom FROM users WHERE role='4' AND etablissement='%d' LIMIT 1", intval($id_etab));
	$result = mysqli_query($base, $request);
	dbDisconnect($base);
	if (mysqli_num_rows($result)) {
		$row = mysqli_fetch_object($result);
		$name = traiteStringFromBDD($row->prenom). ' ' . strtoupper(traiteStringFromBDD($row->nom));
	} else {
		$name = '';
	}
	return $name;
}


function getDirector($id_etab) {
	$base = dbConnect();
	$request = sprintf("SELECT nom,prenom FROM users WHERE role='3' AND etablissement='%d' LIMIT 1", intval($id_etab));
	$result = mysqli_query($base, $request);
	dbDisconnect($base);
	if (mysqli_num_rows($result)) {
		$row = mysqli_fetch_object($result);
		$name = traiteStringFromBDD($row->prenom). ' ' . strtoupper(traiteStringFromBDD($row->nom));
	} else {
		$name = '';
	}
	return $name;
}


function isThereAssessForEtab() {
	$id_etab = $_SESSION['id_etab'];
	$annee = $_SESSION['annee'];
	if (isset($_SESSION['quiz'])) {
		$id_quiz = $_SESSION['quiz'];
		if (isRegroupEtab()) {
			$base = dbConnect();
			$temp = array();
			$request = sprintf("SELECT regroupement FROM etablissement WHERE id='%d' LIMIT 1", $id_etab);
			$result = mysqli_query($base, $request);
			$row = mysqli_fetch_object($result);
			$etabs = explode(',', $row->regroupement);
			foreach ($etabs as $id) {
				$request = sprintf("SELECT * FROM assess WHERE etablissement='%d' AND annee='%d' AND quiz='%d' LIMIT 1", $id, $annee, $id_quiz);
				$result = mysqli_query($base, $request);
				if ($result->num_rows) {
					$temp[$id] = true;
				} else {
					$temp[$id] = false;
				}
			}
			dbDisconnect($base);
			if (in_array(false, $temp)) {
				return false;
			} else {
				return true;
			}
		} else {
			$base = dbConnect();
			$request = sprintf("SELECT * FROM assess WHERE etablissement='%d' AND annee='%d' AND quiz='%d' LIMIT 1", $id_etab, $annee, $id_quiz);
			$result=mysqli_query($base, $request);
			dbDisconnect($base);
			if ($result->num_rows) {
				return true;
			} else {
				return false;
			}
		}
	} else {
		return false;
	}

}


function textItem($num){
	switch ($num) {
		case 1:
			return 'Non applicable';
			break;
		case 2:
			return 'Inexistant et investissement important';
			break;
		case 3:
			return 'Inexistant et investissement peu important';
			break;
		case 4:
			return 'En cours et demande un ajustement';
			break;
		case 5:
			return 'En cours';
			break;
		case 6:
			return 'Existant et demande un ajustement';
			break;
		case 7:
			return 'Opérationnel';
			break;
		default:
			return 'Pas de réponse';
			break;
	}
}


function domainCount() {
	$quiz = getJsonFile();
	return count($quiz);
}

function subDomainCount($num) {
	$quiz = getJsonFile();
	for ($i=0; $i<count($quiz); $i++) {
		if ($quiz[$i]['numero'] === $num) {
			$subDom = $quiz[$i]['subdomains'];
		}
	}
	return count($subDom);
}


function questionsCount() {
	$quiz = getJsonFile();
	$nbrQuestion = 0;
	for ($d=0; $d<count($quiz); $d++) {
		$subDom = $quiz[$d]['subdomains'];
		for ($sd=0; $sd<count($subDom); $sd++) {
			$questions = $subDom[$sd]['questions'];
			$nbrQuestion += count($questions);
		}
	}
	return $nbrQuestion;
}


function printSelect($num_dom, $num_sub_dom, $num_quest, $assessment=0) {
	$nonce = $_SESSION['nonce'];
	$name = 'question'.$num_dom.'_'.$num_sub_dom.'_'.$num_quest;
	printf("<select name='%s' id='%s' >", $name, $name);
	if ($assessment) {
		if ($assessment[$name] == 0) {
			printf("<option selected='selected' value='0'>&nbsp;</option>");
		} else {
			printf("<option selected='selected' value='%d'>%d - %s</option>",$assessment[$name], $assessment[$name], textItem($assessment[$name]));
			printf("<option value='0'>&nbsp;</option>");
		}
	} else {
		printf("<option selected='selected' value='0'>&nbsp;</option>");
	}
	for ($i=1; $i<8; $i++) {
		printf("<option value='%d'>%d - %s</option>", $i, $i, textItem($i));
	}
	printf("</select>");
	printf("<script nonce='%s'>document.getElementById('%s').addEventListener('change', function() {progresse();});</script>", $nonce, $name);
}


function getColorButton($complete, $num) {
	if ($complete[$num] == 0) {
		$color = "<span class='redpoint'>&nbsp;</span>";
	} elseif ($complete[$num] == 1) {
		$color = "<span class='orangepoint'>&nbsp;</span>";
	} else {
		$color = "<span class='greenpoint'>&nbsp;</span>";
	}
	return $color;
}


function domainComplete($assessment) {
	$val = 0;
	$cpt_total = 0;
	$cpt_encours = 0;
	$nbrPar = domainCount();
	$result = array_fill(0, $nbrPar+1, 0);
	if (empty($assessment)) return $result;
	foreach ($assessment as $key=>$value) {
		if (preg_match("/question/", $key)) {
			$temp = preg_replace("/question/", "", $key);
			$temp = explode("_", $temp);
			if ($val <> $temp[0]) {
				if ($cpt_encours == $cpt_total)
					$result[$val] = 0;
				elseif ($cpt_encours <> 0)
					$result[$val] = 1;
				else
					$result[$val] = 2;
				$val = $temp[0];
				$cpt_total = 0;
				$cpt_encours = 0;
			}
			if ($value == 0) {
				$cpt_encours++;
			}
			$cpt_total++;
		}
		if ($cpt_encours == $cpt_total)
			$result[$val] = 0;
		elseif ($cpt_encours <> 0)
			$result[$val] = 1;
		else
			$result[$val] = 2;
	}
	return $result;
}


function subDomainComplete($assessment, $dom, $subdom) {
	$cpt_total = 0;
	$cpt_encours = 0;
	$nbrSubDom = subDomainCount($dom);
	$result = array_fill(0, $nbrSubDom+1, 0);
	if (empty($assessment)) return $result;
	foreach ($assessment as $key=>$value) {
		if (preg_match("/question/", $key)) {
			$temp = preg_replace("/question/", "", $key);
			$temp = explode("_", $temp);
			if (($temp[0] == $dom) && ($temp[1] == $subdom)) {
				if ($value == 0) $cpt_encours++;
				$cpt_total++;
			}
		}
	}
	if ($cpt_encours == $cpt_total)
		$result[$subdom] = 0;
	elseif ($cpt_encours <> 0)
		$result[$subdom] = 1;
	else
		$result[$subdom] = 2;
	return $result;
}


function afficheNotesExplanation() {
	printf("<div class='column littleright sticky'>");
	printf("<div class='event'>");
	printf("<dl>");
	printf("<dt>1: %s</dt>", textItem(1));
	printf("<dd>La règle est non applicable ou à fait l'objet d'une dérogation (à préciser dans le commentaire).</dd>");
	printf("<dt>2: %s</dt>", textItem(2));
	printf("<dd>La disposition proposée n’est pas appliquée actuellement et ne le sera pas avant un délai important (mesure non planifiée, mesure nécessitant une étude préalable importante, mesure nécessitant un budget important, etc.).</dd>");
	printf("<dt>3: %s</dt>", textItem(3));
	printf("<dd>La disposition proposée n’est pas appliquée actuellement, mais le sera rapidement, car sa mise en oeuvre est facile et/ou rapide.</dd>");
	printf("<dt>4: %s</dt>", textItem(4));
	printf("<dd>La disposition proposée est en cours de réalisation (état d'avancement à 30%% au minimum), mais des difficultés sont rencontrées et les plans prévus de réalisation doivent être modifiés.</dd>");
	printf("<dt>5: %s</dt>", textItem(5));
	printf("<dd>La disposition proposée est en cours de réalisation (état d'avancement à 60%% au minimum) et se déroule sans encombre.</dd>");
	printf("<dt>6: %s</dt>", textItem(6));
	printf("<dd>La disposition est mise en place et il reste quelques ajustements à réaliser pour la rendre totalement opérationnelle (état d'avancement à 90%% au minimum).</dd>");
	printf("<dt>7: %s</dt>", textItem(7));
	printf("<dd>La disposition est opérationnelle et remplit entièrement les besoins demandés</dd>");
	printf("</dl></div>");
	printf("</div>");
}


function extractSubDomRep($id_dom, $table) {
	$result = array();
	foreach ($table as $question => $eval) {
		$numQuestion = explode('_', $question);
		if ($numQuestion[0] == $id_dom) {
			$result[$question] = $eval;
		}
	}
	return $result;
}


function calculNotes($table) {
	$mem = 1; // numéro du premier paragraphe
	$sumEval = 0;
	$sumPoids = 0;
	$noteFinale = array();
	foreach ($table as $question => $eval) {
		$numQuestion = explode('_', $question);
		$nq = $numQuestion[0];
		if ($mem == $nq) {
			$poids = getPoidsQuestion($question);
			$sumEval = $sumEval + ($eval * $poids);
			$sumPoids = $sumPoids + $poids;
		} else {
			$noteFinale[$mem] = round($sumEval / $sumPoids, 2);
			$mem=$nq;
			$sumEval = ($eval * $poids);
			$sumPoids = $poids;
		}
	}
	$noteFinale[$mem] = round($sumEval / $sumPoids, 2);
	return $noteFinale;
}


function calculNotesDetail($table, $mem=11) {
	//$mem = 11 -> numéro du premier sous-paragraphe
	$sumEval = 0;
	$sumPoids = 0;
	$noteFinale = array();
	foreach ($table as $question => $eval) {
		$numQuestion = explode('_', $question);
		$nq = $numQuestion[0].$numQuestion[1];
		if ($mem == $nq) {
			$poids = getPoidsQuestion($question);
			$sumEval = $sumEval + ($eval * $poids);
			$sumPoids = $sumPoids + $poids;
		} else {
			$noteFinale[$mem] = round($sumEval / $sumPoids, 2);
			$mem=$nq;
			$sumEval = ($eval * $poids);
			$sumPoids = $poids;
		}
	}
	$noteFinale[$mem] = round($sumEval / $sumPoids, 2);
	return $noteFinale;
}


function getPoidsQuestion($num) {
	$weight = 0;
	$question = explode('_', $num);
	$quiz = getJsonFile();
	for ($i=0; $i<count($quiz); $i++) {
		if ($quiz[$i]['numero'] === intval($question[0])) {
			$subDom = $quiz[$i]['subdomains'];
		}
	}
	for ($i=0; $i<count($subDom); $i++) {
		if ($subDom[$i]['numero'] === intval($question[1])) {
			$questions = $subDom[$i]['questions'];
		}
	}
	for ($i=0; $i<count($questions); $i++) {
		if ($questions[$i]['numero'] === intval($question[2])) {
			$weight = $questions[$i]['poids'];
		}
	}
	return $weight;
}


function getObjectives() {
	$base = dbConnect();
	$request = sprintf("SELECT objectifs FROM etablissement WHERE id='%d' LIMIT 1", $_SESSION['id_etab']);
	$result = mysqli_query($base, $request);
	$row = mysqli_fetch_object($result);
	dbDisconnect($base);
	$objectives = json_decode($row->objectifs, true);
	$output = array();
	foreach ($objectives[$_SESSION['quiz']] as $key => $value) {
		$output[] = $value;
	}
	return $output;
}


function isAssessComplete($table) {
	$result = true;
	$temp = array_values($table);
	if (in_array(0, $temp))
		$result = false;
	return $result;
}


function getAnswers() {
	$id_etab = $_SESSION['id_etab'];
	$id_quiz = $_SESSION['quiz'];
	$base = dbConnect();
	$request = sprintf("SELECT * FROM assess WHERE etablissement='%d' AND quiz='%d' ORDER BY annee", $id_etab, $id_quiz);
	$result = mysqli_query($base, $request);
	$answers = array();
	while ($row = mysqli_fetch_object($result)) {
		if (!empty($row->reponses)) {
			$an = $row->annee;
			foreach(unserialize($row->reponses) as $quest => $rep) {
				if (substr($quest, 0, 8) == 'question') {
					$answers[$an][substr($quest, 8, 14)] = $rep;
				}
			}
		}
	}
	dbDisconnect($base);
	return $answers;
}


function getRegroupAssessment() {
	$id_etab = $_SESSION['id_etab'];
	$id_quiz = $_SESSION['quiz'];
	$annee = $_SESSION['annee'];
	$abrege_etab = getEtablissement($id_etab, $abrege=1);
	$base = dbConnect();
	$request = sprintf("SELECT regroupement FROM etablissement WHERE id='%d' LIMIT 1", $id_etab);
	$result = mysqli_query($base, $request);
	$row = mysqli_fetch_object($result);
	$etabs = explode(',', $row->regroupement);
	$num_etabs = count($etabs);
	$answers = array();
	$allAssessments = array();
	foreach ($etabs as $id) {
		$request = sprintf("SELECT * FROM assess WHERE etablissement='%d' AND annee='%d' AND quiz='%d' LIMIT 1", $id, $annee, $id_quiz);
		$result = mysqli_query($base, $request);
		$row = mysqli_fetch_object($result);
		$assessment = unserialize($row->reponses);
		foreach($assessment as $quest => $rep) {
			if (array_key_exists($quest, $answers)) {
				if (substr($quest, 0, 8) == 'question') {
					$answers[$quest] += intval($rep);
				} else {
					if (!empty($rep)) {
						$answers[$quest] .= "\r".traiteStringFromBDD($rep);
					}
				}
			} else {
				if (substr($quest, 0, 8) == 'question') {
					$answers[$quest] = intval($rep);
				} else {
					$answers[$quest] = traiteStringFromBDD($rep);
				}
			}
		}
		if (array_key_exists('final_etab_comment', $allAssessments)) {
			if (!empty($row->comments)) {
				$allAssessments['final_etab_comment'] .= "\r".traiteStringFromBDD($row->comments);
			}
		} else {
			$allAssessments['final_etab_comment'] = traiteStringFromBDD($row->comments);
		}
		if (array_key_exists('auditor_comment', $allAssessments)) {
			if (!empty($row->comment_graph_par)) {
				$allAssessments['auditor_comment'] .= "\r".traiteStringFromBDD($row->comment_graph_par);
			}
		} else {
			$allAssessments['auditor_comment'] = traiteStringFromBDD($row->comment_graph_par);
		}
	}
	foreach ($answers as $quest => $rep) {
		if (substr($quest, 0, 8) == 'question') {
			$rep = $rep / $num_etabs;
			$answers[$quest] = $rep;
		}
	}
	$allAssessments['assessment'] = $answers;
	dbDisconnect($base);
	return $allAssessments;
}


function getRegroupAnswers() {
	$assessment = getRegroupAssessment();
	$annee = $_SESSION['annee'];
	$answers = array();
	foreach($assessment['assessment'] as $quest => $rep) {
		if (substr($quest, 0, 8) == 'question') {
			$answers[$annee][substr($quest, 8, 14)] = $rep;
		}
	}
	$answers['final_etab_comment'] = $assessment['final_etab_comment'];
	$answers['auditor_comment'] = $assessment['auditor_comment'];
	return $answers;
}


function defineGraphVariables($nbrPar) {
	global $noteMax;
	$tmp = sprintf("\\newcommand{\\dimension}{%d}\n", $nbrPar);
	$tmp .= sprintf("\\newcommand{\\unit}{%d}\n", $noteMax);
	$tmp .= "\\newcommand{\\axisAngle}{360/\\dimension}\n";
	$tmp .= "\\newdimen\\radiusMax \\setlength{\\radiusMax}{130pt}\n";
	$tmp .= "\\newdimen\\labelDist \\setlength{\\labelDist}{\\radiusMax + 15pt}\n";
	$tmp .= "\\definecolor{year1}{RGB}{255,205,86}\n";
	$tmp .= "\\definecolor{year2}{RGB}{75,192,192}\n";
	$tmp .= "\\definecolor{year3}{RGB}{153,102,255}\n";
	$tmp .= "\\definecolor{objectiv}{RGB}{255,99,132}\n\n";
	$tmp .= "\\tikzstyle{myShape} = [line width=1.5pt, opacity=0.9]\n";
	$tmp .= "\\tikzstyle{myShapeFill} = [opacity=0.5]\n";
	$tmp .= "\\tikzstyle{myLabel} = [font=\\scriptsize, text width=45pt, text badly centered, inner sep=0pt]\n";
	$tmp .= "\\tikzstyle{myLegend} = [font=\\scriptsize, anchor=west]\n";
	$tmp .= "\\tikzstyle{myTitle} = [font=\\bfseries, fill=myBeige, text width=90pt, text badly centered, inner sep=5pt, rounded corners=2pt]\n";
	$tmp .= "\\tikzstyle{myMatrix} = [fill=myBeige, inner sep=5pt, rounded corners=2pt]\n";
	return $tmp;
}


function simpleYearGraph($annee, $notes, $titles_par) {
	$objectifs = getObjectives();

	$txtGraph = "\\begin{center}\n\\begin{tikzpicture}[background rectangle/.style={fill=myBeige!45, draw=myBeige, rounded corners=4pt, inner sep=5pt}, show background rectangle]\n";
	$txtGraph .= "\\foreach \\x in {1,...,\\dimension}{ \\draw [color=black!80] (\\x*\\axisAngle:0) -- (\\x*\\axisAngle:\\radiusMax); }\n";
	$txtGraph .= "\\foreach \\y in {0,...,\\unit}{\n";
	$txtGraph .= "\\draw [color=black!40] (0:\\y*\\radiusMax/\\unit) \\foreach \\x in {1,...,\\dimension}{-- (\\x*\\axisAngle:\\y*\\radiusMax/\\unit)} -- cycle;\n";
	$txtGraph .= "\\foreach \\x in {1,...,\\dimension}{\n";
	$txtGraph .= "\\path (\\x*\\axisAngle:\\y*\\radiusMax/\\unit) coordinate (D\\x-\\y);\n";
	$txtGraph .= "\\fill [color=black!80] (D\\x-\\y) circle (1.2pt);\n}\n}\n";
	// label
	for ($i=0; $i<count($titles_par); $i++) {
		$txtGraph .= sprintf("\\path (%d*\\axisAngle:\\labelDist) node [myLabel] {%s};\n", $i+1, $titles_par[$i]);
	}
	$txtGraph .= "\n";
	// Année n
	$temp = "\\draw [myShape, color=year1] ";
	for ($i=0; $i<count($titles_par); $i++) {
		$temp .= sprintf("(D%d-%d) -- ", $i+1, round($notes[$i+1]));
	}
	$txtGraph .=  $temp."cycle;\n\n";
	$temp = "\\fill [myShapeFill, color=year1!20] ";
	for ($i=0; $i<count($titles_par); $i++) {
		$temp .= sprintf("(D%d-%d) -- ", $i+1, round($notes[$i+1]));
	}
	$txtGraph .=  $temp."cycle;\n\n";
	// Objectifs
	$temp = "\\draw [myShape, color=objectiv] ";
	for ($i=0; $i<count($titles_par); $i++) {
		$temp .= sprintf("(D%d-%d) -- ", $i+1, $objectifs[$i]);
	}
	$txtGraph .=  $temp."cycle;\n\n";
	// legend
	$txtGraph .= "\\matrix [myMatrix, xshift=180pt, yshift=-100pt] {\n";
	$txtGraph .= "\\node [rectangle, fill=objectiv] {}; & \\node [myLegend] {Objectifs}; \\\\\n";
	$txtGraph .= sprintf("\\node [rectangle, fill=year1] {};  & \\node [myLegend] {Année %d}; \\\\\n};\n", $annee);
	// title
	$txtGraph .= "\\node [myTitle, xshift=180pt, yshift=40pt] {Résultats par domaines};\n";
	$txtGraph .= "\\end{tikzpicture}\n\\end{center}\n\n";
	return $txtGraph;
}


function cumulatedGraph($cumulNotes, $annee, $titles_par) {
	$objectifs = getObjectives();

	$txtGraph = "\\begin{center}\n\\begin{tikzpicture}[background rectangle/.style={fill=myBeige!45, draw=myBeige, rounded corners=4pt, inner sep=5pt}, show background rectangle]\n";
	$txtGraph .= "\\foreach \\x in {1,...,\\dimension}{ \\draw [color=black!80] (\\x*\\axisAngle:0) -- (\\x*\\axisAngle:\\radiusMax); }\n";
	$txtGraph .= "\\foreach \\y in {0,...,\\unit}{\n";
	$txtGraph .= "\\draw [color=black!40] (0:\\y*\\radiusMax/\\unit) \\foreach \\x in {1,...,\\dimension}{-- (\\x*\\axisAngle:\\y*\\radiusMax/\\unit)} -- cycle;\n";
	$txtGraph .= "\\foreach \\x in {1,...,\\dimension}{\n";
	$txtGraph .= "\\path (\\x*\\axisAngle:\\y*\\radiusMax/\\unit) coordinate (D\\x-\\y);\n";
	$txtGraph .= "\\fill [color=black!80] (D\\x-\\y) circle (1.2pt);\n}\n}\n";
	// label
	for ($i=0; $i<count($titles_par); $i++) {
		$txtGraph .= sprintf("\\path (%d*\\axisAngle:\\labelDist) node [myLabel] {%s};\n", $i+1, $titles_par[$i]);
	}
	$txtGraph .= "\n";
	// Année n-2
	if (isset($cumulNotes[$annee-2])) {
		$curNotes = $cumulNotes[$annee-2];
		$temp = "\\draw [myShape, color=year3] ";
		for ($i=0; $i<count($titles_par); $i++) {
			$temp .= sprintf("(D%d-%d) -- ", $i+1, round($curNotes[$i+1]));
		}
		$txtGraph .=  $temp."cycle;\n\n";
		$temp = "\\fill [myShapeFill, color=year3!20] ";
		for ($i=0; $i<count($titles_par); $i++) {
			$temp .= sprintf("(D%d-%d) -- ", $i+1, round($curNotes[$i+1]));
		}
		$txtGraph .=  $temp."cycle;\n\n";
	}
	// Année n-1
	if (isset($cumulNotes[$annee-1])) {
		$curNotes = $cumulNotes[$annee-1];
		$temp = "\\draw [myShape, color=year2] ";
		for ($i=0; $i<count($titles_par); $i++) {
			$temp .= sprintf("(D%d-%d) -- ", $i+1, round($curNotes[$i+1]));
		}
		$txtGraph .=  $temp."cycle;\n\n";
		$temp = "\\fill [myShapeFill, color=year2!20] ";
		for ($i=0; $i<count($titles_par); $i++) {
			$temp .= sprintf("(D%d-%d) -- ", $i+1, round($curNotes[$i+1]));
		}
		$txtGraph .=  $temp."cycle;\n\n";
	}
	// Année n
	$curNotes = $cumulNotes[$annee];
	$temp = "\\draw [myShape, color=year1] ";
	for ($i=0; $i<count($titles_par); $i++) {
		$temp .= sprintf("(D%d-%d) -- ", $i+1, round($curNotes[$i+1]));
	}
	$txtGraph .=  $temp."cycle;\n\n";
	$temp = "\\fill [myShapeFill, color=year1!20] ";
	for ($i=0; $i<count($titles_par); $i++) {
		$temp .= sprintf("(D%d-%d) -- ", $i+1, round($curNotes[$i+1]));
	}
	$txtGraph .=  $temp."cycle;\n\n";
	// Objectifs
	$temp = "\\draw [myShape, color=objectiv] ";
	for ($i=0; $i<count($titles_par); $i++) {
		$temp .= sprintf("(D%d-%d) -- ", $i+1, $objectifs[$i]);
	}
	$txtGraph .=  $temp."cycle;\n\n";
	// legend
	$txtGraph .= "\\matrix [myMatrix, xshift=180pt, yshift=-100pt] {\n";
	$txtGraph .= "\\node [rectangle, fill=objectiv] {}; & \\node [myLegend] {Objectifs}; \\\\\n";
	if (isset($cumulNotes[$annee-2])) {
		$txtGraph .= sprintf("\\node [rectangle, fill=year3] {};  & \\node [myLegend] {Année %d}; \\\\\n", $annee-2);
	}
	if (isset($cumulNotes[$annee-1])) {
		$txtGraph .= sprintf("\\node [rectangle, fill=year2] {};  & \\node [myLegend] {Année %d}; \\\\\n", $annee-1);
	}
	$txtGraph .= sprintf("\\node [rectangle, fill=year1] {};  & \\node [myLegend] {Année %d}; \\\\\n};\n", $annee);
	// title
	$txtGraph .= "\\node [myTitle, xshift=180pt, yshift=40pt] {Résultats cumulés par domaines};\n";
	$txtGraph .= "\\end{tikzpicture}\n\\end{center}\n\n";
	return $txtGraph;
}


function displayGraphs() {
	printf("<div id='graphs'>");
	printf("<canvas id='currentYearGraphBar'></canvas>");
	printf("<a href='' id='yearGraphBar' class='btnValid' download='yearGraphBar.png' type='image/png'>Télécharger le graphe</a>");
	printf("<p class='separation'>&nbsp;</p>");

	printf("<canvas id='currentYearRadar'></canvas>");
	printf("<a href='' id='yearGraphradar' class='btnValid' download='yearGraphradar.png' type='image/png'>Télécharger le graphe</a>");
	printf("<p class='separation'>&nbsp;</p>");

	printf("<canvas id='currentYearGraphPolar'></canvas>");
	printf("<a href='' id='yearGraphPolar' class='btnValid' download='yearGraphPolar.png' type='image/png'>Télécharger le graphe</a>");
	printf("<p class='separation'>&nbsp;</p>");

	printf("<canvas id='currentYearGraphScatter'></canvas><br>");
	printf("<a href='' id='yearGraphScatter' class='btnValid' download='yearGraphScatter.png' type='image/png'>Télécharger le graphe</a>");
	printf("<p class='separation'>&nbsp;</p>");
	printf("</div>");
	printf("<script nonce='%s'>document.body.addEventListener('load', loadGraphYear());</script>", $_SESSION['nonce']);
}


function displayEtablissmentGraphs() {
	$annee = $_SESSION['annee'];
	$regroup = isRegroupEtab();
	if ($regroup) {
		$reponses = getRegroupAnswers();
	} else {
		$reponses = getAnswers();
	}
	if (sizeof(array_keys($reponses))) {
		if (!$regroup) {
			if (isAssessComplete($reponses[$annee])) {
				linkMsg("#", "L'évaluation pour ".$annee." est complète.", "ok.png");
			} else {
				linkMsg("#", "L'évaluation pour ".$annee." est incomplète, les graphes sont donc partiellement justes.", "alert.png");
			}
		}
		printf("<div class='onecolumn'>");
		assessSynthese($reponses[$annee]);
		displayGraphs();
		printf("</div>");
	} else {
		$msg = sprintf("L'évaluation %d est vide.", $annee);
		linkMsg("#", $msg, "alert.png");
	}
}


function assessSynthese($reponses) {
	$id_etab = $_SESSION['id_etab'];
	$annee = $_SESSION['annee'];
	$id_quiz = $_SESSION['quiz'];
	$titles_par = getAllDomAbstract();

	printf("<table><tr><th colspan='3'>Notes finale des établissements</th></tr>");
	printf("<tr><th>Etablissement</th><th>Détail des notes</th><th>Note finale</th></tr>");
	$name_etab = getEtablissement($id_etab);
	$notes = calculNotes($reponses);
	$text_note = "";
	$noteSum = 0;
	for ($i=0; $i<sizeof($titles_par); $i++) {
		$note = 20 * $notes[$i+1] / 7;
		$noteSum = $noteSum + $note;
		if ($note <= 10) {
			$text_note .= sprintf("<li>%s -> <b class='notok'>%d/20</b></li>", $titles_par[$i], $note);
		} else {
			$text_note .= sprintf("<li>%s -> <b>%d/20</b></li>", $titles_par[$i], $note);
		}
	}
	$noteFinale = 20 * $noteSum / (sizeof($titles_par)*20);
	printf("<tr><td class='assesssynth'><b>%s</b></td><td><ul>%s</ul></td><td><b class='fontvingt'>%d/20</b></td></tr>", $name_etab, $text_note, $noteFinale);
	printf("</table>");
}


function dateToTimestamp($date) {
	$temp = explode(' ', $date);
	$jour = explode('-', $temp[0]);
	$heure = explode(':', $temp[1]);
	return mktime($heure[0],$heure[1],$heure[2],$jour[1],$jour[2],$jour[0]);
}


function timestampToDate($time) {
	return strftime("%a %d %b", $time);
}


function fullTimestampToDate($time) {
	$part1 = strftime("%A %d %B %Y", $time);
	$part2 = strftime("%H:%M", $time);
	return sprintf("le %s à %s", $part1, $part2);
}


function beginningDay($time) {
	$temp = getdate($time);
	$temp['seconds'] = 0;
	$temp['minutes'] = 0;
	$temp['hours'] = 0;
	return mktime($temp['hours'], $temp['minutes'], $temp['seconds'], $temp['mon'], $temp['mday'], $temp['year']);
}


function finishingDay($time) {
	$temp = getdate($time);
	$temp['seconds'] = 59;
	$temp['minutes'] = 59;
	$temp['hours'] = 23;
	return mktime($temp['hours'], $temp['minutes'], $temp['seconds'], $temp['mon'], $temp['mday'], $temp['year']);
}


function getNavigateurName($str) {
	// find browser
	if (preg_match("/Trident/",$str)) {
		$browser = "Internet Explorer";
		$val = stristr($str, "Trident");
		$val = explode("/",$val);
		$val = explode(";",$val[1]);
		$version = $val[0];
	} elseif (preg_match("/Firefox/", $str)) {
		$browser = "Firefox";
		$val = stristr($str, "Firefox");
		$val = explode("/",$val);
		$version = $val[1];
	} elseif (preg_match("/Chrome/", $str)){
		$browser = "Chrome";
		$val = stristr($str, "Chrome");
		$val = explode("/",$val);
		$val = explode(".",$val[1]);
		$version = $val[0].".".$val[1];
	} elseif (preg_match("/Safari/", $str)) {
		$browser = "Safari";
		$val = stristr($str, "Version");
		$val = explode("/",$val);
		$val = explode(" ",$val[1]);
		$version = $val[0];
	} else {
		$browser = "Unknown";
		$version = "Unknown";
	}
	// find operating system
	if (preg_match("/Trident/", $str)) $platform = "Windows";
	elseif (preg_match("/Macintosh/", $str)) $platform = "Apple";
	elseif (preg_match("/linux/", $str)) $platform = "Linux";
	elseif (preg_match("/BeOS/", $str)) $platform = "BeOS";
	else $platform = "Unknown";
	return $browser." ".$version." sur ".$platform;
}


function isValidateRapport($id_etab, $annee=0) {
	$id_quiz = $_SESSION['quiz'];
	$base = dbConnect();
	$id_etab = intval($id_etab);
	if (!$annee) {
		$annee = $_SESSION['annee'];
	}
	$request = sprintf("SELECT valide FROM assess WHERE etablissement='%d' AND annee='%d' AND quiz='%d' LIMIT 1", $id_etab, $annee, $id_quiz);
	$result = mysqli_query($base, $request);
	$row = mysqli_fetch_object($result);
	dbDisconnect($base);
	if ($row->valide == 1) {
		return true;
	} else {
		return false;
	}
}


function parserLatex($text) {
	$patternList = array(
		"\\" => "$\\backslash$", // a laisser en premier
		chr(13) => "\n\\\\", // Carriage Return (\r)
		chr(10) => "", // Line Feed (\n)
		"%" => "\\%",
		"_" => "\\_",
		"&" => "\\&",
		"°" => "\\textsuperscript{o}"
	);
	return(str_replace(array_keys($patternList), array_values($patternList), $text));
}


function disclaimer() {
	$txt = "\\bigskip\n";
	$txt .= "\\begin{center}\n\\begin{tikzpicture}\n";
	$txt .= "\\tikzstyle{every node} = [draw=myRed, rounded corners=4pt, fill=myRed!20, text width=300pt, inner sep=5pt]\n";
	$txt .= "\\node {Ce document, réservé à votre seul usage interne, est émis en application du contrat convenu entre nous. Il a été établi sur la base des informations que vous nous avez préalablement communiquées, par référence à votre contexte et en tenant compte de vos éléments d'analyse. L'émetteur du présent document apporte tout le soin possible à la préparation des informations et des conclusions qui y sont présentées, à partir de notre méthodologie et de nos expertises. La décision de mettre en oeuvre ou non ces conclusions, ainsi que les modalités de mise en oeuvre relèvent de la seule responsabilité du lecteur.};\n";
	$txt .= "\\end{tikzpicture}\n\\end{center}\n";
	return $txt;
}


function latexHead($annee=0) {
	global $rapportPicts;
	$regroup = isRegroupEtab();
	$id_etab = $_SESSION['id_etab'];
	$name_etab = getEtablissement($id_etab);
	$auditor = getAuditor();
	$rssi = getRSSI($id_etab);
	$director = getDirector($id_etab);
	if (!$annee) {
		$annee = $_SESSION['annee'];
	}

	$en_tete = "\\begin{filecontents*}{\jobname.xmpdata}\n";
	$en_tete .= "\\Title{EvalSMSI}\n\\Author{Michel Dubois}\n";
	$en_tete .= "\\Subject{Evaluation du SMSI}\n";
	$en_tete .= "\\Publisher{Michel Dubois}\n\\end{filecontents*}\n\n";
	$en_tete .= "\\documentclass[a4paper,11pt]{article}\n\n";
	$en_tete .= "\\input{header}\n\n";
	$pictures = sprintf("\\includegraphics[width=0.30\\textwidth]{%s}\\hfill\\includegraphics[width=0.30\\textwidth]{%s}\\\\\\bigskip\\bigskip\n", $rapportPicts[0], $rapportPicts[1]);
	$en_tete .= sprintf("\\title{%s Rapport d'évaluation\\\\de la\\\\maturité numérique\\\\ \\textcolor{myBlue}{%s}}\n\n", $pictures, $name_etab);
	$en_tete .= sprintf("\\author{%s -- \\textcolor{myBlue}{Auditeur}}\n\n", $auditor);
	$en_tete .= "\\date{\\today}\n\n";
	$en_tete .= "\\begin{document}\n\n";
	$en_tete .= "\\maketitle\n\n";
	$en_tete .= "\\bigskip\\bigskip\n\n";
	$en_tete .= sprintf("\\abstract{Ce rapport décrit le résultat de l'évaluation réalisée à \\textsl{%s} en %s. L'évaluation initiale a été contrôlée le \\today{} par %s. Cette évaluation repose sur un questionnaire établit conformément aux règles d'hygiène de l'ANSSI.}\n\n\\bigskip\\bigskip\n\n", $name_etab, $annee, $auditor);

	if (!empty($director) or (!empty($rssi))) {
		$en_tete .= sprintf("\\begin{itemize}\n");
	}
	if (!empty($director)) {
		$en_tete .= sprintf("\\item Directeur de l'établissement: \\textsl{%s}\n", $director);
	}
	if (!empty($rssi)) {
		$en_tete .= sprintf("\\item RSSI de l'établissement: \\textsl{%s}\n", $rssi);
	}
	if (!empty($director) or (!empty($rssi))) {
		$en_tete .= "\\end{itemize}\n\n\\bigskip\\bigskip\n\n";
	}

	if (!$regroup) {
		$en_tete .= "\\begin{center}\n";
		if (isValidateRapport($id_etab, $annee)) {
			$en_tete .= "\\Large{\\textcolor{myRed}{Rapport validé}}\n";
		} else {
			$en_tete .= "\\Large{\\textcolor{myRed}{Rapport NON validé !}}\n";
		}
		$en_tete .= "\\end{center}\n\n";
		if (isValidateRapport($id_etab, $annee)) {
			$en_tete .= "\\bigskip\\bigskip\n\n\\begin{flushright}\n\\textcolor{myRed}{Original signé}\n\\end{flushright}\n\n";
		}
	}
	$en_tete .= disclaimer();
	$en_tete .= "\\clearpage\n\n";
	$en_tete .= "\\begin{small}\n\\setlength{\\parskip}{0pt}\n";
	$en_tete .= "\\textcolor{myRed}{\\tableofcontents}\n";
	$en_tete .= "\\end{small}\n\n";
	$en_tete .= "\\clearpage\n\n";
	return $en_tete;
}


function latexFoot() {
	$foot = "\\end{document}\n\n";
	return $foot;
}


function printAssessment($assessment, $annee=0) {
	$quiz = getJsonFile();
	$text = "";
	if (!$annee) {
		$annee = $_SESSION['annee'];
	}
	$text .= sprintf("\\section{Résultats de l'audit pour l'année %s}\n\n", $annee);
	for ($d=0; $d<count($quiz); $d++) {
		$num_dom = $quiz[$d]['numero'];
		$subDom = $quiz[$d]['subdomains'];
		$text .= sprintf("\\subsection{%s}\n\n", $quiz[$d]['libelle']);
		for ($sd=0; $sd<count($subDom); $sd++) {
			$num_sub_dom = $subDom[$sd]['numero'];
			$questions = $subDom[$sd]['questions'];
			$text .= sprintf("\\subsubsection{%s}\n\n", $subDom[$sd]['libelle']);
			for ($q=0; $q<count($questions); $q++) {
				$num_question = $questions[$q]['numero'];
				$text .= sprintf("\\textbf{Question n\\textdegree %d.%d.%d} %s\n\n", $num_dom, $num_sub_dom, $num_question, $questions[$q]['libelle']);
				$questID='question'.$num_dom.'_'.$num_sub_dom.'_'.$num_question;
				$commID = 'comment'.$num_dom.'_'.$num_sub_dom.'_'.$num_question;
				$evalID = 'eval'.$num_dom.'_'.$num_sub_dom.'_'.$num_question;
				$text .= "\\begin{center}\n";
				$text .= "\\begin{tabular}{ | >{\\centering}m{0.05\\textwidth} >{\\centering}m{0.25\\textwidth} | m{0.50\\textwidth} | }\n\\hline\n";
				$text .= "\\multicolumn{2}{|c|}{\\textbf{\\'Evaluation de l'établissement}} & \\centering\\textbf{Commentaire} \\tabularnewline\n";
				if ($assessment[$questID]) {
					$note = $assessment[$questID];
					if (($note >= 0) || ($note < 2)) { $color = "gray"; }
					if (($note >= 2) || ($note < 4)) { $color = "red"; }
					if (($note >= 4) || ($note < 6)) { $color = "orange"; }
					if ($note >= 6) { $color = "green"; }
					$text .= sprintf("\\tikz{\\node [rectangle, fill=%s, inner sep=10pt] {};} & ", $color);
					$text .= sprintf("\\textcolor{myRed}{%s (%s/7)} & ", textItem(intval(round($note, 0, PHP_ROUND_HALF_UP))), $note);
				} else {
					$text .= sprintf(" & \\textcolor{myRed}{Néant} & ");
				}
				if ($assessment[$commID]<>"") {
					$commEtab = parserLatex(traiteStringFromBDD($assessment[$commID]));
					$text .= sprintf("\\makecell{%s}\\tabularnewline\n", $commEtab);
				} else {
					$text .= sprintf("Néant\\tabularnewline\n");
				}
				$text .= "\\hline\n";
				$text .= "\\multicolumn{3}{|>{\\centering}p{0.80\\textwidth}|}{\\textbf{Commentaire évaluateurs}}\\tabularnewline\n";
				if ($assessment[$evalID]<>"") {
					$commEvaluateur = parserLatex(traiteStringFromBDD($assessment[$evalID]));
					$text .= sprintf("\\multicolumn{3}{|>{\\raggedright}p{0.80\\textwidth}|}{\\textcolor{myBlue}{%s}}\\tabularnewline\n", $commEvaluateur);
				} else {
					$text .= sprintf("\\multicolumn{3}{|>{\\raggedright}p{0.80\\textwidth}|}{\\textcolor{myBlue}{Avis conforme}}\\tabularnewline\n");
				}
				$text .= "\\hline\n";
				if ($assessment[$questID] < 5) {
					$text .= "\\multicolumn{3}{|c|}{\\textbf{Recommandations}}\\tabularnewline\n";
					if ($questions[$q]['mesure'] <> "") {
						$text .= sprintf("\\multicolumn{3}{|>{\\raggedright}p{0.80\\textwidth}|}{%s}\\tabularnewline\n", $questions[$q]['mesure']);
					} else {
						$text .= sprintf("\\multicolumn{3}{|>{\\raggedright}p{0.80\\textwidth}|}{Pas de recommandations particulière.}\\tabularnewline\n");
					}
					$text .= "\\hline\n";
				}
				$text .= "\\end{tabular}\n\\end{center}\n\\bigskip\n\n";

			}
		}
	}
	return $text."\\clearpage\n\n";
}


function printGraphsAndNotes($annee) {
	$id_etab = $_SESSION['id_etab'];
	$id_quiz = $_SESSION['quiz'];
	$nbr_par = domainCount();
	$titles_par = getAllDomAbstract();
	$name_etab = getEtablissement($id_etab);
	$regroup = isRegroupEtab();
	if ($regroup) {
		$reponses = getRegroupAnswers();
		$cumulNotes[$_SESSION['annee']] = calculNotes($reponses[$_SESSION['annee']]);
		$notes = calculNotes($reponses[$_SESSION['annee']]);
	} else {
		$reponses = getAnswers();
		$base = dbConnect();
		$request = sprintf("SELECT comment_graph_par, comments FROM assess WHERE etablissement='%d' AND annee='%d' AND quiz='%d' LIMIT 1", $id_etab, $annee, $id_quiz);
		$result = mysqli_query($base, $request);
		$row = mysqli_fetch_object($result);
		dbDisconnect($base);
		foreach (array_keys($reponses) as $year){
			if (isValidateRapport($id_etab, $year) && $year<=$annee) {
				$cumulNotes[$year] = calculNotes($reponses[$year]);
			}
		}
		$notes = calculNotes($reponses[$annee]);
	}
	$noteSum = 0;
	$text = sprintf("\\section{Analyse de l'évaluation pour l'année %s}\n\n", $annee);
	$text .= "\\input{intro}\n\n";
	$text .= "\\subsection{Notes obtenues par l'établissement}\n\n";
	$text .= "\\begin{center}\n";
	$text .= "\\begin{tabular}{ | >{\\centering}m{0.20\\textwidth} | >{\\raggedright}m{0.30\\textwidth} @{\$\\quad\\rightarrow\\quad\$} >{\\raggedright}m{0.10\\textwidth} | >{\\centering}m{0.15\\textwidth} | }\n";
	$text .= "\\hline\n";
	$text .= "\\multicolumn{4}{| c |}{Notes finales de l'établissement}\\tabularnewline\n\\hline\n";
	$text .= "\\'Etablissement & \\multicolumn{2}{ c |}{\\centering{Détail des notes}} & Note finale \\tabularnewline\n";
	$text .= "\\hline\n";
	$text .= sprintf("\\multirow{%d}{0.20\\textwidth}{%s} & \\multicolumn{2}{ c |}{} & \\tabularnewline\n", $nbr_par+2, $name_etab);
	for ($i=0; $i<sizeof($titles_par); $i++) {
		$note = 20 * $notes[$i+1] / 7;
		$noteSum = $noteSum + $note;
		if ($note <= 10) {
			$text .= sprintf("& %s & \\textcolor{myRed}{\$%01.2f / 20\$} & \\tabularnewline\n", $titles_par[$i], $note);
		} else {
			$text .= sprintf("& %s & \$%01.2f / 20\$ & \\tabularnewline\n", $titles_par[$i], $note);
		}
	}
	$noteFinale = 20 * $noteSum / (sizeof($titles_par)*20);
	$text .= sprintf(" & \\multicolumn{2}{ c |}{} & \multirow{-%d}{*}{\$%01.2f / 20\$} \\tabularnewline\n", $nbr_par+2, $noteFinale);
	$text .= "\\hline\n";
	$text .= "\\end{tabular}\n";
	$text .= "\\end{center}\n\n";
	$text .= "\\clearpage\n\n";

	$text .= "\\subsection{Graphes de synthèses de l'établissement}\n\n";
	$text .= defineGraphVariables(count($titles_par));
	$text .= simpleYearGraph($annee, $notes, $titles_par);
	if (count($cumulNotes)>1) {
		$text .= "\\bigskip\n\n\\bigskip\n\n";
		$text .= cumulatedGraph($cumulNotes, $annee, $titles_par);
	}
	$text .= "\\clearpage\n\n";

	$text .= "\\subsection{Commentaires et conclusion}\n\n";
	$text .= "\\subsubsection{Commentaires de l'établissement}\n\n";
	if ($regroup) {
		$commEtab = parserLatex(traiteStringFromBDD($reponses['final_etab_comment']));
	} else {
		$commEtab = parserLatex(traiteStringFromBDD($row->comments));
	}
	$text .= sprintf("\\par\n%s\n\\par", $commEtab);
	$text .= "\\subsubsection{Conclusion des évaluateurs}\n\n";
	if ($regroup) {
		$commEvals = parserLatex(traiteStringFromBDD($reponses['auditor_comment']));
	} else {
		$commEvals = parserLatex(traiteStringFromBDD($row->comment_graph_par));
	}
	$text .= sprintf("\\par\n%s\n\\par", $commEvals);
	return $text."\\clearpage\n\n";
}


function printAnnexes() {
	$text = "\\appendix\n";
	$text .= "\\input{methode}\n\n";
	return $text;
}


function purgeRapportsFiles() {
	global $cheminRAP;
	if (isset($_SESSION['id_etab'])) {
		$abrege_etab = getEtablissement($_SESSION['id_etab'], $abrege=1);
		$rapportPDF = sprintf("%s.pdf", $abrege_etab);
		$rapportXLSX = sprintf("plan_actions_%d.xlsx", $_SESSION['id_etab']);
		$evalDOCX = sprintf("evaluation_%s.docx", $abrege_etab);
		$evalXLSX = sprintf("evaluation_%s.xlsx", $abrege_etab);
		$currentDirectory = getcwd();
		chdir($cheminRAP);
		@unlink($rapportPDF);
		@unlink($rapportXLSX);
		@unlink($evalDOCX);
		@unlink($evalXLSX);
		@unlink("referentiel.docx");
		@unlink("referentiel.pdf");
		chdir($currentDirectory);
	}
}


function makeRapport($abrege_etab, $text, $annee) {
	global $cheminRAP, $cheminDATA;
	$script = dirname($_SERVER['PHP_SELF']);
	$file = sprintf("%s%s.tex", $cheminDATA, $abrege_etab);
	$pdffile = sprintf("%s%s.pdf", $cheminDATA, $abrege_etab);
	$newpdffile = sprintf("%s%s.pdf", $cheminRAP, $abrege_etab);
	$pdfLink = sprintf("%s/rapports/%s.pdf", dirname($_SERVER['REQUEST_URI']), $abrege_etab);
	if ($handle = fopen($file, "w")) {
		fwrite($handle, $text);
		fclose($handle);
		$rem_courant = getcwd();
		chdir($cheminDATA);
		exec("make");
		exec("make clean");
		unlink($file);
		rename($pdffile, $newpdffile);
		chdir($rem_courant);
		$msg = sprintf("Télécharger le rapport %d", $annee);
		linkMsg($pdfLink, $msg, "print_rapport.png", 'menu');
	} else {
		linkMsg("#", "Erreur de création du rapport", "alert.png");
	}
}


function generateRapport($annee=0) {
	$script = $_SESSION['curr_script'];
	$id_etab = $_SESSION['id_etab'];
	$id_quiz = $_SESSION['quiz'];
	$printByEtablissement = true;
	if (!$annee) {
		$annee = $_SESSION['annee'];
		$printByEtablissement = false;
	}
	$abrege_etab = getEtablissement($id_etab, $abrege=1);
	$base = dbConnect();
	$request = sprintf("SELECT * FROM assess WHERE etablissement='%d' AND annee='%d' AND quiz='%d' LIMIT 1", $id_etab, $annee, $id_quiz);
	$result = mysqli_query($base, $request);
	dbDisconnect($base);
	// Il existe une évaluation pour cet établissement
	if (mysqli_num_rows($result)) {
		$row = mysqli_fetch_object($result);
		// L'évaluation n'est pas vide
		if (!empty($row->reponses)) {
			$assessment = unserialize($row->reponses);
			$reponses = array();
			foreach($assessment as $quest => $rep) {
				if (substr($quest, 0, 8) == 'question') {
					$reponses[$annee][substr($quest, 8, 14)]=$rep;
				}
			}
			// L'évaluation est complète
			if (isAssessComplete($reponses[$annee])) {
				if ($row->valide) {
					$head = latexHead($annee);
					if ($printByEtablissement) {
						$head .= "\n\n\\begin{center}\n\n{\\Large{\\textcolor{myRed}{Rapport imprimé par l'établissement le \\today.}}}\n\n\\end{center}\n\n\\clearpage\n\n";
					}
					$first_section = printGraphsAndNotes($annee);
					$second_section = printAssessment($assessment, $annee);
					$annexes = printAnnexes();
					$foot = latexFoot();
					$rapport = $head."\n".$first_section."\n".$second_section."\n".$annexes."\n".$foot;
					makeRapport($abrege_etab, $rapport, $annee);
				} else {
					linkMsg($script, "L'évaluation de cet établissement n'a pas revue par les auditeurs.", "alert.png");
				}
			} else {
				linkMsg($script, "L'évaluation de cet établissement est incomplète.", "alert.png");
			}
		} else {
			linkMsg($script, "L'évaluation de cet établissement est vide.", "alert.png");
		}
	} else {
		linkMsg($script, "Il n'y a pas d'évaluation pour cet établissement.", "alert.png");
	}
}


function createWordDoc() {
	global $appli_titre_short, $appli_titre;
	$phpWord = new \PhpOffice\PhpWord\PhpWord();
	$phpWord->getCompatibility()->setOoxmlVersion(15);
	$phpWord->getSettings()->setThemeFontLang(new Language(Language::FR_FR));
	$phpWord->getSettings()->setDecimalSymbol(',');
	$phpWord->setDefaultFontName('Calibri');
	$phpWord->setDefaultFontSize(11);
	$phpWord->addTitleStyle(1, array('size'=>16, 'color'=>'#406e96', 'smallCaps'=>True), array('spaceBefore'=>600, 'spaceAfter'=>100));
	$phpWord->addTitleStyle(2, array('size'=>14, 'color'=>'#5b9bd5'), array('spaceBefore'=>400, 'spaceAfter'=>100));
	$phpWord->addTitleStyle(3, array('size'=>12, 'color'=>'#222222', 'bold'=>True), array('spaceBefore'=>200, 'spaceAfter'=>100));
	$properties = $phpWord->getDocInfo();
	$properties->setCreator($appli_titre_short);
	$properties->setTitle($appli_titre);
	$properties->setDescription($appli_titre);
	$properties->setCategory('Cybersecurity');
	$properties->setLastModifiedBy($appli_titre_short);
	return $phpWord;
}


function exportEval() {
	global $appli_titre, $appli_titre_short;
	$name_quiz = getQuizName();
	# $dir = dirname($_SERVER['PHP_SELF']);
	$dir = dirname($_SERVER['HTTP_HOST']);
	$id_etab = $_SESSION['id_etab'];
	$id_quiz = $_SESSION['quiz'];
	$annee = $_SESSION['annee'];
	$script = $_SESSION['curr_script'];
	$abrege_etab = getEtablissement(intval($id_etab), $abrege=1);
	$fileDoc = sprintf("rapports/evaluation_%s.docx", $abrege_etab);
	$fileXlsx = sprintf("rapports/evaluation_%s.xlsx", $abrege_etab);

	$phpWord = createWordDoc();
	$myTableStyleName = 'myTable';
	$myTableStyle = array('borderColor'=>'444444', 'borderSize'=>8, 'cellMargin'=>50, 'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER, 'width'=>10000, 'unit'=>'pct');
	$myTableRowStyle = array('cantSplit'=>true);
	$myTableTitleCellStyle = array('bgColor'=>'eeeeee', 'valign'=>'center');
	$myTableCellStyle = array('valign'=>'center');
	$myTableSpanTitleCellStyle = array('bgColor'=>'eeeeee', 'valign'=>'center', 'gridSpan'=>2);
	$myTableSpanCellStyle = array('valign'=>'center', 'gridSpan'=>2);
	$phpWord->addTableStyle($myTableStyleName, $myTableStyle);

	$sectionStyle = array(
		'orientation' => 'portrait',
		'marginTop' => 800,
		'marginLeft' => 800,
		'marginRight' => 800,
		'marginBottom' => 800,
		'headerHeight' => 800,
		'footerHeight' => 800,
	);
	$section = $phpWord->addSection($sectionStyle);
	$header = $section->addHeader();
	$header->addPreserveText($appli_titre, null, array('spaceAfter' => 400, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER));
	$footer = $section->addFooter();
	$footer->addPreserveText('Page {PAGE}/{NUMPAGES}', null, array('spaceBefore' => 400, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER));

	$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
	$sheet = $spreadsheet->getActiveSheet();
	$sheet->setTitle($appli_titre_short);
	$sheet->getParent()->getDefaultStyle()->getFont()->setName('Calibri');
	$sheet->getParent()->getDefaultStyle()->getFont()->setSize(11);
	$sheet->getParent()->getDefaultStyle()->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
	$sheet->getParent()->getDefaultStyle()->getAlignment()->setWrapText(true);
	$sheet->getSheetView()->setZoomScale(100);
	$sheet->getColumnDimension('A')->setWidth(40);
	$sheet->getColumnDimension('B')->setWidth(30);
	$sheet->getColumnDimension('C')->setWidth(45);
	$sheet->getColumnDimension('D')->setWidth(45);
	$sheet->getColumnDimension('E')->setWidth(8);
	$sheet->getColumnDimension('F')->setWidth(40);

	$eval = array();
	$eval[] = ['Thème', 'Domaine', 'Règle', 'Mesure', 'Note', 'Commentaire'];
	$name_etab = getEtablissement($id_etab);
	$base = dbConnect();
	$req_assessment = sprintf("SELECT * FROM assess WHERE etablissement='%d' AND annee='%d' AND quiz='%d' LIMIT 1", $id_etab, $annee, $id_quiz);
	$res_assessment = mysqli_query($base, $req_assessment);
	dbDisconnect($base);

	if (mysqli_num_rows($res_assessment)) {
		$quiz = getJsonFile();
		$row_assessment=mysqli_fetch_object($res_assessment);
		if (!empty($row_assessment->reponses)) {
			$assessment = unserialize($row_assessment->reponses);
			$final_c = $row_assessment->comments;
			$reponses = array();
			foreach($assessment as $quest => $rep) {
				if (substr($quest, 0, 8) == 'question') {
					$reponses[$annee][substr($quest, 8, 14)]=$rep;
				}
			}
			$section->addText($name_quiz, array('bold'=>true, 'size'=>20, 'smallCaps'=>true), array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter'=>500));
			if (intval($_SESSION['role']) == 2) {
				$msg = "Copie de travail de l'auditeur";
			} else {
				$msg = "Copie de travail de l'établissement";
			}
			$section->addText($msg, array('bold'=>true, 'size'=>16, 'smallCaps'=>true, 'color'=>'9e1e1e'), array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter'=>500));
			$section->addText($name_etab . " - " . $annee, array('bold'=>true, 'size'=>18, 'smallCaps'=>true, 'color'=>'444444'), array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter'=>500));

			for ($d=0; $d<count($quiz); $d++) {
				$num_dom = $quiz[$d]['numero'];
				$subDom = $quiz[$d]['subdomains'];
				$section->addTitle($num_dom." ".$quiz[$d]['libelle'], 1);
				for ($sd=0; $sd<count($subDom); $sd++) {
					$num_sub_dom = $subDom[$sd]['numero'];
					$section->addTitle($num_dom.".".$num_sub_dom ." ".$subDom[$sd]['libelle'], 2);
					$questions = $subDom[$sd]['questions'];
					for ($q=0; $q<count($questions); $q++) {
						$num_question = $questions[$q]['numero'];
						$questID = $num_dom.".".$num_sub_dom.".".$num_question;
						$textID = 'comment'.$num_dom."_".$num_sub_dom."_".$num_question;
						$noteID = 'question'.$num_dom."_".$num_sub_dom."_".$num_question;
						$rule = $questions[$q]['libelle'];
						$measure = $questions[$q]['mesure'];
						$comment = traiteStringFromBDD($assessment[$textID]);
						if (empty($comment)) { $comment = "Pas de commentaire"; }
						if (empty($measure)) { $measure = "Pas de recommandation particulière"; }

						$table = $section->addTable($myTableStyleName);
						$table->addRow(300, $myTableRowStyle);
						$table->addCell(7000, $myTableTitleCellStyle)->addText('Règle n°'.$questID);
						$table->addCell(3000, $myTableTitleCellStyle)->addText('Note');
						$table->addRow(500, $myTableRowStyle);
						$table->addCell(7000, $myTableCellStyle)->addText($rule);
						$table->addCell(3000, $myTableCellStyle)->addText($assessment[$noteID].' - '.textItem($assessment[$noteID]));
						$table->addRow(300, $myTableRowStyle);
						$table->addCell(10000, $myTableSpanTitleCellStyle)->addText('Commentaire');
						$table->addRow(500, $myTableRowStyle);
						$table->addCell(10000, $myTableSpanCellStyle)->addText($comment);
						$section->addTextBreak();

						$row = [$quiz[$d]['libelle'], $subDom[$sd]['libelle'], $rule, $measure, $assessment[$noteID], $comment];
						$eval[] = $row;
					}
				}
			}
			$sheet->fromArray($eval, NULL, 'A1');

			printf("<div class='row'>");
			printf("<div class='column left'>");
			$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
			$objWriter->save($fileDoc);
			$msg = "Télécharger l'évaluation (Word)";
			linkMsg($dir."/".$fileDoc, $msg, "docx.png", 'menu');
			printf("</div><div class='column right'>");
			$objWriter = new Xlsx($spreadsheet);
			$objWriter->save($fileXlsx);
			$msg = "Télécharger l'évaluation (Excel)";
			linkMsg($dir."/".$fileXlsx, $msg, "xlsx.png", 'menu');
			printf("</div></div>");
		} else {
			$msg = sprintf("L'évaluation pour l'année %d est vide", $annee);
			linkMsg($script, $msg, "alert.png");
		}
	} else {
		$msg = sprintf("Il n'y a pas d'évaluation pour l'année %d", $annee);
		linkMsg($script, $msg, "alert.png");
		footPage();
	}
}


function generateExcellRapport($annee) {
	$id_etab = $_SESSION['id_etab'];
	$id_quiz = $_SESSION['quiz'];
	$fileXlsx = sprintf("rapports/plan_actions_%d.xlsx", $id_etab);
	$quiz = getJsonFile();

	$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
	$sheet = $spreadsheet->getActiveSheet();
	$sheet->setTitle('EvalSMSI');
	$sheet->getParent()->getDefaultStyle()->getFont()->setName('Calibri');
	$sheet->getParent()->getDefaultStyle()->getFont()->setSize(11);
	$sheet->getParent()->getDefaultStyle()->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
	$sheet->getParent()->getDefaultStyle()->getAlignment()->setWrapText(true);
	$sheet->getSheetView()->setZoomScale(100);
	$sheet->getColumnDimension('A')->setWidth(40);
	$sheet->getColumnDimension('B')->setWidth(30);
	$sheet->getColumnDimension('C')->setWidth(45);
	$sheet->getColumnDimension('D')->setWidth(8);
	$sheet->getColumnDimension('E')->setWidth(40);
	$sheet->getColumnDimension('F')->setWidth(40);
	$sheet->getColumnDimension('G')->setWidth(45);

	$eval = array();
	$eval[] = ['Thème', 'Domaine', 'Règle', 'Note', 'Commentaire', 'Commentaire évaluateur', 'Mesure'];
	$base = dbConnect();
	$request = sprintf("SELECT * FROM assess WHERE etablissement='%d' AND annee='%d' AND quiz='%d' LIMIT 1", $id_etab, $annee, $id_quiz);
	$result = mysqli_query($base, $request);
	dbDisconnect($base);

	// Il existe une évaluation pour cet établissement
	if ($result->num_rows) {
		$row = mysqli_fetch_object($result);
		// L'évaluation n'est pas vide
		if (!empty($row->reponses)) {
			$assessment = unserialize($row->reponses);
			$reponses = array();
			foreach($assessment as $quest => $rep) {
				if (substr($quest, 0, 8) == 'question') {
					$reponses[$annee][substr($quest, 8, 14)]=$rep;
				}
			}
			// L'évaluation est complète
			if (isAssessComplete($reponses[$annee])) {
				if ($row->valide) {
					for ($d=0; $d<count($quiz); $d++) {
						$num_dom = $quiz[$d]['numero'];
						$subDom = $quiz[$d]['subdomains'];
						for ($sd=0; $sd<count($subDom); $sd++) {
							$num_sub_dom = $subDom[$sd]['numero'];
							$questions = $subDom[$sd]['questions'];
							for ($q=0; $q<count($questions); $q++) {
								$num_question = $questions[$q]['numero'];
								$questID = $num_dom.'.'.$num_sub_dom.'.'.$num_question;
								$commID = 'comment'.$num_dom.'_'.$num_sub_dom.'_'.$num_question;
								$noteID = 'question'.$num_dom.'_'.$num_sub_dom.'_'.$num_question;
								$evalID = 'eval'.$num_dom.'_'.$num_sub_dom.'_'.$num_question;
								$par = $quiz[$d]['libelle'];
								$subpar = $subDom[$sd]['libelle'];
								$rule = $questions[$q]['libelle'];
								$measure = $questions[$q]['mesure'];
								$note = intval($assessment[$noteID]);
								$comment = traiteStringFromBDD($assessment[$commID]);
								$comeval = traiteStringFromBDD($assessment[$evalID]);
								if (empty($comment)) { $comment = "Pas de commentaire"; }
								if (empty($comeval)) { $comeval = "Avis conforme"; }
								if (empty($measure)) { $measure = "Pas de recommandations particulière"; }
								$row = [$par, $subpar, $rule, $note, $comment, $comeval, $measure];
								$eval[] = $row;
							}
						}
					}
					$sheet->fromArray($eval, NULL, 'A1');
				} else {
					linkMsg("etab.php", "L'évaluation de cet établissement n'a pas revue par les auditeurs.", "alert.png");
				}
			} else {
				linkMsg("etab.php", "L'évaluation de cet établissement est incomplète.", "alert.png");
			}
		} else {
			linkMsg("etab.php", "L'évaluation de cet établissement est vide.", "alert.png");
		}
	} else {
		linkMsg("etab.php", "Il n'y a pas d'évaluation pour cet établissement.", "alert.png");
	}

	foreach ($sheet->getRowIterator() as $row) {
		$cellIterator = $row->getCellIterator();
		$cellIterator->setIterateOnlyExistingCells(FALSE);
		foreach ($cellIterator as $key => $cell) {
			if ($key == 'D') {
				$note = $cell->getValue();
				if (is_numeric($note)) {
					if ($note == 1) { $color = "ffbebebe"; }
					if (($note == 2) || ($note == 3)) { $color = "ffff0000"; }
					if (($note == 4) || ($note == 5)) { $color = "ffffa500"; }
					if (($note == 6) || ($note == 7)) { $color = "ff00ff00"; }
					$cell->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
					$cell->getStyle()->getFill()->getStartColor()->setARGB($color);
				}
			}
		}
	}
	$objWriter = new Xlsx($spreadsheet);
	$objWriter->save($fileXlsx);
	return(dirname($_SERVER['PHP_SELF'])."/".$fileXlsx);
}


function exportRules() {
	$name_quiz = getQuizName();
	$quiz = getJsonFile();
	# $script = dirname($_SERVER['PHP_SELF']); # <- n'affiche pas le domaine sur l'URL ...
	$script = dirname($_SERVER['HTTP_HOST']);
	$fileDoc = "rapports/referentiel.docx";
	$filePdf = "rapports/referentiel.pdf";

	$phpWord = createWordDoc();

	$protection = $phpWord->getSettings()->getDocumentProtection();
	$protection->setEditing(DocProtect::READ_ONLY);
	$protection->setPassword(base64_encode(random_bytes(16)));

	$sectionStyle = array(
		'orientation' => 'portrait',
		'marginTop' => 800,
		'marginLeft' => 800,
		'marginRight' => 800,
		'marginBottom' => 800,
		'headerHeight' => 800,
		'footerHeight' => 800,
	);
	$section = $phpWord->addSection($sectionStyle);

	$footer = $section->addFooter();
	$footer->addPreserveText('Page {PAGE}/{NUMPAGES}', null, array('spaceBefore' => 400, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER));

	$section->addText('Référentiel', array('size'=>20), array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceBefore'=>1000, 'spaceAfter'=>100));
	$section->addText($name_quiz, array('size'=>20, 'color'=>'#9e1e1e'), array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter'=>400));
	$section->addText('EvalSMSI', array('size'=>16), array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter'=>200));
	$section->addText(strftime("%d %B %Y", time()), array('size'=>16), array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter'=>500));
	$section->addPageBreak();

	for ($d=0; $d<count($quiz); $d++) {
		$num_dom = $quiz[$d]['numero'];
		$subDom = $quiz[$d]['subdomains'];
		$msg = sprintf("%s\t%s", $num_dom, $quiz[$d]['libelle']);
		if (intval($num_dom)>1) { $section->addPageBreak(); }
		$section->addTitle($msg, 1);
		for ($sd=0; $sd<count($subDom); $sd++) {
			$num_sub_dom = $subDom[$sd]['numero'];
			$questions = $subDom[$sd]['questions'];
			$msg = sprintf("%s.%s\t%s", $num_dom, $num_sub_dom, $subDom[$sd]['libelle']);
			$section->addTitle($msg, 2);
			for ($q=0; $q<count($questions); $q++) {
				$num_question = $questions[$q]['numero'];
				$msg = sprintf("Règle %s.%s.%s ", $num_dom, $num_sub_dom, $num_question);
				$section->addTitle($msg, 3);
				$section->addText(str_replace(chr(10), '<w:br/>', $questions[$q]['libelle']));
				$msg = sprintf("Mesure %s.%s.%s ", $num_dom, $num_sub_dom, $num_question);
				$section->addTitle($msg, 3);
				$section->addText(str_replace(chr(10), '<w:br/>', $questions[$q]['mesure']));
				$line = array('weight'=>1, 'width'=>\PhpOffice\PhpWord\Shared\Converter::cmToPixel(8), 'height'=>0, 'color'=>'red');
				$section->addLine($line);

			}
		}
	}

	printf("<div class='row'>");
	printf("<div class='column left'>");
	$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
	$objWriter->save($fileDoc);
	$msg = "Télécharger le référentiel (Word)";
	linkMsg($script."/".$fileDoc, $msg, "docx.png", 'menu');
	printf("</div><div class='column right'>");
	makeReferentiel();
	$msg = "Télécharger le référentiel (Adobe PDF)";
	linkMsg($script."/".$filePdf, $msg, "pdf.png", 'menu');
	printf("</div></div>");
}


function generateReferentiel() {
	$name_quiz = getQuizName();
	$quiz = getJsonFile();
	$text = "\\begin{filecontents*}{\jobname.xmpdata}\n";
	$text .= "\\Title{EvalSMSI}\n\\Author{Michel Dubois}\n";
	$text .= "\\Subject{Evaluation du SMSI}\n";
	$text .= "\\Publisher{Michel Dubois}\n\\end{filecontents*}\n\n";
	$text .= "\\documentclass[a4paper,11pt]{article}\n\n";
	$text .= "\\input{header}\n\n";
	$text .= sprintf("\\title{Référentiel\\\\ \\textcolor{myRed}{%s}}\n\n", $name_quiz);
	$text .= sprintf("\\author{EvalSMSI}\n\n");
	$text .= "\\date{\\today}\n\n";
	$text .= "\\begin{document}\n\n";
	$text .= "\\maketitle\n\n";
	$text .= "\\clearpage\n\n";
	$text .= "\\begin{small}\n\\setlength{\\parskip}{0pt}\n";
	$text .= "\\textcolor{myRed}{\\tableofcontents}\n";
	$text .= "\\end{small}\n\n";
	$text .= "\\clearpage\n\n";
	for ($d=0; $d<count($quiz); $d++) {
		$num_dom = $quiz[$d]['numero'];
		if ($num_dom > 1) { $text .= "\\clearpage\n\n"; }
		$subDom = $quiz[$d]['subdomains'];
		$text .= sprintf("\\section{%s}\n\n", $quiz[$d]['libelle']);
		for ($sd=0; $sd<count($subDom); $sd++) {
			$num_sub_dom = $subDom[$sd]['numero'];
			$questions = $subDom[$sd]['questions'];
			$text .= sprintf("\\subsection{%s}\n\n", $subDom[$sd]['libelle']);
			$text .= sprintf("%s\n\n", $subDom[$sd]['comment']);
			for ($q=0; $q<count($questions); $q++) {
				$num_question = $questions[$q]['numero'];
				$text .= sprintf("\\textbf{Règle %d.%d.%d}\n\n", $num_dom, $num_sub_dom, $num_question);
				$text .= sprintf("%s\n\n", str_replace(chr(10), "\n\n", $questions[$q]['libelle']));
				$text .= sprintf("\\textbf{Mesure %d.%d.%d}\n\n", $num_dom, $num_sub_dom, $num_question);
				$text .= sprintf("%s\n\n", str_replace(chr(10), "\n\n", $questions[$q]['mesure']));
				$text .= "\\textcolor{myRed}{\\rule{\\linewidth}{0.4pt}}\n\n";
			}
		}
	}
	$text .= "\\end{document}\n\n";
	return $text;
}


function makeReferentiel() {
	global $cheminRAP, $cheminDATA;
	$text = generateReferentiel();
	$fileName = "referentiel";
	$file = sprintf("%s%s.tex", $cheminDATA, $fileName);
	$pdffile = sprintf("%s%s.pdf", $cheminDATA, $fileName);
	$newpdffile = sprintf("%s%s.pdf", $cheminRAP, $fileName);
	$pdfLink = sprintf("%s/rapports/%s.pdf", dirname($_SERVER['REQUEST_URI']), $fileName);
	if ($handle = fopen($file, "w")) {
		fwrite($handle, $text);
		fclose($handle);
		$rem_courant = getcwd();
		chdir($cheminDATA);
		exec("make");
		exec("make clean");
		unlink($file);
		rename($pdffile, $newpdffile);
		chdir($rem_courant);
	}
}


?>
