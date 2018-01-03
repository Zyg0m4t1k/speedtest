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
include_file('core', 'authentification', 'php');
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
?>


<form class="form-horizontal">
	<fieldset>
    <div class="form-group">
    <label class="col-lg-1 control-label">{{Droits Sudo : }}</label>
    <?php
    if (exec('sudo cat /etc/sudoers') == "") {
        echo '<div class="col-lg-1"><span class="label label-success">OK</span></div>';
    } else {
        echo '<div class="col-lg-1"><span class="label label-danger">NOK</span>    <span><a href="http://doc.jeedom.fr/fr_FR/doc-installation.html#troubleshoting"><i class="fa fa-question-circle"></i></a></span></div>';
    }
    ?>
    </div>
    </br>    
    
	 <div class="form-group">
            <label class="col-lg-1 control-label">{{Check adresse Ip}}</label>
            <div class="col-sm-3">
                <input type="checkbox" class="configKey" data-l1key="checkIp" />
            </div>
     </div>    
   <div class="form-group">
    <label  class="col-sm-2 control-label">{{Adresse IP}}</label>
    <div class="col-sm-3">
     <input class="configKey form-control" data-l1key="ipkey" disabled />
   </div>
 </div>
 
 
</fieldset>
</form>
<script>

	$('.configKey[data-l1key=checkIp]').change(function () {
		 if(this.checked) {
			$.ajax({// fonction permettant de faire de l'ajax
				type: "POST", // methode de transmission des données au fichier php
				url: "plugins/speedtest/core/ajax/speedtest.ajax.php", // url du fichier php
				data: {
					action: "getIp"
				},
				dataType: 'json',
				error: function (request, status, error) {
					handleAjaxError(request, status, error);
				},
				success: function (data) { // si l'appel a bien fonctionné
					if (data.state != 'ok') {
						$('#div_alert').showAlert({message: data.result, level: 'danger'});
						return;
					}
					console.log(data.result);
					$('.configKey[data-l1key=ipkey]').empty().val(data.result);
					savePluginConfig();
				}
			});				 
			 
		 } else {
			  $('.configKey[data-l1key=ipkey]').empty().val('');
			  savePluginConfig();
		 }
		 
	});	

</script>

