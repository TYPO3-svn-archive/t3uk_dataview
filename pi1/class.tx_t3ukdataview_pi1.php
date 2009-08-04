<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Eike Starkmann <starkmann@undkonsorten.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Plugin 'Dateview' for the 't3uk_dataview' extension.
 *
 * @author	Eike Starkmann <starkmann@undkonsorten.com>
 */


require_once(PATH_tslib.'class.tslib_pibase.php');

class tx_t3ukdataview_pi1 extends tslib_pibase {
	var $prefixId = 'tx_t3ukdataview_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_t3ukdataview_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey = 't3uk_dataview';	// The extension key.
	var $pi_checkCHash = TRUE;
	var $conf;

	function init($conf) {
		$this->conf=$conf;
		$this->pi_loadLL(); 
		$this->pi_setPiVarDefaults();
		$this->pi_initPIflexForm();
		
		$this->sys_language_mode = $this->conf['sys_language_mode']?$this->conf['sys_language_mode'] : $GLOBALS['TSFE']->sys_language_mode;
		//Read Values from the Backend felxform 
		$template = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], general_template, sGENERAL);
	      	$this->config['title'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], general_titel, sGENERAL);
		$this->config['table'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], fetable, sGENERAL);
		$this->config['template'] = $this->cObj->fileResource($template  ? 'uploads/t3uk_dataview/'.$template : $this->conf['templateFile']);
		$this->config['general_pid'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], general_pid, sGENERAL);
		//sDEF
		$this->config['list_showFields'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], showFields, sDEF);
		//This is done because we always need the uid of the elements
		$this->config['list_showFields'] = $this->config['list_showFields'].", uid";
		if ($this->config['list_showFields']=="") $this->config['list_showFields']='*';
		$this->config['list_sorting'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], list_sorting, sDEF);
		$this->config['foreignTables'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], foreignTables, sDEF);
		$this->config['list_order'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], list_order, sDEF);
		$this->config['filter_fields'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], filter_fields, sDEF);
		$this->config['list_limit'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], list_limit, sDEF);
		$this->config['list_linkFields'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], linkFields, sDEF);
		$this->config['list_singlePid'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], single_pid, sDEF);
		//if there is no single page selected, just take the current one
		if ($this->config['list_singlePid']=="") $this->config['list_singlePid']=$this->cObj->data['pid'];
		$this->config['additional_where'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], list_limit, sDEF);
		//sSingle
		$this->config['single_showFields'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], showFields, sSINGLE);
		//if nothing is selected show all fields
		if($this->config['single_showFields']=="") $this->config['single_showFields'] = "*";
		//sSearch
		$this->config['searchFields'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], searchFields, sSEARCH);
		$this->modus = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'what_to_display', 'sGENERAL');
		//Debug shit
		//Complete Flexform
		t3lib_div::print_array($this->config['filter_fields']);
		t3lib_div::print_array($this->config['foreignTables']);
		t3lib_div::print_array($this->modus);
		t3lib_div::print_array($this->config['list_linkFields']);
		t3lib_div::print_array($this->config['list_singlePid']);
		t3lib_div::print_array($this->config['list_singlePid']);
		//t3lib_div::print_array("Config:");
		//t3lib_div::print_array($this->config);
		//t3lib_div::print_array("Template:");
		//t3lib_div::print_array($this->config['template']);
		//t3lib_div::print_array("General Pid");
		//t3lib_div::print_array($this->config['general_pid']);
	}
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf)	{
		$this->local_cObj = t3lib_div::makeInstance('tslib_cObj');
		$this->init($conf);
	
		if ($this->piVars['single']) $this->modus="SINGLE";
		switch($this->modus) {
		 case "LIST":
			$content = $this->displayList();
			break;
		 case "SINGLE":
			$content = $this->displaySingle();
			break;
		}
		return $this->pi_wrapInBaseClass($content);
	}
	function displaySingle(){
		global $TCA;
		t3lib_div::loadTCA($this->config['table']);
		setlocale (LC_ALL,$GLOBALS['TSFE']->config['config']['locale_all']);
		$lconf = $this->conf["displaySingle."];
		
		//This is done because fe_users dont have a hidden and sys_language_uid field (really stupid)
		if($this->config['table']=="fe_users"){
			$hidden =" disable = '0'";
			$language="";
		}
		else {
			$hidden =" hidden = '0'";
			$language ="AND sys_language_uid =".$GLOBALS['TSFE']->sys_language_content;
		}
	
		$res = $GLOBALS["TYPO3_DB"]->exec_SELECTquery($this->config['single_showFields'], $this->config['table'], "$general_pid $hidden $language AND deleted = '0' AND uid= (".intval($this->piVars['uid']).") $additional_where", "", "", "");
		
		$t = array();
		$t["total"] = $this->cObj->getSubpart($this->config['template'], "###TEMPLATE_LIST###");
		$t["item"] = $this->cObj->getSubpart($t["total"], "###TEMPLATE_LIST_ITEM###");
	
		$content_table = "";
		$markerArray = array();

		$row = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res);
		if ($GLOBALS['TSFE']->sys_language_content) {
			$row = $GLOBALS['TSFE']->sys_page->getPageOverlay($row);
		}
		foreach($row as $name => $value){
			  $wrap['stdWrap.']['wrap'] = '<span class="t3uk_dataview_content"> | </span>';
			  $wrap['stdWrap.']['require'] = 1;
				//Is there something defined in LOCAL_LANG?
			  if($this->LOCAL_LANG[$this->LLkey][$name]!=""){
				$wrap['stdWrap.']['wrap2'] = '<div class="t3uk_dataview_single_'.$name.'"><span class="t3uk_dataview_single_label">'.$this->LOCAL_LANG[$this->LLkey][$name].'</span> | </div> '."\r\n";
			  }else $wrap['stdWrap.']['wrap2'] = '<div class="t3uk_dataview_single_'.$name.'"> | </div> '."\r\n";
			  if ($value!=""){
				//Field is a groupbox
			      if($TCA[$this->config['table']]['columns'][$name]['config']['type']=='group'){
				//Field is a file
				if($TCA[$this->config['table']]['columns'][$name]['config']['internal_type']=='file'){
				      
				      //Is it an Image?
				      $pos = strrpos($TCA[$this->config['table']]['columns'][$name]['config']['allowed'], "jpg")||strrpos($TCA[$this->config['table']]['columns'][$name]['config']['allowed'], "gif")
				      ||strrpos($TCA[$this->config['table']]['columns'][$name]['config']['allowed'], "jpeg")||strrpos($TCA[$this->config['table']]['columns'][$name]['config']['allowed'], "tif")||strrpos($TCA[$this->config['table']]['columns'][$name]['config']['allowed'], "bmp")||strrpos($TCA[$this->config['table']]['columns'][$name]['config']['allowed'], "pcx")||strrpos($TCA[$this->config['table']]['columns'][$name]['config']['allowed'], "tga")||strrpos($TCA[$this->config['table']]['columns'][$name]['config']['allowed'], "png")||strrpos($TCA[$this->config['table']]['columns'][$name]['config']['allowed'], "ai");
				      //Yes its an Image
				      if ($pos === true) {
					  $image =$value;
					  $lconf["image."]["file"] = $TCA[$this->config['table']]['columns'][$name]['config']['uploadfolder']."/".($image);
					  $lconf["image."]["altText"] = $image;
					  $theImgCode = $this->cObj->IMAGE($lconf["image."]);
					  $content .= $theImgCode;
				      }
			      }
				//Its a database relation
				if($TCA[$this->config['table']]['columns'][$name]['config']['internal_type']=='db') {	
					//Its an MM Relation
					if($TCA[$this->config['table']]['columns'][$name]['config']['MM']){
						$local=$this->config['table'];
						$mm_table = $TCA[$this->config['table']]['columns'][$name]['config']['MM'];
						$foreign_table = $TCA[$this->config['table']]['columns'][$name]['config']['allowed'];
						//To avoid ambigous fields we set the local table praefix
						//We take the label of the foreign table as the "to show field"
						// for the frontend. Better ideas?
						$fields=explode(",",$TCA[$foreign_table]['ctrl']['label']);
						foreach($fields as $name => $value){
							$temp[$name]=$foreign_table.".".$value;
						}
						$fields=implode(",",$temp);
						if ($general_pid) $general_pid="$foreign_table.$general_pid";
						if ($language) $language = $language ="AND $foreign_table.sys_language_uid =".$GLOBALS['TSFE']->sys_language_content;
						
						$res = $GLOBALS["TYPO3_DB"]->exec_SELECT_mm_query($fields,$local,$mm_table,$foreign_table, "AND $mm_table.uid_local = (".intval($this->piVars['uid']).") AND $general_pid $local.$hidden AND $local.deleted = '0' AND $foreign_table.hidden=0 AND $foreign_table.deleted=0 $additional_where", "$foreign_table.uid", "","");
						/*This is Version with sys_language_uid, which crashes when there is no sys_language_uid in foreign table
						$res = $GLOBALS["TYPO3_DB"]->exec_SELECT_mm_query($fields,$foreign_table,$mm_table,$local, "AND $general_pid $local.$hidden $language AND $local.deleted = '0' AND $foreign_table.hidden=0 AND $foreign_table.deleted=0 $additional_where", "$foreign_table.uid", "","");*/
	
						while ($row = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res)) {
							if ($GLOBALS['TSFE']->sys_language_content) {
								$row = $GLOBALS['TSFE']->sys_page->getPageOverlay($row);
							}
							foreach($row as $name => $value){
							//t3lib_div::print_array($TCA[$this->config['table']]['columns'][$name]);
							$wrap2['stdWrap.']['wrap'] = '<span class="t3uk_dataview_content"> | </span>';
							$wrap2['stdWrap.']['require'] = 1;
							//t3lib_div::print_array($lconf["label_$name."]);
				
							if($this->LOCAL_LANG[$this->LLkey][$name]!=""){
								$wrap2['stdWrap.']['wrap2'] = '<div class="t3uk_dataview_list_'.$name.'"><span class="t3uk_dataview_list_label">'.$this->LOCAL_LANG[$this->LLkey][$name].'</span> | </div> '."\r\n";
							}else $wrap2['stdWrap.']['wrap2'] = '<div class="t3uk_dataview_list_'.$name.'"> | </div> '."\r\n";
							$content .= $this->local_cObj->stdWrap($value,$wrap2);
							}	
						}
					}
					//Its a normal commaseperated Relation
					if($ToDo){
					echo("Commaseperated is ToDo");
					}
				
				}
			      }
			      //Field is an Timestamp
			      if($TCA[$this->config['table']]['columns'][$name]['config']['type']=='input'&&$TCA[$this->config['table']]['columns'][$name]['config']['eval']=='date') $content .=$this->local_cObj->stdWrap(strftime($lconf['date_stdWrap'],$value),$wrap);
				
				
				//Normal Text
			      if(($TCA[$this->config['table']]['columns'][$name]['config']['type']=='input'&&$TCA[$this->config['table']]['columns'][$name]['config']['eval']!='date')||$TCA[$this->config['table']]['columns'][$name]['config']['type']=='text')
				$content .= $this->local_cObj->stdWrap($value,$wrap);
			  }
			}
		return $content;
		
		
		
	}
	function displayList() {
		global $TCA;
		t3lib_div::loadTCA($this->config['table']);
		
		setlocale (LC_ALL,$GLOBALS['TSFE']->config['config']['locale_all']);
		$lconf = $this->conf["displayList."];
		$sorting = $this->config['list_sorting'];
		$order = $this->config['list_order'];
		
		

		
		//This is done because fe_users dont have a hidden and sys_language_uid field (really stupid)
		if($this->config['table']=="fe_users"){
			$hidden =" disable = '0'";
			$language="";
		}
		else {
			$hidden =" hidden = '0'";
			$language ="AND sys_language_uid =".$GLOBALS['TSFE']->sys_language_content;
		}
		
		if($this->config['general_pid']!="") $general_pid ="pid IN (".$this->config['general_pid'].") AND";
		else $general_pid = "";
		
		if($this->config['additional_where']!="") $additional_where = " AND ".$this->config['additional_where'];
		else $additional_where = "";


		$this->internal['results_at_a_time'] = $this->config['list_limit'];
		$resultlimit = $this->internal['results_at_a_time'];
		$startAt = (intval($this->piVars['pointer'])) ? (intval($this->piVars['pointer']) * $this->internal['results_at_a_time']) : 0;
		
		$res = $GLOBALS["TYPO3_DB"]->exec_SELECTquery($this->config['list_showFields'], $this->config['table'], "$general_pid $hidden $language AND deleted = '0' $additional_where", "", "$sorting $order", "$startAt, $resultlimit");
	      
		$restotal = $GLOBALS["TYPO3_DB"]->exec_SELECTquery($this->config['list_showFields'], $this->config['table'], "$general_pid $hidden AND deleted = '0' $additional_where", "", "", "");
	
		//Foreign Tables is set
		if($this->config['foreignTables']){
		//There are Filter Fields, in an MM table
		if(($TCA[$this->config['table']]['columns'][$this->config['foreignTables']]['config']['MM'])){
			$local=$this->config['table'];
			$mm_table = $TCA[$this->config['table']]['columns'][$this->config['foreignTables']]['config']['MM'];
			$foreign_table = $TCA[$this->config['table']]['columns'][$this->config['foreignTables']]['config']['allowed'];
			//To avoid ambigous fields we set the local table praefix
			$fields=explode(",",$this->config['list_showFields']);
			foreach($fields as $name => $value){
				$temp[$name]=$local.".".$value;
			}
			$fields=implode(",",$temp);
			if ($general_pid) $general_pid="$local.$general_pid";
			if ($language) $language = $language ="AND $local.sys_language_uid =".$GLOBALS['TSFE']->sys_language_content;
			$res = $GLOBALS["TYPO3_DB"]->exec_SELECT_mm_query($fields,$local,$mm_table,$foreign_table, "AND $general_pid $local.$hidden $language AND $local.deleted = '0' AND $foreign_table.uid IN(".$this->config['filter_fields'].") AND $foreign_table.hidden=0 AND $foreign_table.deleted=0 $additional_where", "$local.uid", "$local.$sorting $order","$startAt, $resultlimit");
			//$restotal = $GLOBALS["TYPO3_DB"]->exec_SELECT_mm_query($fields,$local,$mm_table,$foreign_table, "AND $general_pid $hidden $language AND $foreign_tabledeleted = '0' AND $foreign_table.uid IN(".$this->config['filter_fields'].") $additional_where", 'uid', "","");	
			
		//There are Filter Fields , in comma seperated list
		} else {
			$res = $GLOBALS["TYPO3_DB"]->exec_SELECTquery($this->config['list_showFields'], $this->config['table'], "$general_pid $hidden $language AND deleted = '0' AND ".$this->config['foreignTables']." IN (". $this->config['filter_fields']." )  $additional_where", "", "$sorting $order", "$startAt, $resultlimit");
	      
			$restotal = $GLOBALS["TYPO3_DB"]->exec_SELECTquery($this->config['list_showFields'], $this->config['table'], "$general_pid $hidden AND deleted = '0' $additional_where", "", "", "");
		}
		}
		
		
	
		$count = $GLOBALS["TYPO3_DB"]->sql_num_rows($restotal);
		$this->internal['res_count'] = $count;
		
		
		
	
		$t = array();
		$t["total"] = $this->cObj->getSubpart($this->config['template'], "###TEMPLATE_LIST###");
		$t["item"] = $this->cObj->getSubpart($t["total"], "###TEMPLATE_LIST_ITEM###");
	
		$content_table = "";
		$markerArray = array();
	
	/* ORIGINAL
			while ($row = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res)) {
			if ($GLOBALS['TSFE']->sys_language_content) {
				$row = $GLOBALS['TSFE']->sys_page->getPageOverlay($row);
				//t3lib_div::print_array($row);
				//$OLmode = ($this->sys_language_mode == 'strict'?'hideNonTranslated':'');
				//$row = $GLOBALS['TSFE']->sys_page->getRecordOverlay('pages_language_overlay', $row, $GLOBALS['TSFE']->sys_language_content, "strict;0");
			}
	*/
			
		while ($row = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res)) {
			if ($GLOBALS['TSFE']->sys_language_content) {
			
				$row = $GLOBALS['TSFE']->sys_page->getPageOverlay($row);
				//t3lib_div::print_array($row);
				//$OLmode = ($this->sys_language_mode == 'strict'?'hideNonTranslated':'');
				//$row = $GLOBALS['TSFE']->sys_page->getRecordOverlay('pages_language_overlay', $row, $GLOBALS['TSFE']->sys_language_content, "strict;0");
			}
			//The uid of the current element
			$currentuid=$row['uid'];
			foreach($row as $name => $value){
			  //t3lib_div::print_array($TCA[$this->config['table']]['columns'][$name]);
			 // $wrap['stdWrap'] = "<div class='t3uk_dataview_$name'> | </div>";
			  $wrap['stdWrap.']['wrap'] = '<span class="t3uk_dataview_content"> | </span>';
			  $wrap['stdWrap.']['require'] = 1;
			//t3lib_div::print_array($lconf["label_$name."]);

			  if($this->LOCAL_LANG[$this->LLkey][$name]!=""){
				$wrap['stdWrap.']['wrap2'] = '<div class="t3uk_dataview_list_'.$name.'"><span class="t3uk_dataview_list_label">'.$this->LOCAL_LANG[$this->LLkey][$name].'</span> | </div> '."\r\n";
			  }else $wrap['stdWrap.']['wrap2'] = '<div class="t3uk_dataview_list_'.$name.'"> | </div> '."\r\n";
			  
			  if ($value!=""){
			      if($TCA[$this->config['table']]['columns'][$name]['config']['type']=='group'&&$TCA[$this->config['table']]['columns'][$name]['config']['internal_type']=='file'){
				      
				      //Is it an Image?
				      $pos = strrpos($TCA[$this->config['table']]['columns'][$name]['config']['allowed'], "jpg")||strrpos($TCA[$this->config['table']]['columns'][$name]['config']['allowed'], "gif")
				      ||strrpos($TCA[$this->config['table']]['columns'][$name]['config']['allowed'], "jpeg")||strrpos($TCA[$this->config['table']]['columns'][$name]['config']['allowed'], "tif")||strrpos($TCA[$this->config['table']]['columns'][$name]['config']['allowed'], "bmp")||strrpos($TCA[$this->config['table']]['columns'][$name]['config']['allowed'], "pcx")||strrpos($TCA[$this->config['table']]['columns'][$name]['config']['allowed'], "tga")||strrpos($TCA[$this->config['table']]['columns'][$name]['config']['allowed'], "png")||strrpos($TCA[$this->config['table']]['columns'][$name]['config']['allowed'], "ai");
				      //Yes its an Image
				      if ($pos === true) {
					  $image =$value;
					  $lconf["image."]["file"] = $TCA[$this->config['table']]['columns'][$name]['config']['uploadfolder']."/".($image);
					  $lconf["image."]["altText"] = $image;
					  $theImgCode = $this->cObj->IMAGE($lconf["image."]);
					  $content .= $theImgCode;
				      }
			      }
			      //Field is an Timestamp
			      if($TCA[$this->config['table']]['columns'][$name]['config']['type']=='input'&&$TCA[$this->config['table']]['columns'][$name]['config']['eval']=='date') $content .=$this->local_cObj->stdWrap(strftime($lconf['date_stdWrap'],$value),$wrap);
				//Normal Text
			      if(($TCA[$this->config['table']]['columns'][$name]['config']['type']=='input'&&$TCA[$this->config['table']]['columns'][$name]['config']['eval']!='date')||$TCA[$this->config['table']]['columns'][$name]['config']['type']=='text')
				//Shall the current field be linke to single page?
				if (stripos($this->config['list_linkFields'],$name)!==false){
					$temp= $this->pi_linkTP($value,array($this->prefixId."[uid]" => $currentuid,$this->prefixId."[single]" => "1"),1,$this->config['list_singlePid']);
					$content .= $this->local_cObj->stdWrap($temp,$wrap);
				}
				else $content .= $this->local_cObj->stdWrap($value,$wrap);
			  }
			}

			/*
			$datum_beginn = date("jS F Y", $row["tx_t3ukdataview_datum_beginn"]);
			$datum_ende = date("jS F Y", $row["tx_t3ukdataview_datum_ende"]);
			$markerArray["###TITEL###"] = $this->pi_linkToPage($row["title"],$row["uid"],$target = '_top',array());
			$image =$row['tx_t3ukdataview_bild'];
			if($row["tx_t3ukdataview_datum_beginn"]==0 ||$row["tx_t3ukdataview_datum_beginn"]==null)$markerArray["###DATUM_BEGINN###"] =""; 
			else $markerArray["###DATUM_BEGINN###"] = $this->local_cObj->stdWrap(strftime($lconf['date_stdWrap'],$row['tx_t3ukdataview_datum_beginn']),$lconf['dateBegin.']);

			if ($row["tx_t3ukdataview_datum_ende"]==0||$row["tx_t3ukdataview_datum_ende"]==null) $markerArray["###DATUM_ENDE###"] ="";
			else $markerArray["###DATUM_ENDE###"] = $this->local_cObj->stdWrap(strftime($lconf['date_stdWrap'],$row['tx_t3ukdataview_datum_ende']),$lconf['dateEnd.']);
	
			$markerArray["###UHRZEIT_BEGINN###"] = $this->local_cObj->stdWrap($row['tx_t3ukdataview_uhrzeit_beginn'],$lconf['time_wrap.']);
			$markerArray["###UHRZEIT_ENDE###"] = $row['tx_t3ukdataview_uhrzeit_ende'];
			$markerArray["###ORT###"] = $row['tx_t3ukdataview_ort'];
			if ($row["tx_t3ukdataview_typ"]!="")$markerArray["###TYP###"] = $row['tx_t3ukdataview_typ'].": ";
			else $markerArray["###TYP###"] ="";
			$markerArray["###TEASER###"] = $row["tx_t3ukdataview_teaser"];
			$markerArray["###DETAILS###"] =$this->pi_linkToPage($this->pi_getLL('label_detaillink_kurz'),$row["uid"],$target = '_top',array());
			$image =$row['tx_t3ukdataview_bild'];	
			$lconf["image."]["file"] = "uploads/tx_t3ukdataview/".($image);
			$lconf["image."]["altText"] = $image;
			$theImgCode = $this->cObj->IMAGE($lconf["image."]);
			$markerArray["###BILD###"] = $theImgCode;
			$content_table .= $this->cObj->substituteMarkerArrayCached($t["item"],$markerArray,array(),array());*/
		}
		/*
		$subpartArray = array();
		$subpartArray["###LABEL_DESCRIPTION###"] = $this->pi_getLL('label_usergroup_description');
		$subpartArray["###USERGROUP###"] = $group_name;
		$subpartArray["###BROWSE###"] = $this->pi_list_browseresults(1, "");
		$subpartArray["###USERGROUP_DESCRIPTION###"] = $group_description;
		$subpartArray["###PASSWORD###"] = $row["password"];
		$subpartArray["###ISONLINE###"] = $online;
		$subpartArray["###LASTLOGIN###"] = $lastlogin;
		$subpartArray["###NAME###"] = $row["name"];
		$subpartArray["###TITLE###"] = $row["title"];
		$subpartArray["###COMPANY###"] = $row["tx_ukalumni_company_name"];
		$subpartArray["###STREET###"] = $row["address"];
		$subpartArray["###ZIP###"] = $row["zip"];
		$subpartArray["###CITY###"] = $row["city"];
		$subpartArray["###COUNTRY###"] = $row["country"];
		$subpartArray["###PHONE###"] = $row["telephone"];
		$subpartArray["###FAX###"] = $row["fax"];
		$subpartArray["###EMAIL###"] = $this->cObj->typolink($row["email"],$temp_conf);
		$subpartArray["###WWW###"] = "<A href='http://".$row['www']."' title='".$row["name"]."' target='_blank'>".$row['www']."</A>";
		$lconf["image."]["file"] = "uploads/tx_srfeuserregister/".($row['image']);
		$lconf["image."]["altText"] = $image;
	
		$theImgCode = $this->cObj->IMAGE($lconf["image."]);
		$markerArray["###IMAGE###"] =$theImgCode;
		$subpartArray["###DESCRIPTION###"] = $row["comments"];
		$subpartArray["###LABEL_USERGROUP_DESCRIPTION###"] = $this->pi_getLL('label_usergroup_description');
		$subpartArray["###LABEL_TITLE###"] = $this->pi_getLL('label_title');
		$subpartArray["###LABEL_NAME###"] = $this->pi_getLL('label_name');
		$subpartArray["###LABEL_COMPANY###"] = $this->pi_getLL('label_company');
		$subpartArray["###LABEL_PASSWORD###"] = $this->pi_getLL('label_password');
		$subpartArray["###LABEL_ONLINE###"] = $this->pi_getLL('label_online');
		$subpartArray["###LABEL_LASTLOGIN###"] = $this->pi_getLL('label_lastlogin');
		$subpartArray["###LABEL_STREET###"] = $this->pi_getLL('label_street');
		$subpartArray["###LABEL_ZIP###"] = $this->pi_getLL('label_zip');
		$subpartArray["###LABEL_CITY###"] = $this->pi_getLL('label_city');
		$subpartArray["###LABEL_COUNTRY###"] = $this->pi_getLL('label_country');
		$subpartArray["###LABEL_PHONE###"] = $this->pi_getLL('label_phone');
		$subpartArray["###LABEL_FAX###"] = $this->pi_getLL('label_fax');
		$subpartArray["###LABEL_EMAIL###"] = $this->pi_getLL('label_email');
		$subpartArray["###LABEL_WWW###"] = $this->pi_getLL('label_www');
		$subpartArray["###LABEL_IMAGE###"] = $this->pi_getLL('label_image');
	
		$subpartArray["###LABEL_SEARCH_BUTTON###"] = $this->pi_getLL('label_search_button');
		$subpartArray["###SEARCH_ACTION###"] = $this->pi_getPageLink($GLOBALS["TSFE"]->id);
		$subpartArray["###SEARCH_INPUT###"] = "$this->prefixId.'[DATA][search]";
		$subpartArray["###SEARCH_SUBMIT###"] = "$this->prefixId.'[search]";
		//$subpartArray["###COUNT###"] = $count;
		//$subpartArray["###COUNT_TEXT###"] = $this->pi_getLL('count_text');
	
	
		if ($this->config['limit'] > 0 && $count > $this->config['limit']) {
			$this->internal['res_count'] = $count;
			$this->internal['maxPages'] = $this->conf['pageBrowser.']['maxPages'] > 0 ? $this->conf['pageBrowser.']['maxPages'] : 10;
			$this->internal['results_at_a_time'] = $this->config['limit'];
			if (!$this->conf['pageBrowser.']['showPBrowserText']) {
				$this->LOCAL_LANG[$this->LLkey]['pi_list_browseresults_page'] = '';
			}
			$subpartArray['###BROWSE_LINKS###'] = $this->pi_list_browseresults($this->conf['pageBrowser.']['showResultCount'],$this->conf['pageBrowser.']['tableParams']);
			$subpartArray = $this->getPageBrowser($subpartArray);
		} else {
			$this->internal['res_count'] = $count;
			$this->internal['maxPages'] = $this->conf['pageBrowser.']['maxPages'] > 0 ? $this->conf['pageBrowser.']['maxPages'] : 10;
		
			$subpartArray = $this->getPageBrowser($subpartArray);
			$subpartArray['###BROWSE_LINKS###'] = '';
			$subpartArray['###LINK_PREV###'] = '';
			$subpartArray['###PAGES###'] = '';
			$subpartArray['###LINK_NEXT###'] = '';
	
		} 	
		$subpartArray["###CONTENT###"] = $content_table;
	
		$content .= $this->cObj->substituteMarkerArrayCached($t["total"],array(),$subpartArray,array('browseBoxWrap' => '<div class="browseBoxWrap">|</div>'));
		*/
		return $content;
	
	}

	function getPageBrowser($markerArray) {
		$newsCount = $this->internal['res_count'] ;
		$begin_at = $this->piVars['pointer'] * $this->config['limit'];
		// Make Next link
		if ($newsCount > $begin_at + $this->config['limit']) {
			$next = ($begin_at + $this->config['limit'] > $newsCount) ? $newsCount - $this->config['limit']:$begin_at + $this->config['limit'];
			$next = intval($next / $this->config['limit']);
			$next_link = $this->pi_linkTP_keepPIvars($this->pi_getLL('pi_list_browseresults_next', 'Next >'), array('pointer' => $next), $this->allowCaching);
			$markerArray['###LINK_NEXT###'] = $this->local_cObj->stdWrap($next_link, $this->conf['pageBrowser.']['next_stdWrap.']);
		} else {
			$markerArray['###LINK_NEXT###'] = '';
		}
		// Make Previous link
		if ($begin_at) {
			$prev = ($begin_at - $this->config['limit'] < 0)?0:$begin_at - $this->config['limit'];
			$prev = intval($prev / $this->config['limit']);
			$prev_link = $this->pi_linkTP_keepPIvars($this->pi_getLL('pi_list_browseresults_prev', '< Previous'), array('pointer' => $prev), $this->allowCaching);
			$markerArray['###LINK_PREV###'] = $this->local_cObj->stdWrap($prev_link, $this->conf['pageBrowser.']['previous_stdWrap.']);
		} else {
			$markerArray['###LINK_PREV###'] = '';
		}
		$firstPage = 0;
		$lastPage = $pages = ceil($newsCount / $this->config['limit']);
		$actualPage = floor($begin_at / $this->config['limit']);
		if(ceil($actualPage - $this->internal['maxPages']/2) > 0){
			$firstPage=ceil($actualPage - $this->internal['maxPages']/2);
			$addLast=0;
		}else{
			$firstPage=0;
			$addLast=floor(($this->internal['maxPages']/2)-$actualPage);
		}
		if(ceil($actualPage + $this->internal['maxPages']/2) <= $pages){
			$lastPage=ceil($actualPage + $this->internal['maxPages']/2) > 0 ? ceil($actualPage + $this->internal['maxPages']/2) : 0;
			$subFirst=0;
		} else{
			$lastPage=$pages;
			$subFirst=ceil($this->internal['maxPages']/2-($pages-$actualPage));
		}
		$firstPage=($firstPage-$subFirst)>0?($firstPage-$subFirst):$firstPage;
		$lastPage=($lastPage+$addLast)<=$pages?($lastPage+$addLast):$pages;

		for ($i = $firstPage ; $i < $lastPage; $i++) {
			if (($begin_at >= $i * $this->config['limit']) && ($begin_at < $i * $this->config['limit'] + $this->config['limit'])) {
				$item = ($this->conf['pageBrowser.']['showPBrowserText']?$this->pi_getLL('pi_list_browseresults_page', 'Page'):'') . (string)($i + 1);
				$markerArray['###PAGES###'] .= $this->local_cObj->stdWrap($item, $this->conf['pageBrowser.']['activepage_stdWrap.']) . ' ';
			} else {
				$item = ($this->conf['pageBrowser.']['showPBrowserText']?$this->pi_getLL('pi_list_browseresults_page', 'Page'):'') . (string)($i + 1);
				$link = $this->pi_linkTP_keepPIvars($this->local_cObj->stdWrap($item, $this->conf['pageBrowser.']['pagelink_stdWrap.']) , array('pointer' => $i), $this->allowCaching) . ' ';
				$markerArray['###PAGES###'] .= $this->local_cObj->stdWrap($link, $this->conf['pageBrowser.']['page_stdWrap.']);
			}
		}
		$ezd_at = ($begin_at + $this->config['limit']);
		if ($this->conf['pageBrowser.']['showResultCount']) {
			$markerArray['###RESULT_COUNT###'] = ($this->internal['res_count'] ?
			sprintf(
					str_replace('###SPAN_BEGIN###','<span'.$this->pi_classParam('browsebox-strong').'>',$this->pi_getLL('pi_list_browseresults_displays','Displaying results ###SPAN_BEGIN###%s to %s</span> out of ###SPAN_BEGIN###%s</span>')),
					$this->internal['res_count'] > 0 ? ($begin_at+1) : 0,
					min(array($this->internal['res_count'],$end_at )),
					$this->internal['res_count']

				) :
				$this->pi_getLL('pi_list_browseresults_noResults','Sorry, no items were found.'));
		}
		else {
			$markerArray['###RESULT_COUNT###'] = '';
		}
		return $markerArray;

	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3uk_dataview/pi1/class.tx_t3ukdataview_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3uk_dataview/pi1/class.tx_t3ukdataview_pi1.php']);
}

?>