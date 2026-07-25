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

	public static function getSpeedtestBin() {
		$venv = __DIR__ . '/../../resources/venv/bin/speedtest';
		if (@is_file($venv) && @is_executable($venv)) {
			return $venv;
		}
		return '/usr/local/bin/speedtest';
	}

	public static function dependancy_info() {
		$return = array();
		$return['log'] = log::getPathToLog(__CLASS__ . '_update');
		$return['progress_file'] = jeedom::getTmpFolder(__CLASS__) . '/dependance';

		if (file_exists($return['progress_file'])) {
			$return['state'] = 'in_progress';
			return $return;
		}

		$venvBin = __DIR__ . '/../../resources/venv/bin/speedtest';
		$ok = (is_file($venvBin) && is_executable($venvBin));

		if ($ok) {
			$out = array();
			$rc = 0;
			exec(escapeshellcmd($venvBin) . ' --version 2>&1', $out, $rc);
			$ok = ((int)$rc === 0);
		}

		$return['state'] = $ok ? 'ok' : 'nok';
		return $return;
	}

	public static function dependancy_install() {
		log::remove(__CLASS__ . '_update');
		return array(
			'script' => dirname(__FILE__) . '/../../resources/install.sh ' . jeedom::getTmpFolder(__CLASS__) . '/dependance',
			'log' => log::getPathToLog(__CLASS__ . '_update')
		);
	}

	public static function cronHourly() {
		$getIp = config::byKey('checkIp', 'speedtest', 0);
		if ($getIp == 1 && config::byKey('ipkey', 'speedtest') != '') {
			$ip = self::getIp();
			if ($ip != config::byKey('ipkey', 'speedtest')) {
				log::add('speedtest', 'error', 'Changement d\'ip :' . $ip);
				config::save('ipkey', $ip, 'speedtest');
			}
		}
	}

	public static function getIp() {
		$urls = array('ipinfo.io/ip', 'ipecho.net/plain', 'ifconfig.me');
		foreach ($urls as $url) {
			$out = array();
			$rc = 0;
			exec('curl -s --max-time 10 ' . escapeshellarg($url) . ' 2>/dev/null', $out, $rc);
			$ip = trim(implode('', $out));
			if ($rc === 0 && filter_var($ip, FILTER_VALIDATE_IP)) {
				return $ip;
			}
		}
		log::add('speedtest', 'error', '!!! Impossible de détecter l\'adresse IP !!!');
		return false;
	}

	/*     * *************** Mesure jitter et perte de paquets *************** */

	public static function measurePingStats($_host = '8.8.8.8', $_count = 20) {
		$out = array();
		$rc = 0;
		exec('ping -c ' . intval($_count) . ' -W 2 ' . escapeshellarg($_host) . ' 2>&1', $out, $rc);
		$result = array('jitter' => 0, 'packet_loss' => 0);

		$allRtt = array();
		foreach ($out as $line) {
			// Parse "time=12.3 ms"
			if (preg_match('/time[=<]([\d.]+)\s*ms/', $line, $m)) {
				$allRtt[] = (float)$m[1];
			}
			// Parse "3 packets transmitted, 3 received, 0% packet loss"
			if (preg_match('/([\d.]+)%\s*packet loss/', $line, $m)) {
				$result['packet_loss'] = (float)$m[1];
			}
		}

		// Jitter = moyenne des différences absolues entre RTT consécutifs
		if (count($allRtt) > 1) {
			$diffs = array();
			for ($i = 1; $i < count($allRtt); $i++) {
				$diffs[] = abs($allRtt[$i] - $allRtt[$i - 1]);
			}
			$result['jitter'] = round(array_sum($diffs) / count($diffs), 2);
		}

		return $result;
	}

	/*     * *************** Notation d'usage *************** */

	public static function computeScore($_download, $_upload, $_ping, $_jitter = 0, $_packetLoss = 0) {
		// Score sur 10 basé sur les métriques réseau
		// Download (40%), Upload (20%), Ping (20%), Jitter (10%), Packet Loss (10%)

		// Download score: 100 Mbps+ = 10, 50 = 8, 25 = 6, 10 = 4, 5 = 2
		$dlScore = min(10, $_download / 10);

		// Upload score: 50 Mbps+ = 10, 20 = 8, 10 = 5
		$ulScore = min(10, $_upload / 5);

		// Ping score: <10ms = 10, 20ms = 8, 50ms = 5, >100ms = 2
		if ($_ping <= 0) {
			$pingScore = 0;
		} elseif ($_ping <= 10) {
			$pingScore = 10;
		} elseif ($_ping <= 20) {
			$pingScore = 8;
		} elseif ($_ping <= 50) {
			$pingScore = 5;
		} elseif ($_ping <= 100) {
			$pingScore = 3;
		} else {
			$pingScore = 1;
		}

		// Jitter score: <2ms = 10, <5ms = 8, <10ms = 5, <30ms = 3
		if ($_jitter <= 2) {
			$jitterScore = 10;
		} elseif ($_jitter <= 5) {
			$jitterScore = 8;
		} elseif ($_jitter <= 10) {
			$jitterScore = 5;
		} elseif ($_jitter <= 30) {
			$jitterScore = 3;
		} else {
			$jitterScore = 1;
		}

		// Packet loss score: 0% = 10, <1% = 7, <3% = 4, >5% = 1
		if ($_packetLoss <= 0) {
			$plScore = 10;
		} elseif ($_packetLoss < 1) {
			$plScore = 7;
		} elseif ($_packetLoss < 3) {
			$plScore = 4;
		} else {
			$plScore = 1;
		}

		$score = ($dlScore * 0.4) + ($ulScore * 0.2) + ($pingScore * 0.2) + ($jitterScore * 0.1) + ($plScore * 0.1);
		return round(min(10, $score), 1);
	}

	public static function getUsageRating($_download, $_upload, $_ping) {
		// Notation textuelle par usage
		$ratings = array();

		// Navigation web
		if ($_download >= 5 && $_ping < 100) {
			$ratings[] = 'web:excellent';
		} elseif ($_download >= 1) {
			$ratings[] = 'web:bon';
		} else {
			$ratings[] = 'web:insuffisant';
		}

		// Streaming vidéo
		if ($_download >= 25) {
			$ratings[] = 'streaming:4K';
		} elseif ($_download >= 10) {
			$ratings[] = 'streaming:HD';
		} elseif ($_download >= 3) {
			$ratings[] = 'streaming:SD';
		} else {
			$ratings[] = 'streaming:insuffisant';
		}

		// Jeu en ligne
		if ($_ping < 20 && $_download >= 10) {
			$ratings[] = 'gaming:excellent';
		} elseif ($_ping < 50 && $_download >= 5) {
			$ratings[] = 'gaming:bon';
		} elseif ($_ping < 100) {
			$ratings[] = 'gaming:moyen';
		} else {
			$ratings[] = 'gaming:insuffisant';
		}

		// Visioconférence
		if ($_upload >= 3 && $_download >= 5 && $_ping < 50) {
			$ratings[] = 'visio:excellent';
		} elseif ($_upload >= 1 && $_download >= 2) {
			$ratings[] = 'visio:bon';
		} else {
			$ratings[] = 'visio:insuffisant';
		}

		return implode(' | ', $ratings);
	}

	/*     * *************** Collecte des données *************** */

	public function getInfo($_options = false) {
		if ($_options != null) {
			$eq = speedtest::byId($_options['speedtest_id']);
		} else {
			$eq = speedtest::byId($this->getId());
		}
		if (!is_object($eq)) {
			log::add('speedtest', 'error', 'Equipement introuvable');
			return;
		}

		$changed = false;
		$speedtestBin = self::getSpeedtestBin();

		// Construction de la commande avec options
		$cmdLine = escapeshellcmd($speedtestBin) . ' --json --share';
		$serverId = $eq->getConfiguration('server_id', '');
		if ($serverId != '') {
			$cmdLine .= ' --server ' . escapeshellarg($serverId);
		}

		log::add('speedtest', 'debug', '############ Lancement speedtest ############');
		log::add('speedtest', 'debug', 'Commande : ' . $cmdLine);

		$output = array();
		$rc = 0;
		exec($cmdLine . ' 2>&1', $output, $rc);
		$raw = implode('', $output);
		log::add('speedtest', 'debug', 'Sortie brute : ' . $raw);

		$data = json_decode($raw, true);

		if (!is_array($data) || !isset($data['download'])) {
			log::add('speedtest', 'error', 'Echec du speedtest (rc=' . $rc . ')');
			$eq->checkAndUpdateCmd('status', 0);
			$eq->checkAndUpdateCmd('speeddl', 0);
			$eq->checkAndUpdateCmd('speedul', 0);
			$eq->checkAndUpdateCmd('ping', 0);
			$eq->checkAndUpdateCmd('jitter', 0);
			$eq->checkAndUpdateCmd('packet_loss', 0);
			$eq->checkAndUpdateCmd('score', 0);
			$eq->refreshWidget();
			return;
		}

		// Conversion bits/s en Mbit/s
		$download = round($data['download'] / 1000000, 2);
		$upload = round($data['upload'] / 1000000, 2);
		$ping = round($data['ping'], 2);

		log::add('speedtest', 'debug', 'Download: ' . $download . ' Mbit/s');
		log::add('speedtest', 'debug', 'Upload: ' . $upload . ' Mbit/s');
		log::add('speedtest', 'debug', 'Ping: ' . $ping . ' ms');

		$changed = $eq->checkAndUpdateCmd('status', 1) || $changed;
		$changed = $eq->checkAndUpdateCmd('speeddl', $download) || $changed;
		$changed = $eq->checkAndUpdateCmd('speedul', $upload) || $changed;
		$changed = $eq->checkAndUpdateCmd('ping', $ping) || $changed;

		// Infos serveur
		if (isset($data['server'])) {
			$serverName = $data['server']['sponsor'] . ' - ' . $data['server']['name'];
			$serverLocation = $data['server']['name'] . ', ' . $data['server']['country'];
			$changed = $eq->checkAndUpdateCmd('server_name', $serverName) || $changed;
			log::add('speedtest', 'debug', 'Serveur: ' . $serverName . ' (' . $serverLocation . ')');
		}

		// ISP
		if (isset($data['client']['isp'])) {
			$changed = $eq->checkAndUpdateCmd('isp', $data['client']['isp']) || $changed;
			log::add('speedtest', 'debug', 'ISP: ' . $data['client']['isp']);
		}

		// Image de partage
		if (isset($data['share']) && $data['share'] != '') {
			$eq->setConfiguration('image', $data['share']);
			$eq->save();
		}

		// Jitter et perte de paquets via ping
		$pingHost = '8.8.8.8';
		if (isset($data['server']['host'])) {
			$hostParts = explode(':', $data['server']['host']);
			$pingHost = $hostParts[0];
		}
		log::add('speedtest', 'debug', 'Mesure jitter/packet_loss sur ' . $pingHost);
		$pingStats = self::measurePingStats($pingHost, 20);
		$changed = $eq->checkAndUpdateCmd('jitter', $pingStats['jitter']) || $changed;
		$changed = $eq->checkAndUpdateCmd('packet_loss', $pingStats['packet_loss']) || $changed;
		log::add('speedtest', 'debug', 'Jitter: ' . $pingStats['jitter'] . ' ms');
		log::add('speedtest', 'debug', 'Packet loss: ' . $pingStats['packet_loss'] . ' %');

		// Score global et notation d'usage
		$score = self::computeScore($download, $upload, $ping, $pingStats['jitter'], $pingStats['packet_loss']);
		$changed = $eq->checkAndUpdateCmd('score', $score) || $changed;
		log::add('speedtest', 'debug', 'Score: ' . $score . '/10');

		$rating = self::getUsageRating($download, $upload, $ping);
		$changed = $eq->checkAndUpdateCmd('usage_rating', $rating) || $changed;
		log::add('speedtest', 'debug', 'Usage: ' . $rating);

		log::add('speedtest', 'debug', '############ Speedtest terminé ############');

		if ($changed) {
			$eq->refreshWidget();
		}
	}

	public function postUpdate() {
		// Commandes info numériques
		$numericCmds = array(
			'speeddl'     => array('name' => __('Download', __FILE__), 'unite' => 'Mbit/s'),
			'speedul'     => array('name' => __('Upload', __FILE__), 'unite' => 'Mbit/s'),
			'ping'        => array('name' => __('Ping', __FILE__), 'unite' => 'ms'),
			'jitter'      => array('name' => __('Jitter', __FILE__), 'unite' => 'ms'),
			'packet_loss' => array('name' => __('Perte de paquets', __FILE__), 'unite' => '%'),
			'score'       => array('name' => __('Score', __FILE__), 'unite' => '/10'),
		);
		foreach ($numericCmds as $logicalId => $def) {
			$cmd = $this->getCmd(null, $logicalId);
			if (!is_object($cmd)) {
				$cmd = new speedtestCmd();
				$cmd->setName($def['name']);
			}
			$cmd->setLogicalId($logicalId);
			$cmd->setEqLogic_id($this->getId());
			$cmd->setType('info');
			$cmd->setSubType('numeric');
			$cmd->setUnite($def['unite']);
			$cmd->save();
		}

		// Commandes info string
		$stringCmds = array(
			'server_name'  => __('Serveur', __FILE__),
			'isp'          => __('FAI', __FILE__),
			'usage_rating' => __('Notation usage', __FILE__),
		);
		foreach ($stringCmds as $logicalId => $name) {
			$cmd = $this->getCmd(null, $logicalId);
			if (!is_object($cmd)) {
				$cmd = new speedtestCmd();
				$cmd->setName($name);
			}
			$cmd->setLogicalId($logicalId);
			$cmd->setEqLogic_id($this->getId());
			$cmd->setType('info');
			$cmd->setSubType('string');
			$cmd->save();
		}

		// Commande status (binary)
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

		// Commande action refresh
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

		// Gestion du cron
		if ($this->getIsEnable() == 1 && $this->getConfiguration('autCron', 0) == 1) {
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
		$replace = $this->preToHtml($_version);
		if (!is_array($replace)) {
			return $replace;
		}
		$version = jeedom::versionAlias($_version);

		foreach (array('speeddl', 'speedul', 'ping', 'jitter', 'packet_loss', 'score', 'status') as $logicalId) {
			$cmd = $this->getCmd('info', $logicalId);
			if (!is_object($cmd)) {
				$replace['#' . $logicalId . '_id#'] = '';
				$replace['#' . $logicalId . '_value#'] = '-';
				continue;
			}
			$replace['#' . $logicalId . '_id#'] = $cmd->getId();
			$replace['#' . $logicalId . '_value#'] = $cmd->execCmd();
			$replace['#' . $logicalId . '_collectDate#'] = $cmd->getCollectDate();
		}

		$cmdRefresh = $this->getCmd('action', 'refresh');
		$replace['#refresh_id#'] = is_object($cmdRefresh) ? $cmdRefresh->getId() : '';

		$replace['#maxdl#'] = $this->getConfiguration('maxdl', 1000);
		$replace['#maxul#'] = $this->getConfiguration('maxul', 500);

		// Labels traduits pour le template
		$statusVal = isset($replace['#status_value#']) ? $replace['#status_value#'] : 0;
		$replace['#status_text#'] = ($statusVal == 1) ? __('OK', __FILE__) : __('Erreur', __FILE__);
		$replace['#status_color#'] = ($statusVal == 1) ? '#1D9E75' : '#E54D42';
		$replace['#refresh_label#'] = __('Rafraichir', __FILE__);
		$replace['#score_label#'] = __('Score', __FILE__);
		$replace['#loss_label#'] = __('Perte', __FILE__);

		return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'eqLogic.speedtest', 'speedtest')));
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
