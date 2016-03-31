<?php
/**
 * Zeigt im Menue Einstellungen ein Popup-Fenster mit Hinweisen an
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:	keine
 *
 ***********************************************************************************************
 */

// Pfad des Plugins ermitteln
$plugin_folder_pos = strpos(__FILE__, 'adm_plugins') + 11;
$plugin_file_pos   = strpos(__FILE__, basename(__FILE__));
$plugin_path       = substr(__FILE__, 0, $plugin_folder_pos);
$plugin_folder     = substr(__FILE__, $plugin_folder_pos+1, $plugin_file_pos-$plugin_folder_pos-2);

require_once($plugin_path. '/../adm_program/system/common.php');

// set headline of the script
$headline = $gL10n->get('PLG_KATEGORIEREPORT_CONFIGURATIONS');

header('Content-type: text/html; charset=utf-8');

echo '
<div class="modal-header">
    <h4 class="modal-title">'.$headline.'</h4>
</div>
<div class="modal-body">
	<strong>'.$gL10n->get('PLG_KATEGORIEREPORT_COL_DESC').'</strong><br>
    '.$gL10n->get('PLG_KATEGORIEREPORT_COL_DESC_DESC').'<br><br>
    <strong>'.$gL10n->get('PLG_KATEGORIEREPORT_COLUMN_SELECTION').'</strong><br>
	'.$gL10n->get('PLG_KATEGORIEREPORT_COLUMN_SELECTION_DESC').'<br><br>		
    <strong>'.$gL10n->get('PLG_KATEGORIEREPORT_DISPLAY_TEXT_MEMBERSHIP_YES').'</strong><br>
	'.$gL10n->get('PLG_KATEGORIEREPORT_DISPLAY_TEXT_MEMBERSHIP_YES_DESC').'<br><br>
    <strong>'.$gL10n->get('PLG_KATEGORIEREPORT_DISPLAY_TEXT_MEMBERSHIP_NO').'</strong><br>
	'.$gL10n->get('PLG_KATEGORIEREPORT_DISPLAY_TEXT_MEMBERSHIP_NO_DESC').'<br><br>
    <strong>'.$gL10n->get('PLG_KATEGORIEREPORT_ROLE_SELECTION').'</strong><br>
	'.$gL10n->get('PLG_KATEGORIEREPORT_ROLE_SELECTION_CONF_DESC').'<br><br>
    <strong>'.$gL10n->get('PLG_KATEGORIEREPORT_CAT_SELECTION').'</strong><br>
	'.$gL10n->get('PLG_KATEGORIEREPORT_CAT_SELECTION_CONF_DESC').'
</div>';
