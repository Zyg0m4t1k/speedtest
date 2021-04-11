<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class speedtest extends eqLogic {
	
	public static $_widgetPossibility = array('custom' => true);

	public static function dependancy_info() {
		$return = array();
		$return['log'] = __CLASS__ . '_update';
		$return['progress_file'] = jeedom::getTmpFolder(__CLASS__) . '/dependance';
		$return['state'] = 'nok';
		try {
			$pip = com_shell::execute(system::getCmdSudo() . 'which pip');
		} catch (Exception $exc) {
			log::add('switchbot', 'debug', 'Impossible de trouver pip ' . $exc);
			$return['state'] = 'nok';
			return $return;
		}	
		$pip = rtrim($pip);	
		try {
			$list = com_shell::execute(system::getCmdSudo() . $pip . ' show speedtest-cli'); 
			$lines = explode(PHP_EOL, $list);
			foreach ($lines as $line) {
				if ($line == 'Version: 2.1.3') {
					$return['state'] = 'ok';		
				}
			}		
			return $return;			
		} catch(Exception $exc) {
			return $return;
		}
	}
	
    public static function dependancy_install() {
        log::remove(__CLASS__ . '_update');
		return array('script' => __DIR__ . '/../../resources/install.sh ' . jeedom::getTmpFolder(__CLASS__) . '/dependance','log' => log::getPathToLog(__CLASS__ . '_update'));
    }
	
	public static function cronHourly() {
		$getIp = config::byKey('checkIp', 'speedtest', 0);
		if ($getIp == 1 && config::byKey('ipkey', 'speedtest') != '' ) {
			$ip = self::getIp();
			if ($ip != config::byKey('ipkey', 'speedtest')) {
				log::add('speedtest', 'error', 'Changement d\'ip :' . $ip);
				config::save('ipkey',$ip,'speedtest') ;
			} 
		}
	}
	
	public function getIp() {
		$cmds = array('ipinfo.io/ip','ipecho.net/plain','ifconfig.me');
		$check = '';
		foreach($cmds as $cmd) {
			$ip = 'sudo curl ' . $cmd;
			$ip = exec($ip);
			if (filter_var($ip, FILTER_VALIDATE_IP)) {
				return $ip;
				break;
			} else {
				$check = false;
			}
		}
		if(!$check) {
			log::add('speedtest', 'error', '!!! Impossible de détecter l\'adresse IP !!!');	
			return false;	
		}
	}
	
	public function getInfo($_options=false) {
		
		if ($_options != NULL) {
			$eq = speedtest::byId($_options['speedtest_id']);	
		} else {
			$eq = speedtest::byId($this->getId());	
		}
		$changed = false;	
		$cmd = 'sudo speedtest --share';
		$cmd = exec($cmd,$results);
		log::add('speedtest','debug','############################################');
		log::add('speedtest','debug','############################################');
		log::add('speedtest','debug',print_r($results,true));
		log::add('speedtest','debug','count: ' . count($results));
		if (count($results) == 2) {
			log::add('speedtest','debug','status 0');
			$eq->checkAndUpdateCmd('status', 0);
			$eq->checkAndUpdateCmd('speeddl', 0);
			$eq->checkAndUpdateCmd('speedul', 0);
			$eq->checkAndUpdateCmd('ping', 0);
			$eq->setConfiguration('image',$img);
			$eq->save();
			$eq->refreshWidget();
			return;			
						
		} else {
			log::add('speedtest','debug','status 1');
			$changed = $eq->checkAndUpdateCmd('status', 1) || $changed;
		}
		foreach ($results as $result) {
			log::add('speedtest','debug','info : ' . $result);
				if ($result[0] == '.') {
					log::add('speedtest','debug','suppresion du : ' .$result[0]);
					$result = substr($result,1);
				}
			if ((strstr($result, "Download:"))) {
				$downloads = str_replace("Download: ", "" , $result);
				$download = explode(' ' , $downloads);			
				$changed = $eq->checkAndUpdateCmd('speeddl', $download[0]) || $changed;
				log::add('speedtest','debug','dl : ' . $download[0]);
			} elseif(((strstr($result, "Upload:")))) {
				$uploads = str_replace("Upload: ", "" , $result);
				$upload = explode(' ' , $uploads);				
				$changed = $eq->checkAndUpdateCmd('speedul', $upload[0]) || $changed;
				log::add('speedtest','debug','ul : ' . $upload[0]);				
			} elseif (((strstr($result, "Share results:")))) {
				$img = str_replace("Share results: ", "" , $result);
				$eq->setConfiguration('image',$img);
				$eq->save();
			} elseif (preg_match_all('#Hosted by .*: (.*?) ms#',$result,$ping)) {
				log::add('speedtest','debug','ping : ' . $ping[1][0]);
				$changed = $eq->checkAndUpdateCmd('ping', $ping[1][0]) || $changed;
			} 
		};	
		log::add('speedtest','debug','############################################');
		log::add('speedtest','debug','############################################');
		if ($changed) {
			$eq->refreshWidget();
		}		
	}
	
    public function preUpdate() {
        if ($this->getConfiguration('autAlt', 0) == 1 && $this->getConfiguration('autAltBeta', 0) == 1) {
            throw new Exception(__('Il ne faut sélectionner qu\'un widget', __FILE__));
        }
	}  
	
//	public function preSave() {
//		$arch = com_shell::execute(system::getCmdSudo() . 'dpkg --print-architecture'); 
//		switch ($arch) {
//			case 'i386':
//				$this->setConfiguration('arch', $arch);
//				break;
//			case 'x86_64':
//				$this->setConfiguration('arch', $arch);
//				break;
//			case 'aarch64':
//				$this->setConfiguration('arch', $arch);
//				break;
//			case 'arm':
//				$this->setConfiguration('arch', $arch);
//				break;
//			case 'armhf':
//				$this->setConfiguration('arch', $arch);
//				break;
//			case strpos('64', $arch) >= 0:
//				$this->setConfiguration('arch', 'aarch64');
//				break;
//			default:
//				log::add('speedtest','debug','Architecture non trouvée : ' . $arch);
//		}
//	}
//	
	public function postUpdate() {
		$speedDl = $this->getCmd(null, 'speeddl');
		if (!is_object($speedDl)) {
			$speedDl = new speedtestCmd();
			$speedDl->setName(__('Download', __FILE__));
		}
		$speedDl->setLogicalId('speeddl');
		$speedDl->setEqLogic_id($this->getId());
		$speedDl->setType('info');
		$speedDl->setSubType('numeric');
		$speedDl->setUnite('Mbit/s');
		$speedDl->save(); 			
		
		$speedul = $this->getCmd(null, 'speedul');
		if (!is_object($speedul)) {
			$speedul = new speedtestCmd();
			$speedul->setName(__('Upload', __FILE__));					
		}
		$speedul->setLogicalId('speedul');
		$speedul->setEqLogic_id($this->getId());
		$speedul->setType('info');
		$speedul->setSubType('numeric');
		$speedul->setUnite('Mbit/s');
		$speedul->save(); 
		
		$ping = $this->getCmd(null, 'ping');
		if (!is_object($ping)) {
			$ping = new speedtestCmd();
			$ping->setName(__('Ping', __FILE__));
		}
		$ping->setLogicalId('ping');
		$ping->setEqLogic_id($this->getId());
		$ping->setType('info');
		$ping->setSubType('numeric');
		$ping->setUnite('ms');
		$ping->save(); 
		
		$status = $this->getCmd(null, 'status');
		if (!is_object($status)) {
			$status = new speedtestCmd();
			$status->setName(__('Etat', __FILE__));
		}
		$status->setLogicalId('status');
		$status->setEqLogic_id($this->getId());
		$status->setType('info');
		$status->setSubType('binary');	
		$status->save(); 		
		
		$refresh = $this->getCmd(null, 'refresh');
		if (!is_object($refresh)) {
			$refresh = new speedtestCmd();
			$refresh->setName(__('Rafraichir', __FILE__));
		}
		$refresh->setLogicalId('refresh');
		$refresh->setEqLogic_id($this->getId());
		$refresh->setType('action');
		$refresh->setSubType('other');
		$refresh->save(); 

		if ($this->getIsEnable() == 1 && $this->getConfiguration('autCron', 0) == 1 ) {
			$cron = cron::byClassAndFunction('speedtest', 'getInfo', array('speedtest_id' => intval($this->getId()))); 
			if (!is_object($cron)) {
				$cron = new cron();
				$cron->setClass('speedtest');
				$cron->setFunction('getInfo');
				$cron->setOption(array('speedtest_id' => intval($this->getId())));
			}
			$cron->setSchedule($this->getConfiguration('refreshCron'));
			$cron->save();			
		} else {
			$cron = cron::byClassAndFunction('speedtest', 'getInfo', array('speedtest_id' => intval($this->getId())));  
			if (is_object($cron)) {
				$cron->remove();
			}
		}
    }

	public function preRemove() {
	   $cron = cron::byClassAndFunction('speedtest', 'getInfo', array('speedtest_id' => intval($this->getId())));  
	   if (is_object($cron)) {
		   $cron->remove();
	   }		
	}
	
	public function toHtml($_version = 'dashboard') {
		$cmd = $this->getCmd(null, 'status');
		$replace = $this->preToHtml($_version);
		if (!is_array($replace)) {
			return $replace;
		}
		if ($cmd->execCmd() == 0) {
			$replace['#image#'] = 'plugins/speedtest/doc/images/error.png';
		}		
		$version = jeedom::versionAlias($_version);		
		if ($this->getConfiguration('autAlt', 0) == 1) {			
				$replace['#image#'] = $this->getConfiguration('image');
				return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'defaut', 'speedtest')));
		} elseif ($this->getConfiguration('autAltBeta', 0) == 1) {
			  $arr = parse_url($this->getConfiguration('image'));
			  $url = 'https://beta.speedtest.net' . $arr['path'];
			  $replace['#image#'] = $url;		  		  
			  return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'defaut', 'speedtest')));			
		}else {
			  return parent::toHtml($_version);		
		}
	}
}

class speedtestCmd extends cmd {
	
	public function dontRemoveCmd() {
		return true;
	}	
	
    public function execute($_options = array()) {	
		$server = speedtest::byId($this->getEqLogic_id());
		if ($this->getLogicalId() == 'refresh') {
			$server->getInfo();
		}		
    }
}

?>
