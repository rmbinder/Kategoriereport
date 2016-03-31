<?php
/******************************************************************************
 * 
 * configdata.php
 *   
 * Konfigurationsdaten fuer das Admidio-Plugin Kategoriereport
 * 
 * Copyright    : (c) 2004 - 2015 The Admidio Team
 * Homepage     : http://www.admidio.org
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 * 
 ****************************************************************************/

global $gL10n, $gProfileFields;

//Standardwerte einer Neuinstallation oder beim Anfügen einer zusätzlichen Konfiguration
$config_default['Pluginfreigabe']['freigabe'] = array(	getRole_IDPKR($gL10n->get('SYS_WEBMASTER')),
													   getRole_IDPKR($gL10n->get('SYS_MEMBER')));    		
$config_default['Pluginfreigabe']['freigabe_config'] = array(	getRole_IDPKR($gL10n->get('SYS_WEBMASTER')),
															getRole_IDPKR($gL10n->get('SYS_MEMBER')));    		 		   		

$config_default['Konfigurationen'] = array(	'col_desc' 		=> array($gL10n->get('PKR_PATTERN')),
											'col_fields' 	=> array(	'p'.$gProfileFields->getProperty('FIRST_NAME', 'usf_id').','.
																		'p'.$gProfileFields->getProperty('LAST_NAME', 'usf_id').','.
																		'p'.$gProfileFields->getProperty('ADDRESS', 'usf_id').','.
																		'p'.$gProfileFields->getProperty('CITY', 'usf_id')),
											'col_yes'		=> array('ja'),
											'col_no'		=> array('nein'),
 											'selection_role'=> array(' '),
											'selection_cat'	=> array(' ')  );
															
$config_default['Optionen']['config_default'] = 0; 
															
$config_default['Plugininformationen']['version'] = '';
$config_default['Plugininformationen']['stand'] = '';

/*
 *  Mittels dieser Zeichenkombination werden Konfigurationsdaten, die zur Laufzeit als Array verwaltet werden,
 *  zu einem String zusammengefasst und in der Admidiodatenbank gespeichert. 
 *  Müssen die vorgegebenen Zeichenkombinationen (#_#) jedoch ebenfalls, z.B. in der Beschreibung 
 *  einer Konfiguration, verwendet werden, so kann das Plugin gespeicherte Konfigurationsdaten 
 *  nicht mehr richtig einlesen. In diesem Fall ist die vorgegebene Zeichenkombination abzuändern (z.B. in !-!)
 *  
 *  Achtung: Vor einer Änderung muss eine Deinstallation durchgeführt werden!
 *  Bereits gespeicherte Werte in der Datenbank können nach einer Änderung nicht mehr eingelesen werden!
 */
$dbtoken  = '#_#';  

?>
