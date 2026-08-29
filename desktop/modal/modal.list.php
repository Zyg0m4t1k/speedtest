<?php

$speedtestBin = speedtest::getSpeedtestBin();
$cmd = escapeshellcmd($speedtestBin) . ' --list';
$output = array();
$rc = 0;
exec($cmd . ' 2>&1', $output, $rc);
if ($rc !== 0) {
	echo '<legend class="danger">{{Erreur lors de la récupération des serveurs}}</legend>';
	die;
}
$lines = $output;
?>
<br/>
<center>
	<span class="success"> <?php echo count($lines) -1;?> {{ serveurs }}</span>
	<table id="speedtest_serverList">
		<caption><input class="form-control" placeholder="{{Rechercher Pays}}" id="countrysearchServer" /></caption>
		<thead>
			<tr>
				<th>Id</th>
				<th>{{ Nom }}</th>
				<th>{{ Localisation }}</th>
				<th>{{ Distance }}</th>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach ($lines as $line) {
			// 17392) myLoc managed IT AG (Dusseldorf, Germany) [524.97 km]
			if(preg_match('/(.*)\) (.*) \((.*)\) \[(.*)\]/',$line,$m)) {
				echo '<tr class="dataServer">';
				echo '<td>' . $m[1]  . '</td>';
				echo '<td>' . $m[2]  . '</td>';
				echo '<td class="country">' . $m[3]  . '</td>';
				echo '<td>' . $m[4]  . '</td>';
				echo '</tr>';
			}
		}
		?>
		</tbody>
	</table>
</center>
<script>
document.getElementById('countrysearchServer').addEventListener('keyup', function() {
	var search = this.value
	var rows = document.querySelectorAll('#speedtest_serverList .dataServer')
	if (search == '') {
		rows.forEach(function(row) { row.style.display = '' })
		return
	}
	rows.forEach(function(row) { row.style.display = 'none' })
	search = search.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase()
	document.querySelectorAll('#speedtest_serverList .country').forEach(function(el) {
		var text = el.textContent.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase()
		if (text.indexOf(search) >= 0) {
			el.closest('tr').style.display = ''
		}
	})
})

document.getElementById('speedtest_serverList').addEventListener('click', function(event) {
	var row = event.target.closest('.dataServer')
	if (!row) return
	var serverId = row.querySelector('td').textContent.trim()
	document.querySelector('.eqLogicAttr[data-l1key="configuration"][data-l2key="server_id"]').jeeValue(serverId)
	jeeFrontEnd.modifyWithoutSave = true
	document.getElementById('md_speedtestServerList')._jeeDialog.destroy()
})
</script>
<style>
	#speedtest_serverList th, #speedtest_serverList td {
		padding: 10px;
		text-align: center;
	}
	#speedtest_serverList .dataServer {
		cursor: pointer;
	}
	#speedtest_serverList .dataServer:hover {
		background-color: var(--el-hoverbgColor);
	}
</style>