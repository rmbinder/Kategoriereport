<?php
/**
 ***********************************************************************************************
 * Kategoriereport
 *
 * Version 2.2.2
 *
 * Dieses Plugin erzeugt eine Auflistung aller Rollenzugehoerigkeiten eines Mitglieds.
 * 
 * Author: rmb
 *
 * Compatible with Admidio version 3.2
 *
 * @copyright 2004-2017 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

//$gNavigation ist zwar definiert, aber in diesem Script nicht immer sichtbar
global $gNavigation;

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

$plugin_folder = '/'.substr(__DIR__,strrpos(__DIR__,DIRECTORY_SEPARATOR)+1);

// Einbinden der Sprachdatei
$gL10n->addLanguagePath(ADMIDIO_PATH . FOLDER_PLUGINS . $plugin_folder . '/languages');

$pPreferences = new ConfigTablePKR();

//Initialisierung und Anzeige des Links nur, wenn vorher keine Deinstallation stattgefunden hat
// sonst waere die Deinstallation hinfaellig, da hier wieder Default-Werte der config in die DB geschrieben werden
// Zweite Voraussetzung: Ein User muss erfolgreich eingeloggt sein
if (strpos($gNavigation->getUrl(), 'preferences_function.php?mode=3') === false && $gValidLogin)
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
	if (check_showpluginPKR($pPreferences->config['Pluginfreigabe']['freigabe']) )
	{
		if (isset($pluginMenu))
		{
			// wenn in der my_body_bottom.php ein $pluginMenu definiert wurde, dann innerhalb dieses Menüs anzeigen
			$pluginMenu->addItem('categoryreport_show', FOLDER_PLUGINS . $plugin_folder .'/kategoriereport_show.php?mode=html',
				$gL10n->get('PLG_KATEGORIEREPORT_CATEGORY_REPORT'), '/icons/lists.png'); 
		}
		else 
		{
			// wenn nicht, dann innerhalb des (immer vorhandenen) Module-Menus anzeigen
			$moduleMenu->addItem('categoryreport_show', FOLDER_PLUGINS . $plugin_folder .'/kategoriereport_show.php?mode=html',
				$gL10n->get('PLG_KATEGORIEREPORT_CATEGORY_REPORT'), '/icons/lists.png'); 
		}
	}
}		
