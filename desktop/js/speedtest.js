
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
 
$("#table_cmd").sortable({
	axis: "y",
	cursor: "move",
	items: ".cmd",
	placeholder: "ui-state-highlight",
	tolerance: "intersect",
	forcePlaceholderSize: true
	}
);

$("body").undelegate(".eqLogicAttr[data-l1key=configuration][data-l2key=autCron]", 'change ').delegate('.eqLogicAttr[data-l1key=configuration][data-l2key=autCron]', 'change ', function () {
	$(this).value() == 1 ? $('#cron_speedtest').show() : $('#cron_speedtest').hide();
});

$("body").undelegate(".eqLogicAttr[data-l1key=configuration][data-l2key=useArch]", 'change ').delegate('.eqLogicAttr[data-l1key=configuration][data-l2key=useArch]', 'change ', function () {
	$(this).value() == 1 ? $('.official').show() : $('.official').hide();
});

$('#bt_cronGenerator').on('click', function () {
	jeedom.getCronSelectModal({}, function (result) {
		$('.eqLogicAttr[data-l1key=configuration][data-l2key=refreshCron]').value(result.value);
	});
});

$("body").undelegate(".speedtest-widgetType", 'change ').delegate('.speedtest-widgetType', 'change ', function () {
	if ( $(this).value() == 1) {
		$('.speedtest-widgetType').not(this).each(function(){
			$( this ).prop("checked", false);
		});
	}	
});

$(".eqLogicAction[data-action='serverList']").on('click',function(){
	let id = $('.eqLogicAttr[data-l1key=id]').value();
	$('#md_modal').dialog({title: "{{Listes des serveurs}}"}).load('index.php?v=d&plugin=speedtest&modal=modal.list&id=' + id).dialog('open');
});

function printEqLogic(_eqLogic) {
	$('#cron_speedtest').hide();
	$('.official').hide();
	if (isset(_eqLogic.configuration)) {
		if (isset(_eqLogic.configuration.autCron)) {
			_eqLogic.configuration.autCron == 0 ? $('#cron_speedtest').hide() : $('#cron_speedtest').show();
		}
		if (isset(_eqLogic.configuration.useArch)) {
			_eqLogic.configuration.useArch == 0 ? $('.official').hide() : $('.official').show();
		}		
	}
}

function addCmdToTable(_cmd) {
	if (!isset(_cmd)) {
		var _cmd = {};
	}
	if (!isset(_cmd.configuration)) {
		_cmd.configuration = {};
	}
	var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
	tr += '<td>';
	tr += '<span class="cmdAttr" data-l1key="id" ></span>';
	tr += '</td>';
	tr += '<td>' + _cmd.name + '</td>';
	tr += '<td>';
	tr += '<input class="cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:30%;display:inline-block;margin-left:2px;">';	
	tr += '<span><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" /> {{Historiser}}<br/></span>';
	tr += '<span><input type="checkbox" class="cmdAttr" data-l1key="isVisible" /> {{Afficher}}<br/></span>';
	tr += '</td>';
	tr += '<td>';
	if (is_numeric(_cmd.id)) {
		tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fas fa-cogs"></i></a> ';
		tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>';
	}
	tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
	tr += '</tr>';
	$('#table_cmd tbody').append(tr);
	$('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
}
