	$('.configKey[data-l1key=checkIp]').change(function () {
//		 if(this.checked) {
//			$.ajax({// fonction permettant de faire de l'ajax
//				type: "POST", // methode de transmission des données au fichier php
//				url: "plugins/speedtest/core/ajax/speedtest.ajax.php", // url du fichier php
//				data: {
//					action: "getIp"
//				},
//				dataType: 'json',
//				error: function (request, status, error) {
//					handleAjaxError(request, status, error);
//				},
//				success: function (data) { // si l'appel a bien fonctionné
//					if (data.state != 'ok') {
//						$('#div_alert').showAlert({message: data.result, level: 'danger'});
//						return;
//					}
//					console.log(data.result);
//					$('.configKey[data-l1key=ipkey]').empty().val(data.result);
//					savePluginConfig();
//				}
//			});				 
//		 } else {
//			  $('.configKey[data-l1key=ipkey]').empty().val('');
//			  savePluginConfig();
//		 }
	});

	function speedtest_postSaveConfiguration(){
		var datas = [$('.configKey[data-l1key=checkIp]').value(),$('.configKey[data-l1key=useArch]').value()];
		$.ajax({// fonction permettant de faire de l'ajax
			type: "POST", // methode de transmission des données au fichier php
			url: "plugins/speedtest/core/ajax/speedtest.ajax.php", // url du fichier php
			data: {
				action: "setInfo",
				datas: datas
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
				$('.configKey[data-l1key=ipkey]').empty().val(data.result['ip']);
				$('.configKey[data-l1key=arch]').empty().val(data.result['arch']);
			}
		});			
	}