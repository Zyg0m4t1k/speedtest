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
require_once __DIR__ . '/../../../../core/php/core.inc.php';

class speedtest extends eqLogic {
	
	public static $_widgetPossibility = array('custom' => true);
	
	public static function cron() {
		foreach ( self::byType( 'speedtest', true) as $server ) {
			$autorefresh = $server->getConfiguration( 'refreshCron' );
			if ( $autorefresh != '' ) {
				try {
					$c = new Cron\ CronExpression( $autorefresh, new Cron\ FieldFactory );
					if ( $c->isDue() ) {
						try {
							$server->updateInfo();
						} catch ( Exception $exc ) {
							log::add( 'speedtest', 'error', __( 'Erreur pour ', __FILE__ ) . $server->getHumanName() . ' : ' . $exc->getMessage() );
						}
					}
				} catch ( Exception $exc ) {
					log::add( __CLASS__, 'error', __( 'Expression cron non valide pour ', __FILE__ ) . $server->getHumanName() . ' : ' . $server );
				}
			}
		}
	}	
	
	public static function dependancy_info() {
		$return = array();
		$return['log'] = __CLASS__ . '_update';
		$return['progress_file'] = jeedom::getTmpFolder(__CLASS__) . '/dependance';
		$return['state'] = 'nok';
		try {
			$pip = com_shell::execute(system::getCmdSudo() . 'which pip');
		} catch (Exception $exc) {
			log::add(__CLASS__, 'debug', 'Impossible de trouver pip ' . $exc);
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
				log::add(__CLASS__, 'error', 'Changement d\'ip :' . $ip);
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
				config::save('ipkey',$ip,__CLASS__);
				return $ip;
				break;
			} else {
				$check = false;
			}
		}
		if(!$check) {
			log::add(__CLASS__, 'error', '!!! Impossible de détecter l\'adresse IP !!!');	
			return false;	
		}
	}
	
	public function getInfoOkla() { 
		log::add(__CLASS__,'debug','############################################');
		log::add(__CLASS__,'debug','############################################');		
		$cmd = $this->createCommand();
		if(!$cmd) {
			log::add(__CLASS__, 'debug', '!!! Le fichier executable n\'existe pas !!!');
			return;
		}
		log::add(__CLASS__, 'debug', 'Cmd : ' . $cmd );
		try {
			//$result = com_shell::execute(system::getCmdSudo() . $cmd);
			exec('sudo ' . $cmd, $result, $err);
			log::add(__CLASS__,'debug', ' result '  . print_r($result,true));
			if($err != 0) {
				log::add(__CLASS__,'debug', ' Lancement de la commande impossible ' . $err);
				$this->checkAndUpdateCmd('status', false);
				$this->checkAndUpdateCmd('speeddl', 0);
				$this->checkAndUpdateCmd('speedul', 0);
				$this->checkAndUpdateCmd('ping', 0);
				$this->setConfiguration('image','');				
				return;
			}
			if(preg_match('#Latency:\s{1,}([0-9]*[.]?[0-9]+)\s{1,}(.*)\s{1,}\(#',$result[5],$m)) {
				$ping = $m[1];
			}			
			if(preg_match('#Download:\s{1,}([0-9]*[.]?[0-9]+)\s{1,}(.*)\s{1,}\(#',$result[6],$m)) {
				$download = $m[1];
			}
			if(preg_match('#Upload:\s{1,}([0-9]*[.]?[0-9]+)\s{1,}(.*)\s{1,}\(#',$result[7],$m)) {
				$upload = $m[1];
			}			
			if(preg_match('#Result URL:\s{1,}(.*)#',$result[9],$m)) {
				$img = trim($m[1]) . '.png';
			}			

			$this->checkAndUpdateCmd('status', true);
			$this->checkAndUpdateCmd('speeddl', $download);
			$this->checkAndUpdateCmd('speedul', $upload);
			$this->checkAndUpdateCmd('ping', $ping);
			$this->setConfiguration('image', $img);			
		} catch (Exception $exc) {
			log::add(__CLASS__,'debug','getInfoOkla error ' . $exc);
			$this->checkAndUpdateCmd('status', false);
			$this->checkAndUpdateCmd('speeddl', 0);
			$this->checkAndUpdateCmd('speedul', 0);
			$this->checkAndUpdateCmd('ping', 0);
			$this->setConfiguration('image','');
		}
		log::add(__CLASS__,'debug','############################################');
		log::add(__CLASS__,'debug','############################################');		
		$this->save();
		$this->refreshWidget();		
		return;
	}
	
	public function createCommand($local = true) {
		if(!$local) {
			$cmd = 'sudo speedtest --share ';
			if ($this->getConfiguration('server_id', '') != '') {
				$cmd .= ' --server ' . $this->getConfiguration('server_id');
			}
			return $cmd;			
		} else {
			$dir= __DIR__ . '/../../3rdparty/' . $this->getConfiguration('arch');
			if(!is_dir($dir)) {
				return false;
			}
			$cmd = $dir . '/speedtest --accept-license';
			$cmd .= ' -u ' . $this->getConfiguration('unit');
			if ($this->getConfiguration('server_id', '') != '') {
				$cmd .= ' -s ' . $this->getConfiguration('server_id');
			}
			return $cmd;			
		}
	}	
	
	public function getInfo() {
		$changed = false;	
		$cmd = $this->createCommand(false);
		log::add(__CLASS__,'debug','cmd : ' . $cmd);
		$cmd = exec($cmd,$results);
		log::add(__CLASS__,'debug','############################################');
		log::add(__CLASS__,'debug','############################################');
		log::add(__CLASS__,'debug',print_r($results,true));
		log::add(__CLASS__,'debug','count: ' . count($results));
		if (count($results) == 2) {
			log::add(__CLASS__,'debug','status 0');
			$this->checkAndUpdateCmd('status', 0);
			$this->checkAndUpdateCmd('speeddl', 0);
			$this->checkAndUpdateCmd('speedul', 0);
			$this->checkAndUpdateCmd('ping', 0);
			$this->setConfiguration('image',$img);
			$this->save();
			$this->refreshWidget();
			return;			
						
		} else {
			log::add(__CLASS__,'debug','status 1');
			$changed = $this->checkAndUpdateCmd('status', 1) || $changed;
		}
		foreach ($results as $result) {
			log::add(__CLASS__,'debug','info : ' . $result);
				if ($result[0] == '.') {
					log::add(__CLASS__,'debug','suppresion du : ' .$result[0]);
					$result = substr($result,1);
				}
			if ((strstr($result, "Download:"))) {
				$downloads = str_replace("Download: ", "" , $result);
				$download = explode(' ' , $downloads);			
				$changed = $this->checkAndUpdateCmd('speeddl', $download[0]) || $changed;
				log::add(__CLASS__,'debug','dl : ' . $download[0]);
			} elseif(((strstr($result, "Upload:")))) {
				$uploads = str_replace("Upload: ", "" , $result);
				$upload = explode(' ' , $uploads);				
				$changed = $this->checkAndUpdateCmd('speedul', $upload[0]) || $changed;
				log::add(__CLASS__,'debug','ul : ' . $upload[0]);				
			} elseif (((strstr($result, "Share results:")))) {
				$img = str_replace("Share results: ", "" , $result);
				$this->setConfiguration('image',$img);
				$this->save();
			} elseif (preg_match_all('#Hosted by .*: (.*?) ms#',$result,$ping)) {
				log::add(__CLASS__,'debug','ping : ' . $ping[1][0]);
				$changed = $this->checkAndUpdateCmd('ping', $ping[1][0]) || $changed;
			} 
		};	
		log::add(__CLASS__,'debug','############################################');
		log::add(__CLASS__,'debug','############################################');
		if ($changed) {
			$this->refreshWidget();
		}		
	}
	
	public function updateInfo() {
		$this->getConfiguration('useArch', 0) == 1 ? $this->getInfoOkla() : $this->getInfo();
	}
	
	public function getArch() {
		try {
			$arch = com_shell::execute(system::getCmdSudo() . 'dpkg --print-architecture');
			config::save('archOrigin',$arch,__CLASS__);
			switch ($arch) {
				case 'i386':
				case 'x86_64':
				case 'arm':
				case 'armhf':
					config::save('arch',$arch,__CLASS__);
					return $arch;
					break;
				case 'aarch64':
				case strpos('64', $arch) >= 0:
					config::save('arch','aarch64',__CLASS__) ;
					return 'aarch64';
					break;
				default:
					log::add(__CLASS__,'error','Architecture non trouvée : ' . $arch);
					return 'nok';
			}			
		} catch (Exception $except) {
			log::add( __CLASS__, 'error', __( 'Erreur Architecture : ', __FILE__ ) . $except );
		}
	}
	
	public function preSave() {
		$arch = $this->getArch();
		$this->setConfiguration('arch',$arch);
	}
	
	public function postAjax() {
		
		$speedDl = $this->getCmd(null, 'speeddl');
		if (!is_object($speedDl)) {
			$speedDl = new speedtestCmd();
			$speedDl->setName(__('Download', __FILE__));
		}
		$speedDl->setLogicalId('speeddl');
		$speedDl->setEqLogic_id($this->getId());
		$speedDl->setType('info');
		$speedDl->setSubType('numeric');
		if( $this->getConfiguration('useArch', 0) == 1) {
			$speedDl->setUnite($this->getConfiguration('unit'));
		} else {
			$speedDl->setUnite('Mbps');
		}		
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
		if( $this->getConfiguration('useArch', 0) == 1) {
			$speedul->setUnite($this->getConfiguration('unit'));
		} else {
			$speedul->setUnite('Mbps');
		}
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

		$cron = cron::byClassAndFunction('speedtest', 'getInfo', array('speedtest_id' => intval($this->getId())));  
		if (is_object($cron)) {
			$cron->remove();
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
		} else {
			  return parent::toHtml($_version);		
		}
	}
}

class speedtestCmd extends cmd {
	
	public function dontRemoveCmd() {
		return true;
	}	
	
    public function execute($_options = array()) {	
		$server = $this->getEqLogic();
		if ($this->getLogicalId() == 'refresh') {
			$server->updateInfo();
		}		
    }
}

?>
