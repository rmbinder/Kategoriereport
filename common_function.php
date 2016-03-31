<?php
/******************************************************************************
 * 
 * common_function.php
 *   
 * Gemeinsame Funktionen fuer das Admidio-Plugin Kategoriereport
 * 
 * Copyright    : (c) 2004 - 2015 The Admidio Team
 * Homepage     : http://www.admidio.org
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 * 
 ****************************************************************************/

// Pfad des Plugins ermitteln
$plugin_folder_pos = strpos(__FILE__, 'adm_plugins') + 11;
$plugin_file_pos   = strpos(__FILE__, basename(__FILE__));
$plugin_path       = substr(__FILE__, 0, $plugin_folder_pos);
$plugin_folder     = substr(__FILE__, $plugin_folder_pos+1, $plugin_file_pos-$plugin_folder_pos-2);
 
require_once($plugin_path. '/../adm_program/system/common.php');

// Funktion liest die Role-ID einer Rolle aus
// $role_name - Name der zu pruefenden Rolle
function getRole_IDPKR($role_name)
{
    global $gDb, $gCurrentOrganization;
	
    $sql    = 'SELECT rol_id
                 FROM '. TBL_ROLES. ', '. TBL_CATEGORIES. '
                 WHERE rol_name   = \''.$role_name.'\'
                 AND rol_valid  = 1 
                 AND rol_cat_id = cat_id
                 AND (  cat_org_id = '.$gCurrentOrganization->getValue('org_id').'
                 OR cat_org_id IS NULL ) ';
                      
    $result = $gDb->query($sql);
    $row = $gDb->fetch_object($result);

   // für den seltenen Fall, dass während des Betriebes die Sprache umgeschaltet wird:  $row->rol_id prüfen
    return (isset($row->rol_id) ?  $row->rol_id : 0);
}

// Funktion prueft, ob der Nutzer, aufgrund seiner Rollenzugehörigkeit, berechtigt ist das Plugin aufzurufen
// Parameter: Array mit Rollen-IDs: entweder $pPreferences->config['Pluginfreigabe']['freigabe']
//      oder $pPreferences->config['Pluginfreigabe']['freigabe_config']
function check_showpluginPKR($array)
{
	global $gCurrentUser;
	
    $showPlugin = false;

    foreach ($array AS $i)
    {
        if($gCurrentUser ->isMemberOfRole($i))
        {
            $showPlugin = true;
        } 
    } 
    return $showPlugin;
}

// Funktion überprüft den übergebenen Namen, ob er gemaess den Namenskonventionen für
// Profilfelder und Kategorien zum Uebersetzen durch eine Sprachdatei geeignet ist
// Bsp: SYS_COMMON --> Rueckgabe true
// Bsp: Mitgliedsbeitrag --> Rueckgabe false
function check_languagePKR($field_name)
{
    $ret = false;
 
    //prüfen, ob die ersten 3 Zeichen von $field_name Grußbuchstaben sind
    //prüfen, ob das vierte Zeichen von $field_name ein _ ist

    //Prüfung entfällt: prüfen, ob die restlichen Zeichen von $field_name Grußbuchstaben sind
    //if ((ctype_upper(substr($field_name,0,3))) && ((substr($field_name,3,1))=='_')  && (ctype_upper(substr($field_name,4)))   )

    if ((ctype_upper(substr($field_name,0,3))) && ((substr($field_name,3,1))=='_')   )
    {
      $ret = true;
    }
    return $ret;
}
 
// Funktion prueft, ob ein User die uebergebene Rolle besitzt
// $role_id   - ID der zu pruefenden Rolle
// $user_id   - ID des Users, fuer den die Mitgliedschaft geprueft werden soll
function hasRole_IDPKR($role_id, $user_id = 0)
{
    global $gCurrentUser, $gDb, $gCurrentOrganization;

    if($user_id == 0)
    {
        $user_id = $gCurrentUser->getValue('usr_id');
    }
    elseif(is_numeric($user_id) == false)
    {
        return -1;
    }

    $sql    = 'SELECT mem_id
                FROM '. TBL_MEMBERS. ', '. TBL_ROLES. ', '. TBL_CATEGORIES. '
                WHERE mem_usr_id = '.$user_id.'
                AND mem_begin <= \''.DATE_NOW.'\'
                AND mem_end    > \''.DATE_NOW.'\'
                AND mem_rol_id = rol_id
                AND rol_id   = \''.$role_id.'\'
                AND rol_valid  = 1 
                AND rol_cat_id = cat_id
                AND (  cat_org_id = '.$gCurrentOrganization->getValue('org_id').'
                OR cat_org_id IS NULL ) ';
                
    $result = $gDb->query($sql);

    $user_found = $gDb->num_rows($result);

    if($user_found == 1)
    {
        return 1;
    }
    else
    {
        return 0;
    }
}

// Funktion prueft, ob ein User Angehoeriger einer Kategorie ist
// $cat_id    - ID der zu pruefenden Kategorie
// $user_id   - ID des Users, fuer den die Mitgliedschaft geprueft werden soll
function hasCategorie_IDPKR($cat_id, $user_id = 0)
{
    global $gCurrentUser, $gDb, $gCurrentOrganization;

    if($user_id == 0)
    {
        $user_id = $gCurrentUser->getValue('usr_id');
    }
    elseif(is_numeric($user_id) == false)
    {
        return -1;
    }

    $sql    = 'SELECT mem_id
                FROM '. TBL_MEMBERS. ', '. TBL_ROLES. ', '. TBL_CATEGORIES. '
                WHERE mem_usr_id = '.$user_id.'
                AND mem_begin <= \''.DATE_NOW.'\'
                AND mem_end    > \''.DATE_NOW.'\'
                AND mem_rol_id = rol_id
                AND cat_id   = \''.$cat_id.'\'
                AND rol_valid  = 1 
                AND rol_cat_id = cat_id
                AND (  cat_org_id = '.$gCurrentOrganization->getValue('org_id').'
                OR cat_org_id IS NULL ) ';
                
    $result = $gDb->query($sql);

    $user_found = $gDb->num_rows($result);

    if($user_found == 1)
    {
        return 1;
    }
    else
    {
        return 0;
    }   
}
 
?>