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

    public static function pull($_option) {

    }
	
	
	
	public static function dependancy_info() {
		$return = array();
		$return['log'] = __CLASS__ . '_update';
		$return['progress_file'] = '/tmp/dependancy_speedtest_in_progress';
		if (exec('which speedtest-cli | wc -l') != 0) {
			$return['state'] = 'ok';
		} else {
			$return['state'] = 'nok';
		}
		return $return;
	}
	
	public static function dependancy_install() {
		log::remove(__CLASS__ . '_update');
		$cmd = 'sudo /bin/bash ' .dirname(__FILE__) . '/../../resources/install.sh';
		$cmd .= ' >> ' . log::getPathToLog(__CLASS__ . '_update') . ' 2>&1 &';
		exec($cmd);		
		
	}
	
	public function getInfo($_options=false) {
		
			$cmd = 'speedtest-cli --simple --secure --json';
			$cmd = exec($cmd,$results);
			$pings = str_replace("Ping: ", "" , $results[0]);
			$ping = explode(' ' , $pings);
			$result['Ping'] =  $ping[0];
			
			$downloads = str_replace("Download: ", "" , $results[1]);
			$download = explode(' ' , $downloads);
			$result['Download'] = $download[0];	
			
			$uploads = str_replace("Upload: ", "" , $results[2]);
			$upload	= explode(' ' , $uploads);
			$result['Upload'] = $upload[0];	
			
			if ($_options != NULL) {
				log::add('speedtest','debug', ' id: ' . print_r($_options,true) );
				$eq = speedtest::byId($_options['speedtest_id']);	
			} else {
				log::add('speedtest','debug', ' Non id: ');
				$eq = speedtest::byId($this->getEqLogic_id());	
			}
		
		
			$changed = $eq->checkAndUpdateCmd('speedul', $result['Upload']) || $changed;
			$changed = $eq->checkAndUpdateCmd('speeddl', $result['Download']) || $changed;
			$changed = $eq->checkAndUpdateCmd('ping', $result['Ping']) || $changed;	
	}
	
	


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
	    $speedDl->setTemplate('dashboard', 'line');
		$speedDl->setTemplate('mobile', 'line');		
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
	    $speedul->setTemplate('dashboard', 'line');
		$speedul->setTemplate('mobile', 'line');		
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
	    $ping->setTemplate('dashboard', 'line');
		$ping->setTemplate('mobile', 'line');		
		$ping->setUnite('ms');
		$ping->save(); 
		
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
	

	

	
	public function dontRemoveCmd() {
		return true;
	}	
	

}

class speedtestCmd extends cmd {
	
	
    public function execute($_options = array()) {
		if ($this->getLogicalId() == 'refresh') {
			speedtest::getInfo();
		}		
		 
    }
}

?>
