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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function speedtest_install() {
	$cmd = system::getCmdSudo() . 'chown -R ' . system::get('www-uid') . ':' . system::get('www-gid') . ' ' . __DIR__ . '/../3rdparty/;';
	$cmd .= system::getCmdSudo() . 'chmod 775 -R ' . __DIR__ . '/../3rdparty/;';
	exec($cmd);		
}

function speedtest_update() {
	foreach ( speedtest::byType( 'speedtest' ) as $speedtest ) {
		if ( $speedtest->getConfiguration( 'autAltBeta', 0 ) == 1 ) {
			$speedtest->setConfiguration( 'autAlt', 1 );
		}
		$speedtest->save();
		$cron = cron::byClassAndFunction( 'speedtest', 'getInfo', array( 'speedtest_id' => intval( $speedtest->getId() ) ) );
		if ( is_object( $cron ) ) {
			$cron->remove();
		}
		$cron = cron::byClassAndFunction( 'speedtest', 'updateInfo', array( 'speedtest_id' => intval( $speedtest->getId() ) ) );
		if ( is_object( $cron ) ) {
			$cron->remove();
		}		
	}

	$cmd = system::getCmdSudo() . 'chown -R ' . system::get('www-uid') . ':' . system::get('www-gid') . ' ' . __DIR__ . '/../3rdparty/;';
	$cmd .= system::getCmdSudo() . 'chmod 775 -R ' . __DIR__ . '/../3rdparty/;';
	exec($cmd);	
}

function speedtest_remove() {
    $crons = cron::searchClassAndFunction('speedtest','updateInfo');
    foreach($crons as $cron) {    
        if(is_object($cron)) {
            $cron->remove();
        }  
    }
    $crons = cron::searchClassAndFunction('speedtest','getInfo');
    foreach($crons as $cron) {    
        if(is_object($cron)) {
            $cron->remove();
        }  
    }	
}

?>
