<?php
/*
* Diese Datei wird bei der Installation des AddOns ausgeführt.
*
* Eine Fehlermeldung kann man ausgeben mit:
*
* $REX['ADDON']['installmsg']['adressen'] = 'Hier hat was nicht funktionert ...';
*/

$myAddonName = 'api_json'; // ??? how to provide this over all files of addon?
$REX['ADDON']['install'][$myAddonName] = 1;
// 1 = wenn erfolgreich installiert, 0 = wenn nicht installiert
?>
