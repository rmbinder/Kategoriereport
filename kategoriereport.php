<?php
/******************************************************************************
 * Kategoriereport
 *
 * Version 2.0.1
 *
 * Dieses Plugin erzeugt eine Auflistung aller Rollenzugehörigkeiten eines Mitglieds.
 * 
 *   
 * Copyright    : (c) 2004 - 2013 The Admidio Team
 * Homepage     : http://www.admidio.org
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 * Author		: rmb
 *
 * Version		  : 2.0.1
 * Datum        : 02.11.2015
 * Änderung     : - Fehler (verursacht durch die Methode addHeadline) behoben
 * 				        - Fehler in Datei de_sie.xml behoben (</text> fehlte in einer Zeile) 
 * 
 * Version		  : 2.0.0
 * Datum        : 26.05.2015
 * Änderung     : - Anpassung an Admidio 3.0
 *                - Deinstallationsroutine erstellt
 *                - Verfahren zum Einbinden des Plugins (include) geändert 
 *                - Erzeugen mehrerer Konfigurationen möglich
 *                - Kennzeichnung inaktiver und unsichtbarer Rollen
 *                - Menübezeichnungen angepasst (gleichlautend mit anderen Plugins) 
 *                - Nur Intern: Verwaltung der Konfigurationsdaten geändert 
 * 
 * Version		  : 1.3.2
 * Datum        : 17.10.2014
 * Änderung     : Für den Export sind diverse Parameter jetzt im Setup einstellbar
 * 
 * Version		: 1.3.1
 * Datum        : 23.09.2014
 * Änderung     : bei Namensgleichheit von Profilfeldern wird die Kategorie in Klammern angehängt 
 * 
 * Version		: 1.3.0
 * Datum        : 01.04.2013
 * Änderung     : - Anpassung an Admidio 2.4
 * 				  - Konfigurationsdaten werden nicht mehr in einer config.ini gespeichert,
 * 				    sondern in der Admidio Datenbank abgelegt
 *   			  - Das Menü Einstellungen kann separat über Berechtigungen angezeigt werden
 *   			  - Die Leiter von Rollen können ausgegeben werden
 * 				  - E-Mail-Adressen werden mit einem Link versehen (DieterB)
 * 				  - Englische Sprachdatei erstellt
 * 				  - Die Default-Einstellung der Pluginfreigabe wurde erweitert um die Rolle Mitglied     
 *
 * Version		: 1.2.1 
 * Datum        : 18.12.2012
 * Änderung     : - Setuproutine erstellt (Vorschlag im Forum durch guenter47: "Kategoriereport mit weniger Userspalten")
 * 				  - Die angezeigten Spalten sind frei wählbar (Vorschlag im Forum durch guenter47: "Kategoriereport mit weniger Userspalten")
 * 				  - Kleinere Änderungen eingepflegt
 * 				  - Funktion checkStringLenth von guenter47 eingearbeitet
 * 				  - In der Anzeige einen Link zum Userprofil eingearbeitet
 * 
 * Version		: 1.2.0 
 * Datum        : 21.02.2012
 * Änderung     : - das Plugin ist jetzt Admidio 2.3 kompatibel
 * 
 * Version		: 1.1.2
 * Datum        : 08.12.2011
 * Änderung     : - das Standard-Datenbankpräfix (adm_) ist nicht mehr fest kodiert
 *
 * Version		: 1.1.1  
 * Datum        : 21.11.2011                                
 * Änderung     : - Die Einschränkung in einer Abfrage (kategoriereport_show.php,
 *                  Zeile 412) auf nur Mitglieder der Rolle "Mitglied" wurde aufgehoben.   
 *                - Die Berechtigung das Plugin aufzurufen, wurde um 
 *                  Rollenmitgliedschaften erweitert.
 *                  
 * Version		: 1.1.0  
 * Datum        : 26.10.2011
 * Änderung     : Der gesamte Plugin wurde überarbeitet und an die in Admidio
 *                geführten Listen angepasst.
 *                Die erzeugte CSV-Datei wird nicht mehr auf dem Server 
 *                zwischengespeichert, sie wird in der Listenansicht zum
 *                Download angeboten. Das zusätzliche Plugin downloadfile.php
 *                wird nicht mehr benötigt. 
 *                     
 * Version		: 1.0.0
 * Datum        : 11.07.2011   
 *  
 *****************************************************************************/

//$gNaviagation ist zwar definiert, aber in diesem Script in bestimmten Fällen nicht sichtbar
global $gNavigation;

// Pfad des Plugins ermitteln
$plugin_folder_pos = strpos(__FILE__, 'adm_plugins') + 11;
$plugin_file_pos   = strpos(__FILE__, basename(__FILE__));
$plugin_path       = substr(__FILE__, 0, $plugin_folder_pos);
$plugin_folder     = substr(__FILE__, $plugin_folder_pos+1, $plugin_file_pos-$plugin_folder_pos-2);

require_once($plugin_path. '/../adm_program/system/common.php');
require_once($plugin_path. '/'.$plugin_folder.'/common_function.php');
require_once($plugin_path. '/'.$plugin_folder.'/classes/configtable.php'); 

// Einbinden der Sprachdatei
$gL10n->addLanguagePath($plugin_path.'/'.$plugin_folder.'/languages');

$pPreferences = new ConfigTablePKR();

// DB auf Admidio setzen, da evtl. noch andere DBs beim User laufen
$gDb->setCurrentDB();

//Initialisierung und Anzeige des Links nur, wenn vorher keine Deinstallation stattgefunden hat
// sonst wäre die Deinstallation hinfällig, da hier wieder Default-Werte der config in die DB geschrieben werden
if(  strpos($gNavigation->getUrl(), 'preferences_function.php?mode=3') === false)
{
	if ($pPreferences->checkforupdate())
	{
		$pPreferences->init();
	}
	else 
	{
		$pPreferences->read();
	}

	// Zeige Link zum Plugin
	if(check_showpluginPKR($pPreferences->config['Pluginfreigabe']['freigabe']) )
	{
		if (isset($pluginMenu))
		{
			// wenn in der my_body_bottom.php ein $pluginMenu definiert wurde, 
			// dann innerhalb dieses Menüs anzeigen
			$pluginMenu->addItem('categoryreport_show', '/adm_plugins/'.$plugin_folder.'/kategoriereport_show.php?mode=html',
				$gL10n->get('PKR_CATEGORY_REPORT'), '/icons/lists.png'); 
		}
		else 
		{
			// wenn nicht, dann innerhalb des (immer vorhandenen) Module-Menus anzeigen
			$moduleMenu->addItem('categoryreport_show', '/adm_plugins/'.$plugin_folder.'/kategoriereport_show.php?mode=html',
				$gL10n->get('PKR_CATEGORY_REPORT'), '/icons/lists.png'); 
		}
	}
}		

?>