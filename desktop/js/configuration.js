function speedtest_postSaveConfiguration() {
	domUtils.ajax({
		type: 'POST',
		url: 'plugins/speedtest/core/ajax/speedtest.ajax.php',
		data: {
			action: 'setInfo',
			datas: [
				document.querySelector('.configKey[data-l1key="checkIp"]').jeeValue(),
				document.querySelector('.configKey[data-l1key="useArch"]').jeeValue()
			]
		},
		dataType: 'json',
		error: function(error) {
			jeedomUtils.showAlert({message: error.message, level: 'danger'})
		},
		success: function(data) {
			document.querySelector('.configKey[data-l1key="ipkey"]').jeeValue(data.result['ip'])
			document.querySelector('.configKey[data-l1key="arch"]').jeeValue(data.result['arch'])
		}
	})
}
