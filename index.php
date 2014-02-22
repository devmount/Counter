<?php

/**
 * Plugin:   Counter
 * @author:  HPdesigner (hpdesigner[at]web[dot]de)
 * @version: v1.1.2013-09-19
 * @license: GPL
 * @see:     Now faith is being sure of what we hope for and certain of what we do not see.
 * 			 — The Bible
 *
 * Plugin created by DEVMOUNT
 * www.devmount.de
 *
**/

if(!defined('IS_CMS')) die();

class Counter extends Plugin {

	public $admin_lang;
	private $cms_lang;

	function getContent($value) {

		global $CMS_CONF;
		global $lang_counter;

		$this->cms_lang = new Language(PLUGIN_DIR_REL.'Counter/sprachen/cms_language_'.$CMS_CONF->get('cmslanguage').'.txt');
		
		// initialize values
		$count 			= 0;
		$time 			= time();
		$ip 			= getenv(REMOTE_ADDR);
		$filename 		= 'plugins/Counter/counterdb.txt';
		$linearray 		= file($filename);
		$current_date 	= date('d.m.y');
		$setdate 		= 0;
		$uhrzeit 		= date('H:i:s');
		$countgueltig 	= $this->settings->get('aufenthalt');	// Aufenthaltszeit (in sec)
		$reload 		= $this->settings->get('reload');		// Reloadsperre (in sec)
		$olddate 		= $this->settings->get('resetdatum');   // Resetdatum
		$max 			= 1;                           			// Rekord Initialisierung
		$average 		= 0;									// Durchschnitt
		$tstamp  		= mktime(0, 0, 0, date('m'), date('d')-1, date('Y'));
		$datum_gestern 	= date('Y-m-d', $tstamp); 
		$vorhanden 		= 0;

		// check if IP exists
		foreach($linearray as $sperre) {
			$arraysp = explode('#',$sperre);
			if($ip == trim($arraysp[1]) && $arraysp[0] > $time - $reload) $vorhanden = 1;
		}

		// get day and total number
		foreach($linearray as $line) {
			$line = explode('#',$line);
			if($line[0]=='datum' && trim($line[1]) != $current_date) $setdate = 1;
			if($vorhanden==1) {
				if($line[0] == 'heute' && $setdate == 0) $heute = trim($line[1]);
				if($line[0] == 'heute' && $setdate == 1) { 
					$heute = 1; 
					$gestern = trim($line[1]); 
				}
				if($line[0] == 'gesamt') $gesamt = trim($line[1]);
				if($line[0] == 'gestern' && $setdate == 0) $gestern = trim($line[1]);
			} else {
				if($line[0]=='heute' && $setdate == 0) $heute = trim($line[1]) + 1;
				if($line[0]=='heute' && $setdate == 1) { 
					$heute = 1; 
					$gestern=trim($line[1]); 
				}
				if($line[0]=='gestern' && $setdate == 0) $gestern = trim($line[1]);
				if($line[0]=='gesamt') $gesamt = trim($line[1]) + 1;
			}
			if ($line[0] == 'max') $max = trim($line[1]);
		}

		// initialize counts
		if ($heute == '') $heute = 0;
		if ($gestern == '') $gestern = 0;
		if ($max == '') $max = 0;
		if ($gesamt == '') $gesamt = 0;

		// build maximum
		if ($heute > $max) $max = $heute;

		// write day, total, maximal counts
		$contenttowrite = '';
		$contenttowrite .= 'datum'.'#'.$current_date."\n";
		$contenttowrite .= 'heute'.'#'.$heute."\n";
		$contenttowrite .= 'gestern'.'#'.$gestern."\n";
		$contenttowrite .= 'gesamt'.'#'.$gesamt."\n";
		$contenttowrite .= 'max'.'#'.$max."\n";
		$contenttowrite .= $time.'#'.$ip."\n";;
		$dbfile = fopen($filename , 'w');
		if (flock($dbfile, LOCK_EX)) { 				// exclusive lock
			fwrite ($dbfile, $contenttowrite);
			flock($dbfile, LOCK_UN);				// free lock
		}
		fclose($dbfile);

		// write online count
		$dbfile = fopen($filename , 'a');
		foreach($linearray as $useronline) {
			$useronlinearray = explode('#',$useronline);
			if($useronlinearray[0] > $time - $countgueltig && $ip != rtrim($useronlinearray[1])) {
				if (flock($dbfile, LOCK_EX)) { 		// exclusive lock
					fwrite ($dbfile,$useronline);
					flock($dbfile, LOCK_UN);		// free lock
				}
			}
		}
		fclose($dbfile);

		// evaluate average
		$verstrichene_tage = bcdiv((strtotime(date($datum_gestern)) - strtotime($olddate)), 86400, 0);
		if($verstrichene_tage > 0) $average = round((($gesamt - $heute)/$verstrichene_tage), 1); 
		else $average = 0;

		// get online count
		$werte = file($filename);
		$count = count($werte)-5;

		// load template
		$conf_template	= $this->settings->get('template');
				
		// write output
		$online 	= '';
		$today 		= '';
		$yesterday 	= '';
		$maximum 	= '';
		$average 	= '';
		$total 		= '';

		$online 	= $this->cms_lang->getLanguageValue('count_online').' '.$count;
		$today 		= $this->cms_lang->getLanguageValue('count_heute').' '.$heute;
		$yesterday 	= $this->cms_lang->getLanguageValue('count_gestern').' '.$gestern;
		$maximum 	= $this->cms_lang->getLanguageValue('count_rekord').' '.$max;
		$average 	= $this->cms_lang->getLanguageValue('count_schnitt').' '.$average;
		$total 		= $this->cms_lang->getLanguageValue('count_gesamt').' '.$gesamt;

		$counter = '';

		if (isset($conf_template)) {
			$counter .= $conf_template;
			$counter = str_replace(
				array("{ONLINE}", "{TODAY}", "{YESTERDAY}", "{MAXIMUM}", "{AVERAGE}", "{TOTAL}", "{DATE}"),
				array($online, $today, $yesterday, $maximum, $average, $total, $olddate),
				$counter
			);
		} else $counter .= $online . ' ' . $today . ' ' . $yesterday . ' ' . $maximum . ' ' . $average . ' ' . $total . ' ' . $olddate;
				
		return $counter;

	} // function getContent
	
	

	function getConfig() {
		
		$config = array();
		
		// Resetdatum
		$config['resetdatum']  = array(
			'type' => 'text',
			'description' => $this->admin_lang->getLanguageValue('config_resetdatum'),
			'maxlength' => '50',
			'size' => '15'
		);
		
		// Gültige Aufenthaltsdauer
		$config['aufenthalt']  = array(
			'type' => 'text',
			'description' => $this->admin_lang->getLanguageValue('config_aufenthalt'),
			'maxlength' => '50',
			'size' => '3',
			'regex' => "/^[0-9]{0,6}$/",
			'regex_error' => $this->admin_lang->getLanguageValue('config_aufenthalt_error')
		);

		// Reloadsperre
		$config['reload']  = array(
			'type' => 'text',
			'description' => $this->admin_lang->getLanguageValue('config_reload'),
			'maxlength' => '50',
			'size' => '3',
			'regex' => "/^[0-9]{0,6}$/",
			'regex_error' => $this->admin_lang->getLanguageValue('config_reload_error')
		);       
			
		// Template
		$config['template']  = array(
			"type" => "textarea",
			"rows" => "5",
			"description" => $this->admin_lang->getLanguageValue("config_template"),
			'template' => '{template_description}<br />{template_textarea}'
		);
		
		
		// Rückgabe
		return $config;

	} // function getConfig    
	
	
	function getInfo() {
		global $ADMIN_CONF;

		$this->admin_lang = new Language(PLUGIN_DIR_REL.'Counter/sprachen/admin_language_'.$ADMIN_CONF->get('language').'.txt');
				
		$info = array(
			// Plugin-Name + Version
			'<b>Counter</b> v1.1.2013-09-19',
			// moziloCMS-Version
			'2.0',
			// Kurzbeschreibung nur <span> und <br /> sind erlaubt
			$this->admin_lang->getLanguageValue('config_description'), 
			// Name des Autors
			'HPdesigner',
			// Docu-URL
			'http://www.devmount.de/Develop/Mozilo%20Plugins/Counter.html',
			// Platzhalter für die Selectbox in der Editieransicht 
			// - ist das Array leer, erscheint das Plugin nicht in der Selectbox
			array(
				'{Counter}' => $this->admin_lang->getLanguageValue('toolbar_platzhalter')
			)
		);
		// Rückgabe der Infos.
		return $info;
		
	} // function getInfo

}

?>