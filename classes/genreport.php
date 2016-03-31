<?php
/**
 ***********************************************************************************************
 * Class manages the data for the report
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/******************************************************************************
 * Klasse verwaltet die Daten für den Report
 *
 * Folgende Methoden stehen zur Verfügung:
 *
 * generate_listData()					-	perzeugt die Arrays listData und headerData für den Report
 * generate_headerSelection() 			- 	erzeugt die Auswahlliste für die Spaltenauswahl
 * isInheaderSelection($search_value)	-	liest die Konfigurationsdaten aus der Datenbank
 *
 *****************************************************************************/

class GenReport
{    
    public	  $headerData = array();               ///< Array mit allen Spaltenüberschriften
    public	  $listData  =array();                 ///< Array mit den Daten für den Report
    public	  $headerSelection  =array();          ///< Array mit der Auswahlliste für die Spaltenauswahl
    public	  $conf;							   ///< die gewählte Konfiguration

    /**
     * GenReport constructor
     */
    public function __construct()
    {   	
		// die HeaderSelection-Daten werden bei jedem Aufruf der Klasse benötigt
		$this->generate_headerSelection();
    }

    /**
     * Erzeugt die Arrays listData und headerData für den Report
     * @return void
     */
	public function generate_listData()
	{
		global $gDb, $gProfileFields, $gCurrentOrganization, $pPreferences;
		
		$workarray = array();
		
		$colfields=explode(',',$pPreferences->config['Konfigurationen']['col_fields'][$this->conf]);
		// die gespeicherten Konfigurationen durchlaufen
		foreach($colfields as $key => $data)
        {
        	// das ist nur zur Überprüfung, ob diese Freigabe noch existent ist
            // es könnte u.U. ja sein, daß ein Profilfeld oder eine Rolle seit der letzten Speicherung gelöscht wurde
        	$found = $this->isInHeaderSelection($data);
            if($found==0)
            {
            	continue;	
            }
            else 
            {
            	$workarray[$key+1] = array();
            }
            
        	//$data splitten in Typ und ID
        	$type=substr($data,0,1);
        	$id=substr($data,1);
        	
        	$workarray[$key+1]['type']=$type;
        	$workarray[$key+1]['id']=$id;
        	
        	$this->headerData[$key+1]['id'] = 0;
        	$this->headerData[$key+1]['data'] = $this->headerSelection[$found]['data'];
        	
        	switch($type)
        	{
        		case 'p':                    //p=profileField
        			// nur bei Profilfeldern wird 'id' mit der 'usf_id' überschrieben
        			$this->headerData[$key+1]['id'] = $id;
        			break;
        		case 'c':                    //c=categorie
        			
        			$sql = 'SELECT DISTINCT mem_usr_id
             				FROM '.TBL_MEMBERS.', '.TBL_CATEGORIES.' , '.TBL_ROLES.' 
             				WHERE cat_type = \'ROL\' 
             				AND cat_id = rol_cat_id
             				AND mem_rol_id = rol_id
             				AND mem_end = \'9999-12-31\'
             				AND cat_id = \''.$id.'\'
             				AND (  cat_org_id = '.$gCurrentOrganization->getValue('org_id').'
               				OR cat_org_id IS NULL )';
	
					$statement = $gDb->query($sql);

					while ($row = $statement->fetch())
					{
						$workarray[$key+1]['usr_id'][]=$row['mem_usr_id'];
					}
        			break;
        		case 'r':                    //r=role

        			$sql = 'SELECT mem_usr_id
             				FROM '.TBL_MEMBERS.', '.TBL_ROLES.' 
             				WHERE mem_rol_id = rol_id
             				AND mem_end = \'9999-12-31\'
             				AND rol_id = \''.$id.'\' ';
	
					$statement = $gDb->query($sql);

					while ($row = $statement->fetch())
					{
						$workarray[$key+1]['usr_id'][]=$row['mem_usr_id'];
					}
        			break;
        		case 'l':                    //l=leader
        			
        			$sql = 'SELECT mem_usr_id
             				FROM '.TBL_MEMBERS.', '.TBL_ROLES.' 
             				WHERE mem_rol_id = rol_id
             				AND mem_end = \'9999-12-31\'
             				AND rol_id = \''.$id.'\' 
             				AND mem_leader = 1 ';
	
					$statement = $gDb->query($sql);

					while ($row = $statement->fetch())
					{
						$workarray[$key+1]['usr_id'][]=$row['mem_usr_id'];
					}
        			break;
        	}
        }  

		// alle Mitglieder der aktuellen Organisation einlesen
		$sql = ' SELECT mem_usr_id
             	FROM '.TBL_MEMBERS.' , '.TBL_ROLES.' , '. TBL_CATEGORIES. ' 
             	WHERE mem_rol_id = rol_id
             	AND rol_valid  = 1   
             	AND rol_cat_id = cat_id
             	AND (  cat_org_id = '.$gCurrentOrganization->getValue('org_id').'
               	OR cat_org_id IS NULL )
             	AND mem_end = \'9999-12-31\' ';
		
		$statement = $gDb->query($sql);
		while($row = $statement->fetch())
		{
			$this->listData[$row['mem_usr_id']] = array();
		}
		
		$user = new User($gDb, $gProfileFields);
		
		// alle Mitlieder durchlaufen   ...
    	foreach ($this->listData as $member => $dummy)
		{     	
			$user->readDataById($member);
	   		
			// bestehen Rollen- und/oder Kategorieeinschränkungen?
        	$rolecatmarker = true;
        	if ($pPreferences->config['Konfigurationen']['selection_role'][$this->conf]<>' '
        	 || $pPreferences->config['Konfigurationen']['selection_cat'][$this->conf]<>' ')
        	{
        		$rolecatmarker = false;	
        		foreach (explode(',',$pPreferences->config['Konfigurationen']['selection_role'][$this->conf]) as $rol)
        		{
        			if (hasRole_IDPKR($rol, $member))
        			{
        				$rolecatmarker = true;
        			}
        		}	
				foreach (explode(',',$pPreferences->config['Konfigurationen']['selection_cat'][$this->conf]) as $cat)
        		{
        			if (hasCategorie_IDPKR($cat, $member))
        			{
        				$rolecatmarker = true;
        			}
        		}
        	} 			
			if (!$rolecatmarker )
        	{
        		unset($this->listData[$member]);
        		continue;
        	}
        	
			foreach($workarray as $key => $data)
			{
				if($data['type']=='p')
				{				
                    if(  ($gProfileFields->getPropertyById($data['id'], 'usf_type') == 'DROPDOWN'
                       	|| $gProfileFields->getPropertyById($data['id'], 'usf_type') == 'RADIO_BUTTON') )
    				{
    					$this->listData[$member][$key] = $user->getValue($gProfileFields->getPropertyById($data['id'], 'usf_name_intern'),'database');
    				}
    				else 
    				{
    					$this->listData[$member][$key] = $user->getValue($gProfileFields->getPropertyById($data['id'], 'usf_name_intern'));
    				}
				}
				elseif($data['type']=='a')              //Sonderfall: Rollengesamtübersicht erstellen
				{
					$role = new TableRoles($gDb);
					$memberShips = $user->getRoleMemberships();
					
					$this->listData[$member][$key] = '';
					foreach($memberShips as $rol_id)
					{
						$role->readDataById($rol_id);
						$this->listData[$member][$key] .= $role->getValue('rol_name').'; ';
					}
					$this->listData[$member][$key] = trim($this->listData[$member][$key],'; ');
				}
				else 
				{
					if(isset($data['usr_id']) AND  in_array($member,$data['usr_id']))
                	{
                    	$this->listData[$member][$key] = $pPreferences->config['Konfigurationen']['col_yes'][$this->conf];
            		}
                	else
                	{
                    	$this->listData[$member][$key] = $pPreferences->config['Konfigurationen']['col_no'][$this->conf];
                	}
				}
			}
		}
	}	
		
    /**
     * Erzeugt die Auswahlliste für die Spaltenauswahl
     * @return void
     */
	private function generate_headerSelection()
	{
		global $gDb,  $gL10n, $gProfileFields, $gCurrentOrganization, $gCurrentUser;
	    
        $categories = array();   
        
        $i 	= 1;
        foreach($gProfileFields->mProfileFields as $field)
        {               
            if($field->getValue('usf_hidden') == 0 || $gCurrentUser->editUsers())
            {   
                $this->headerSelection[$i]['id']   = 'p'.$field->getValue('usf_id');
                $this->headerSelection[$i]['cat_name']   = $field->getValue('cat_name');
                $this->headerSelection[$i]['data']   = addslashes($field->getValue('usf_name'));
                $i++;
            }
        }
        
		// alle (Rollen-)Kategorien der aktuellen Organisation einlesen
		$sql = ' SELECT DISTINCT cat.cat_name, cat.cat_id
             	FROM '.TBL_CATEGORIES.' as cat, '.TBL_ROLES.' as rol
             	WHERE cat.cat_type = \'ROL\' 
             	AND cat.cat_id = rol.rol_cat_id
             	AND (  cat.cat_org_id = '.$gCurrentOrganization->getValue('org_id').'
               	OR cat.cat_org_id IS NULL )';
	
		$statement = $gDb->query($sql);

		$k = 0;
		while ($row = $statement->fetch())
		{
			// ueberprüfen, ob der Kategoriename mittels der Sprachdatei übersetzt werden kann
        	if(check_languagePKR($row['cat_name']))
        	{
        		$row['cat_name'] = $gL10n->get($row['cat_name']);
        	}
			$categories[$k]['cat_id']   = $row['cat_id'];
			$categories[$k]['cat_name'] = $row['cat_name'];
			$categories[$k]['data'] 	= $gL10n->get('SYS_CATEGORY').': '.$row['cat_name'];
			$k++;
		}
 
		// alle eingelesenen Kategorien durchlaufen und die Rollen dazu einlesen
  		foreach ($categories as $data)
		{
			$this->headerSelection[$i]['id']   		= 'c'.$data['cat_id'];
			$this->headerSelection[$i]['cat_name']	= $data['cat_name'];
			$this->headerSelection[$i]['data']		= $data['data'];
			$i++;

       		$sql = 'SELECT DISTINCT rol.rol_name, rol.rol_id, rol.rol_valid, rol.rol_visible
                	FROM '.TBL_CATEGORIES.' as cat, '.TBL_ROLES.' as rol
                	WHERE cat.cat_id = \''.$data['cat_id'].'\'
                	AND cat.cat_id = rol.rol_cat_id';
    		$statement = $gDb->query($sql);
    		
        	while($row = $statement->fetch())
        	{
        		$marker='';
        		if($row['rol_valid']==0 || $row['rol_visible']==0)
        		{
        			$marker = ' (';
        			$marker .= ($row['rol_valid']==0 ? '*' : '');
        			$marker .= ($row['rol_visible']==0 ? '!' : '');
        			$marker .= ')';
        		}
        			
        		$this->headerSelection[$i]['id']   		= 'r'.$row['rol_id'];
        		$this->headerSelection[$i]['cat_name']	= $data['cat_name'];
				$this->headerSelection[$i]['data']		= $gL10n->get('SYS_ROLE').': '.$row['rol_name'].$marker;
				$i++;
        		
				$this->headerSelection[$i]['id']   		= 'l'.$row['rol_id'];
        		$this->headerSelection[$i]['cat_name']	= $data['cat_name'];
				$this->headerSelection[$i]['data']		= $gL10n->get('SYS_LEADER').': '.$row['rol_name'].$marker;
				$i++;
        	}	
    	}
    	//Zusatzspalte für die Gesamtrollenübersicht erzeugen
    	$this->headerSelection[$i]['id']   		= 'adummy';          //a wie additional
        $this->headerSelection[$i]['cat_name']	= $gL10n->get('PLG_KATEGORIEREPORT_ADDITIONAL_COLS');
		$this->headerSelection[$i]['data']		= $gL10n->get('PLG_KATEGORIEREPORT_ROLEMEMBERSHIPS');
	}
	
    /**
     * Prüft, ob es den übergebenen Wert in der Spaltenauswahlliste gibt
     * Hinweis: die Spaltenauswahlliste ist immer aktuell, da sie neu generiert wird,
     * der zu prüfende Wert könnte jedoch veraltet sein, da er aus der Konfigurationstabelle stammt
     * @param 	string $search_value
     * @return 	int
     */
	public function isInheaderSelection($search_value)
	{
		$ret = 0;
		foreach($this->headerSelection as $key =>$data)
		{
			if($data['id']==$search_value)
			{
				$ret = $key;
				break;
			}
		}
		return $ret;
	}
}
