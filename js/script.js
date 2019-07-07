var chart = null;

window.onload = function() {
	drawChart([0,0], [0,0]);
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

function onSuccess(response) {
	console.log(response);
	if (response.success)
		drawChart(response.message.date_arr, response.message.val_arr);
}

function onError(jqXHR, textStatus, errorThrown) {
	switch(jqXHR.status) {
		case 0:
			alert('Ошибка при попытке соединиться с сервером!');
			break;
		case 500:
			alert('На сервере произошла ошибка!\nОбратитесь к администратору ресурса!');
			break;
		default:
			alert('Произошла ошибка при попытке получить даные!\nОбратитесь к администратору ресурса!');
	}
}

function drawChart(date_arr, val_arr) {
	var context = document.getElementById('myChart').getContext('2d');

	// Если не очистить холст перед перерисовкой - возможны баги. Например, вывод старого графика при скроллинге.
	if (chart != null) chart.destroy();
	chart = new Chart(context, {
	    // The type of chart we want to create
	    type: 'line',

	    // The data for our dataset
	    data: {
	        labels: date_arr,
	        datasets: [{
	            label: 'Стоимость доллара в рублях',
	            backgroundColor: 'rgba(255, 99, 132, 1)',
	            borderColor: 'rgb(255, 99, 132)',
	            data: val_arr,
	            radius: 0,
	        }]
	    },

	    options: {
	        elements: {
	            line: {
	                tension: 0, // disables bezier curves
	                fill: false,
	            }
	        }
    }
	});
}