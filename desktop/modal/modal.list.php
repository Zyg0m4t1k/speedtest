<?php



if (init('id') == '') {
    throw new Exception('{{L\'id de l\'opération ne peut etre vide : }}' . init('op_id'));
}

$id = init('id');
$eq = eqLogic::byId($id);
if(!is_object($eq)) {
	echo '<legend class="danger">Equipement non trouvé</legend>';
}
if( $eq->getConfiguration('useArch', 0) == 1 ) {
	$dir = __DIR__ . '/../../3rdparty/' . $eq->getConfiguration('arch');
	if(!is_dir($dir)) {
		echo '<legend class="danger">Architecture non valable</legend>';
		die;
	}
	$cmd = $dir . '/speedtest --servers';
	$list = com_shell::execute(system::getCmdSudo() . $cmd);
	$lines = explode(PHP_EOL, $list);
	echo '<center>';
	foreach ($lines as $line) {
		echo $line . '<br/>';
	}
	echo '</center>';
	die;
	
} else {
	
}
$cmd = 'speedtest --list ';
$list = com_shell::execute(system::getCmdSudo() . $cmd);
$lines = explode(PHP_EOL, $list);
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
$( '#countrysearchServer' ).off( 'keyup' ).keyup( function () {
	var search = $( this ).value()
	if ( search == '' ) {
		$( '#speedtest_serverList .dataServer' ).show()
		return
	}
	$( '#speedtest_serverList .dataServer' ).hide()
	search = search.normalize( 'NFD' ).replace( /[\u0300-\u036f]/g, "" ).toLowerCase()
	var text
	$( '#speedtest_serverList .country' ).each( function () {
		text = $( this ).text().normalize( 'NFD' ).replace( /[\u0300-\u036f]/g, "" ).toLowerCase()
		if ( text.indexOf( search ) >= 0 ) {
			$( this ).closest( 'tr' ).show()
		}
	} )
} )
</script>
<style>
	#speedtest_serverList th , #speedtest_serverList td  {
		padding:10px;
		text-align: center;
	}
</style>