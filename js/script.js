window.onload = function() {
	var chart = document.getElementById('myChart').getContext('2d');
}

function sendData() {
	var dateRange = {
		from: $('#controls input[name=fromDate]').val(),
		to: $('#controls input[name=toDate]').val(),
	}, regex = new RegExp("^[0-9]{2}\.[0-9]{2}\.[0-9]{4}$");
	
	if (dateRange.from == '' || dateRange.to == '')
		alert('Ошибка! Не все поля заполнены!');
	else if (!regex.test(dateRange.from) || !regex.test(dateRange.to))
		alert('Ошибка! Введите дату в формате [dd.mm.YYYY]');
	else
		$.ajax({
			url: '/handler.php',
			type: 'POST',
			data: JSON.stringify(dateRange),
			dataType: 'json',
			success: onSuccess,
			error: onError,
			async: true,
		});
}

function onSuccess(response, textStatus, jqXHR) {
	console.log('Ответ от сервера: ' + response.message);
	console.log(response);
	console.log(textStatus);
}

function onError(jqXHR, textStatus, errorThrown) {
	console.log('Произошла ошибка: ' + textStatus);
	console.log(jqXHR);
}