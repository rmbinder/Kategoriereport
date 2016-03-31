<?php
/**
 ***********************************************************************************************
 * Zeigt den Kategoriereport auf dem Bildschirm an
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *   
 * Hinweis:   kategoriereport_show ist eine modifierte lists_show
 * 
 * Parameters:
 *
 * mode   		: Output (html, print, csv-ms, csv-oo, pdf, pdfl)
 * full_screen  : 0 - (Default) show sidebar, head and page bottom of html page
 *                1 - Only show the list without any other html unnecessary elements
 * config		: Die gewählte Konfiguration (Alte Bezeichnung Fokus; die Standardeinstellung wurde über Einstellungen-Optionen festgelegt)
 ***********************************************************************************************
 */

// Pfad des Plugins ermitteln
$plugin_folder_pos = strpos(__FILE__, 'adm_plugins') + 11;
$plugin_file_pos   = strpos(__FILE__, basename(__FILE__));
$plugin_path       = substr(__FILE__, 0, $plugin_folder_pos);
$plugin_folder     = substr(__FILE__, $plugin_folder_pos+1, $plugin_file_pos-$plugin_folder_pos-2);

require_once($plugin_path. '/../adm_program/system/common.php');
require_once($plugin_path. '/'.$plugin_folder.'/common_function.php');  
require_once($plugin_path. '/'.$plugin_folder.'/classes/configtable.php'); 
require_once($plugin_path. '/'.$plugin_folder.'/classes/genreport.php'); 

// Konfiguration einlesen          
$pPreferences = new ConfigTablePKR();
$pPreferences->read();

// Initialize and check the parameters
$validValues = array();
foreach ($pPreferences->config['Konfigurationen']['col_desc'] as $key => $dummy)
{
	$validValues[] = 'X'.$key.'X';
}
$getConfig      = admFuncVariableIsValid($_GET, 'config', 'string', array('defaultValue' => 'X'.$pPreferences->config['Optionen']['config_default'].'X', 'validValues' => $validValues) );

$getMode        = admFuncVariableIsValid($_GET, 'mode', 'string', array('requireValue' => true, 'validValues' => array('csv-ms', 'csv-oo', 'html', 'print', 'pdf', 'pdfl' )));
$getFullScreen  = admFuncVariableIsValid($_GET, 'full_screen', 'numeric');

// initialize some special mode parameters
$separator   = '';
$valueQuotes = '';
$charset     = '';
$classTable  = '';
$orientation = '';
$filename    = $g_organization.'-'.$gL10n->get('PLG_KATEGORIEREPORT_CATEGORY_REPORT');

switch ($getMode)
{
    case 'csv-ms':
        $separator   = ';';  // Microsoft Excel 2007 or new needs a semicolon
        $valueQuotes = '"';  // all values should be set with quotes
        $getMode     = 'csv';
        $charset     = 'iso-8859-1';
        break;
    case 'csv-oo':
        $separator   = ',';   // a CSV file should have a comma
        $valueQuotes = '"';   // all values should be set with quotes
        $getMode     = 'csv';
        $charset     = 'utf-8';
        break;
    case 'pdf':
        $classTable  = 'table';
        $orientation = 'P';
        $getMode     = 'pdf';
        break;
    case 'pdfl':
        $classTable  = 'table';
        $orientation = 'L';
        $getMode     = 'pdf';
        break;
    case 'html':
        $classTable  = 'table table-condensed';
        break;
    case 'print':
        $classTable  = 'table table-condensed table-striped';
        break;
    default:
        break;
}

$str_csv      = '';   // enthaelt die komplette CSV-Datei als String

//die Anzeigeliste erzeugen 
$report = new GenReport();
$report->conf = trim($getConfig,'X');
$report->generate_listData();

$numMembers = count($report->listData);

if($numMembers == 0)
{
    // Es sind keine Daten vorhanden !
    $gMessage->show($gL10n->get('LST_NO_USER_FOUND'));
}

//die Spaltenanzahl bestimmen
$columnCount = count($report->headerData);
    
// define title (html) and headline
$title = $gL10n->get('PLG_KATEGORIEREPORT_CATEGORY_REPORT');
$headline = $gL10n->get('PLG_KATEGORIEREPORT_CATEGORY_REPORT');
$subheadline = $pPreferences->config['Konfigurationen']['col_desc'][trim($getConfig,'X')];    

// if html mode and last url was not a list view then save this url to navigation stack
if($getMode == 'html' && strpos($gNavigation->getUrl(), 'kategoriereport_show.php') === false)
{
    $gNavigation->addUrl(CURRENT_URL);
}

if($getMode != 'csv')
{
    $datatable = false;
    $hoverRows = false;

    if($getMode == 'print')
    {
        // create html page object without the custom theme files
        $page = new HtmlPage($headline);
        $page->hideThemeHtml();
        $page->hideMenu();
        $page->setPrintMode();
                
        $page->setTitle($title);
        $page->addHtml('<h3>'.$subheadline.'</h3>');
        
    	$table = new HtmlTable('adm_lists_table', $page, $hoverRows, $datatable, $classTable);
    }
    elseif($getMode == 'pdf')
    {
    	if(ini_get('max_execution_time')<600)
    	{
    		ini_set('max_execution_time', 600); //600 seconds = 10 minutes
    	}
        require_once(SERVER_PATH. '/adm_program/libs/tcpdf/tcpdf.php');
        $pdf = new TCPDF($orientation, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Admidio');
        $pdf->SetTitle($title);

        // remove default header/footer
        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(false);
 		// set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
		
        // set auto page breaks
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
        $pdf->SetMargins(10, 20, 10);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(0);

        //headline for PDF
        $pdf->SetHeaderData('', '', $headline, '');
		
        // set font
        $pdf->SetFont('times', '', 10);

        // add a page
        $pdf->AddPage();
        
        // Create table object for display
		$table = new HtmlTable('adm_lists_table', $pdf, $hoverRows, $datatable, $classTable);
        $table->addAttribute('border', '1');
        $table->addTableHeader();
        $table->addRow();
        $table->addAttribute('align', 'center');
        $table->addColumn($subheadline, array('colspan' => $columnCount + 1));
        $table->addRow();
    }
    elseif($getMode == 'html')
    {
        $datatable = true;
        $hoverRows = true;

        // create html page object
        $page = new HtmlPage($headline.'<h3>'.$subheadline.'</h3>');

        if($getFullScreen == true)
        {
        	$page->hideThemeHtml();
        }

        $page->setTitle($title);
        $page->addJavascript('
            $("#export_list_to").change(function () {
                if($(this).val().length > 1) {
                    self.location.href = "'. $g_root_path. '/adm_plugins/'.$plugin_folder.'/kategoriereport_show.php?" +
                        "config='.$getConfig.'&mode=" + $(this).val();
                }
            });
            $("#configList").change(function () {
            	if($(this).val().length > 1) {
                    self.location.href = "'. $g_root_path. '/adm_plugins/'.$plugin_folder.'/kategoriereport_show.php?" +
                        "mode=html&full_screen='.$getFullScreen.'&config=" + $(this).val();
                }
            });            
            $("#menu_item_print_view").click(function () {
                window.open("'.$g_root_path. '/adm_plugins/'.$plugin_folder.'/kategoriereport_show.php?" +
                 "config='.$getConfig.'&mode=print", "_blank");
            });', true);

        // get module menu
        $listsMenu = $page->getMenu();
        
        if($getFullScreen == true)
        {
            $listsMenu->addItem('menu_item_normal_picture', $g_root_path. '/adm_plugins/'.$plugin_folder.'/kategoriereport_show.php?mode=html&amp;config='.$getConfig.'&amp;full_screen=0',  
                $gL10n->get('SYS_NORMAL_PICTURE'), 'arrow_in.png');
        }
        else
        {
            $listsMenu->addItem('menu_item_full_screen', $g_root_path. '/adm_plugins/'.$plugin_folder.'/kategoriereport_show.php?mode=html&amp;config='.$getConfig.'&amp;full_screen=1',   
                $gL10n->get('SYS_FULL_SCREEN'), 'arrow_out.png');
        }
        
        // link to print overlay and exports
        $listsMenu->addItem('menu_item_print_view', '#', $gL10n->get('LST_PRINT_PREVIEW'), 'print.png');
        
        $form = new HtmlForm('navbar_export_to_form', '', $page, array('type' => 'navbar', 'setFocus' => false));
        $selectBoxEntries = array('' => $gL10n->get('LST_EXPORT_TO').' ...', 'csv-ms' => $gL10n->get('LST_MICROSOFT_EXCEL').' ('.$gL10n->get('SYS_ISO_8859_1').')', 'pdf' => $gL10n->get('SYS_PDF').' ('.$gL10n->get('SYS_PORTRAIT').')', 
                                  'pdfl' => $gL10n->get('SYS_PDF').' ('.$gL10n->get('SYS_LANDSCAPE').')', 'csv-oo' => $gL10n->get('SYS_CSV').' ('.$gL10n->get('SYS_UTF8').')');
        $form->addSelectBox('export_list_to', null, $selectBoxEntries, array('showContextDependentFirstEntry' => false));
        
        $selectBoxEntries = array(' ' => $gL10n->get('PLG_KATEGORIEREPORT_SELECT_CONFIGURATION').' ...');
    	foreach ($pPreferences->config['Konfigurationen']['col_desc'] as $key => $item)
    	{
			$selectBoxEntries['X'.$key.'X'] =  $item;
		}
        $form->addSelectBox('configList', null, $selectBoxEntries, array('showContextDependentFirstEntry' => false));
        
        $listsMenu->addForm($form->show(false));
        
        if(check_showpluginPKR($pPreferences->config['Pluginfreigabe']['freigabe_config']))
		{
    		// show link to pluginpreferences 
    		$listsMenu->addItem('admMenuItemPreferencesLists', $g_root_path. '/adm_plugins/'.$plugin_folder.'/preferences.php', 
                        $gL10n->get('PLG_KATEGORIEREPORT_SETTINGS'), 'options.png', 'right');        
		}
        
    	$table = new HtmlTable('adm_lists_table', $page, $hoverRows, $datatable, $classTable);
        $table->setDatatablesRowsPerPage($gPreferences['lists_members_per_page']);
    }
	else
	{
		$table = new HtmlTable('adm_lists_table', $page, $hoverRows, $datatable, $classTable);
	}
}

$columnAlign  = array('center');
$columnValues = array($gL10n->get('SYS_ABR_NO'));
$columnNumber = 1;  
  
foreach($report->headerData as $columnHeader) 
{
	// bei Profilfeldern ist in 'id' die usf_id, ansonsten 0
	$usf_id = $columnHeader['id'];
	
    if($gProfileFields->getPropertyById($usf_id, 'usf_type') == 'NUMBER'
        || $gProfileFields->getPropertyById($usf_id, 'usf_type') == 'DECIMAL_NUMBER')
    {
        $columnAlign[] = 'right';
    }
    else
    {
    	$columnAlign[] = 'center';    
    }
	 
    if($getMode == 'csv')
    {
    	if($columnNumber == 1)
        {
        	// in der ersten Spalte die laufende Nummer noch davorsetzen
            $str_csv = $str_csv. $valueQuotes. $gL10n->get('SYS_ABR_NO'). $valueQuotes;
        }
        $str_csv = $str_csv. $separator. $valueQuotes. $columnHeader['data']. $valueQuotes;
    }
    elseif($getMode == 'pdf')
    {
    	if($columnNumber == 1)
        {
        	$table->addColumn($gL10n->get('SYS_ABR_NO'), array('style' => 'text-align: center;font-size:14;background-color:#C7C7C7;'), 'th');
        }
        $table->addColumn($columnHeader['data'], array('style' => 'text-align: center;font-size:14;background-color:#C7C7C7;'), 'th');
    }
    elseif($getMode == 'html' || $getMode == 'print')
    {
    	$columnValues[] = $columnHeader['data'];
    }
    $columnNumber++;
} 

if($getMode == 'csv')
{
    $str_csv = $str_csv. "\n";
}
elseif($getMode == 'html' || $getMode == 'print')
{
    $table->setColumnAlignByArray($columnAlign);
    $table->addRowHeadingByArray($columnValues);
}
else
{
    $table->addTableBody();
    $table->setColumnAlignByArray($columnAlign);
}

$listRowNumber = 1;    

// die Daten einlesen
foreach($report->listData as $member => $memberdata) 
{
	$columnValues = array();

    // Felder zu Datensatz
    $columnNumber=1;
    foreach($memberdata as $key => $content) 
    {         
    	if($getMode == 'html' || $getMode == 'print' || $getMode == 'pdf')
        {    
        	if($columnNumber == 1)
            {
            	// die Laufende Nummer noch davorsetzen
                $columnValues[] = $listRowNumber;  
            }
        }
        else
        {
            if($columnNumber == 1)
            {
                // erste Spalte zeigt lfd. Nummer an
                $str_csv = $str_csv. $valueQuotes. $listRowNumber. $valueQuotes;
            }
        }
         
        /*****************************************************************/
        // create output format
       	/*****************************************************************/
        
        // format value for csv export
        $usf_id = 0;
        $usf_id = $report->headerData[$key]['id'];
      
        if( $usf_id  != 0 
         && $getMode == 'csv'
         && $content > 0
         && ($gProfileFields->getPropertyById($usf_id, 'usf_type') == 'DROPDOWN'
              || $gProfileFields->getPropertyById($usf_id, 'usf_type') == 'RADIO_BUTTON') )
        {
            // show selected text of optionfield or combobox
            $arrListValues = $gProfileFields->getPropertyById($usf_id, 'usf_value_list', 'text');
            $content       = $arrListValues[$content];
        }

        if($getMode == 'csv')
        {
        	$str_csv = $str_csv. $separator. $valueQuotes. $content. $valueQuotes;
        }
        else                   // create output in html layout
        {            
        	if($usf_id!=0)     //only profileFields
        	{
        		$content = $gProfileFields->getHtmlValue($gProfileFields->getPropertyById($usf_id, 'usf_name_intern'), $content, $member);
        	}
       	
            // if empty string pass a whitespace
			if(strlen($content) > 0)
            {
            	$columnValues[] = $content;
			}
            else
            {
            	$columnValues[] = '&nbsp;';
            }
		}
		$columnNumber++;
    }

	if($getMode == 'csv')
    {
    	$str_csv = $str_csv. "\n";
    }
    elseif($getMode == 'html')
    {
        $table->addRowByArray($columnValues, null, array('style' => 'cursor: pointer', 'onclick' => 'window.location.href=\''. $g_root_path. '/adm_program/modules/profile/profile.php?user_id='. $member. '\''));
    }
    elseif($getMode == 'print' || $getMode == 'pdf')
    {
        $table->addRowByArray($columnValues, null, array('nobr' => 'true'));
    }

    $listRowNumber++;
}  // End-For (jeder gefundene User)

// Settings for export file
if($getMode == 'csv' || $getMode == 'pdf')
{
    $filename .= '.'.$getMode;
    
     // for IE the filename must have special chars in hexadecimal 
    if (preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT']))
    {
        $filename = urlencode($filename);
    }

    header('Content-Disposition: attachment; filename="'.$filename.'"');
    
    // neccessary for IE6 to 8, because without it the download with SSL has problems
    header('Cache-Control: private');
    header('Pragma: public');
}

if($getMode == 'csv')
{
    // nun die erstellte CSV-Datei an den User schicken
    header('Content-Type: text/comma-separated-values; charset='.$charset);

    if($charset == 'ISO-8859-1')
    {
        echo utf8_decode($str_csv);
    }
    else
    {
        echo $str_csv;
    }
}
// send the new PDF to the User
elseif($getMode == 'pdf')
{
    // output the HTML content
    $pdf->writeHTML($table->getHtmlTable(), true, false, true, false, '');
    
    //Save PDF to file
    $pdf->Output(SERVER_PATH. '/adm_my_files/'.$filename, 'F');
    
    //Redirect
    header('Content-Type: application/pdf');

    readfile(SERVER_PATH. '/adm_my_files/'.$filename);
    ignore_user_abort(true);
    unlink(SERVER_PATH. '/adm_my_files/'.$filename); 
}
elseif($getMode == 'html' || $getMode == 'print')
{    
    // add table list to the page
    $page->addHtml($table->show(false));

    // show complete html page
    $page->show();
}
