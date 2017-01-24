<?php
/**
 ***********************************************************************************************
 * Gemeinsame Funktionen fuer das Admidio-Plugin Kategoriereport
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');

$plugin_folder = '/'.substr(__DIR__,strrpos(__DIR__,DIRECTORY_SEPARATOR)+1);

/**
 * Funktion liest die Role-ID einer Rolle aus
 * @param   string  $role_name Name der zu pruefenden Rolle
 * @return  int     rol_id  Rol_id der Rolle; 0, wenn nicht gefunden
 */
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
                      
    $statement = $gDb->query($sql);
    $row = $statement->fetchObject();

   // für den seltenen Fall, dass waehrend des Betriebes die Sprache umgeschaltet wird:  $row->rol_id pruefen
    return (isset($row->rol_id) ?  $row->rol_id : 0);
}

/**
 * Funktion prueft, ob der Nutzer, aufgrund seiner Rollenzugehoerigkeit berechtigt ist das Plugin aufzurufen
 * @param   array  $array   Array mit Rollen-IDs:   entweder $pPreferences->config['Pluginfreigabe']['freigabe']
 *                                                  oder $pPreferences->config['Pluginfreigabe']['freigabe_config']
 * @return  bool   $showPlugin
 */
function check_showpluginPKR($array)
{
	global $gCurrentUser;
	
    $showPlugin = false;

    foreach ($array as $i)
    {
        if ($gCurrentUser->isMemberOfRole($i))
        {
            $showPlugin = true;
        } 
    } 
    return $showPlugin;
}

/**
 * Funktion überprueft den übergebenen Namen, ob er gemaess den Namenskonventionen für
 * Profilfelder und Kategorien zum Uebersetzen durch eine Sprachdatei geeignet ist
 * Bsp: SYS_COMMON --> Rueckgabe true
 * Bsp: Mitgliedsbeitrag --> Rueckgabe false
 *
 * @param   string  $field_name
 * @return  bool
 */
function check_languagePKR($field_name)
{
    $ret = false;
 
    //pruefen, ob die ersten 3 Zeichen von $field_name Grußbuchstaben sind
    //pruefen, ob das vierte Zeichen von $field_name ein _ ist

    //Prüfung entfaellt: pruefen, ob die restlichen Zeichen von $field_name Grußbuchstaben sind
    //if ((ctype_upper(substr($field_name,0,3))) && ((substr($field_name,3,1))=='_')  && (ctype_upper(substr($field_name,4)))   )

    if ((ctype_upper(substr($field_name,0,3))) && ((substr($field_name,3,1)) == '_')   )
    {
      $ret = true;
    }
    return $ret;
}
 
/**
 * Funktion prueft, ob ein User Angehoeriger einer bestimmten Rolle ist
 *
 * @param   int  $role_id   ID der zu pruefenden Rolle
 * @param   int  $user_id [optional]  ID des Users, fuer den die Mitgliedschaft geprueft werden soll;
 * 										ohne Uebergabe wird für den aktuellen User geprueft
 * @return  bool
 */
function hasRole_IDPKR($role_id, $user_id = 0)
{
    global $gCurrentUser, $gDb, $gCurrentOrganization;

    if ($user_id === 0)
    {
        $user_id = $gCurrentUser->getValue('usr_id');
    }
    elseif (is_numeric($user_id) == false)
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
                
    $statement = $gDb->query($sql);

    $user_found = $statement->rowCount();

    if ($user_found === 1)
    {
        return 1;
    }
    else
    {
        return 0;
    }
}

/**
 * Funktion prueft, ob ein User Angehoeriger einer bestimmten Kategorie ist
 *
 * @param   int  $cat_id    ID der zu pruefenden Kategorie
 * @param   int  $user_id   ID des Users, fuer den die Mitgliedschaft geprueft werden soll
 * @return  bool
 */
function hasCategorie_IDPKR($cat_id, $user_id = 0)
{
    global $gCurrentUser, $gDb, $gCurrentOrganization;

    if ($user_id == 0)
    {
        $user_id = $gCurrentUser->getValue('usr_id');
    }
    elseif (is_numeric($user_id) == false)
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
                
    $statement = $gDb->query($sql);

    $user_found = $statement->rowCount();

    if ($user_found == 1)
    {
        return 1;
    }
    else
    {
        return 0;
    }   
}
