<?php
/*
	Name of addon.
*/
$mypage = 'api_json';


/*
AddOn-ID, still a *dummy*
will be adjusted before publish to redaxo.org
*/
$REX['ADDON']['rxid'][$mypage] = '2657';


/*
Die Page-Variable regelt die URL, mit der auf das AddOn zugegriffen wird.
*/
// $REX['ADDON']['page'][$mypage] = $mypage;


/*
AddOn-Name, wie er im Menü angezeigt werden soll.
Ist natürlich nur dann nötig, wenn das AdOn überhaupt einen Menüpunkt erzeugen soll.
*/
// $REX['ADDON']['name'][$mypage] = 'JSON Api';


/*
Benutzer-Recht: Wenn man im AddOn Rechte verwaltet, muss diese Variable gesetzt werden.
Man muss dann einem Redakteur explizit das Recht zuweisen,
damit er im Backend auf das AddOn im Backend zugreifen kann.
*/
// $REX['ADDON']['perm'][$mypage] = 'apijson[]';
// $REX['PERM'][] = 'adressen[]';


/*
Hier kann man Informationen zur Version und zum Autor hinterlegen.
*/
$REX['ADDON']['version'][$mypage] = "0.1.1";
$REX['ADDON']['author'][$mypage] = "Thomas Kühne";


/*
Hier wird der Tabellen-Prefix verwaltet.
*/
// $REX['ADDON']['dbpref'][$mypage]=$REX['TABLE_PREFIX'].$REX['ADDON']['rxid'][$mypage].'_';


/*
Das Anlegen einer Sprachintanz ist nötig, wenn man das AddOn Backend mehrsprachig verwalten will.
Dieser Schlüssel muss natürlich eindeutig sein.
*/
// $I18N_adressen = new i18n($REX['LANG'], $REX['INCLUDE_PATH'].'/addons/'.$mypage.'/lang/');

// - uses OUTPUT_FILTER because user will expect this (esp. for project wide replacemets)
function kwd_startJsonApi_output($params) {
	$response = '';
	$kwdApi = new kwd_jsonapi_rex4(); // ??? add parameter from config as server string *index*
	$response = $kwdApi->buildResponse(); // returns immediately when no valid API request found
	if ($response) {
		$kwdApi->sendHeaders(); // ! this means you can alter/prevent response body; but not the HTTP headers
		return $response;
	}
	return $params['subject'];
}

// - could be switched "on" by configuration
function kwd_startJsonApi_fast() {
	$kwdApi = new kwd_jsonapi_rex4(); // ??? add parameter from config as server string *index*

	// returns false, if repsonse empty, true when something in it
	if ($kwdApi->send($kwdApi->buildResponse())) // ! send contains ob_end and echo
		exit();
}

/*
Falls benötigt, werden AddOn-spezifische Klassen und Funktionen geladen.
Mit $REX['REDAXO'] kann man zwischen Frontend und Backend unterscheiden.
*/
if ($REX['REDAXO']) {
	// Gilt nur für das Backend
} else {
	// Gilt nur für das Frontend
	// bitte require ... auskommentieren.
	require $REX['INCLUDE_PATH'].'/addons/'.$mypage.'/classes/kwd_jsonapi.php';
	require $REX['INCLUDE_PATH'].'/addons/'.$mypage.'/classes/kwd_jsonapi_rex4.php';

	// if (!$REX['REDAXO']) rex_register_extension('OUTPUT_FILTER', 'kwd_startJsonApi_output');
	if (!$REX['REDAXO']) rex_register_extension('ADDONS_INCLUDED', 'kwd_startJsonApi_fast');

	// faster but can not use OUTPUT_FILTER:
	// - all other must be included because we request article contents which will need e.g. a class from the "extensions" addon
	// rex_register_extension('ADDONS_INCLUDED', 'kwd_startJsonApi_fast');
}
?>
