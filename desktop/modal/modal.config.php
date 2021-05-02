<?php

$file = __DIR__ . '/../../resources/serveur.xml';


if(!is_file($file)) {
	die;
}

$xml = new SimpleXMLElement($file, 0, TRUE);
?>
<legend class="col-form-label warning"> {{ Les serveurs sont donnés à titre indicatif, En cas d'échec , choissisez-en un autre...}}</legend>
<input class="form-control" placeholder="{{Rechercher Pays}}" id="countrysearchServer" />
<table id="speedtest_serverList">
	<thead>
		<tr>
			<th>Id</th>
			<th>{{ Pays }}</th>
			<th>{{ Nom }}</th>
			<th>{{ Url }}</th>
			<th>{{ Sponsor }}</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($xml->servers->server as $server) { ?>
		<tr class="dataServer">
			<td>
				<?php echo $server['id']; ?>
			</td>
			<td class="country">
				<?php echo $server['country']; ?>
			</td>
			<td>
				<?php echo $server['name']; ?>
			</td>
			<td>
				<?php echo $server['url']; ?>
			</td>
			<td>
				<?php echo $server['sponsor']; ?>
			</td>				
		</tr>
		<?php } ?>
	</tbody>
</table>
<script>
$( '#countrysearchServer' ) . off( 'keyup' ) . keyup( function () {
	var search = $( this ) . value()
	if ( search == '' ) {
		$( '#speedtest_serverList .dataServer' ) . show()
		return
	}
	$( '#speedtest_serverList .dataServer' ) . hide()
	search = search . normalize( 'NFD' ) . replace( /[\u0300-\u036f]/g, "" ) . toLowerCase()
	var text
	$( '#speedtest_serverList .country' ) . each( function () {
		text = $( this ) . text() . normalize( 'NFD' ) . replace( /[\u0300-\u036f]/g, "" ) . toLowerCase()
		if ( text . indexOf( search ) >= 0 ) {
			$( this ) . closest( 'tr' ) . show()
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
