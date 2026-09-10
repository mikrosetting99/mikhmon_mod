/**
 * Mikhmon Material Dark theme for Highcharts JS
 * @author Laksamadi Guko
 */

Highcharts.theme = {
	colors: ["#90CAF9", "#EF9A9A"],
	chart: {
		backgroundColor: '#1D1B20',
		borderColor: '#1D1B20',
		borderWidth: 1,
		className: 'material-dark-cart',
		plotBackgroundColor: '#1D1B20',
		plotBorderColor: '#49454F',
		plotBorderWidth: 1,
		height: '300px'
	},
	title: {
		style: {
			color: '#E6E0E9',
			font: 'bold 14px "Inter", "Trebuchet MS", Verdana, sans-serif, Roboto,"Seggoe UI"'
		}
	},
	subtitle: {
		style: {
			color: '#E6E0E9',
			font: 'bold 12px "Inter", "Trebuchet MS", Verdana, sans-serif'
		}
	},
	xAxis: {
		gridLineColor: '#49454F',
		gridLineWidth: 1,
		labels: {
			style: {
				color: '#CAC4D0'
			}
		},

		lineColor: '#49454F',
		tickColor: '#49454F',
		title: {
			style: {
				color: '#E6E0E9',
				fontWeight: 'bold',
				fontSize: '12px',
				fontFamily: 'bold 16px "Inter", "Trebuchet MS", Verdana, sans-serif, Roboto,"Seggoe UI"'

			}
		}
	},
	yAxis: {
		gridLineColor: '#49454F',
		labels: {
			style: {
				color: '#CAC4D0'
			}
		},
		lineColor: '#49454F',
		minorTickInterval: null,
		tickColor: '#49454F',
		tickWidth: 1,
		title: {
			style: {
				color: '#E6E0E9',
				fontWeight: 'bold',
				fontSize: '12px',
				fontFamily: 'bold 16px "Inter", "Trebuchet MS", Verdana, sans-serif, Roboto,"Seggoe UI"'
			}
		}
	},
	plotOptions: {
		series: {
			fillOpacity: 0.15
		}
	},
	tooltip: {
		backgroundColor: 'rgba(29, 27, 32, 0.92)',
		style: {
			color: '#E6E0E9'
		}
	},
	legend: {
		itemStyle: {
			font: '9pt Inter, Trebuchet MS, Verdana, sans-serif',
			color: '#CAC4D0'
		},
		itemHoverStyle: {
			color: '#90CAF9'
		},
		itemHiddenStyle: {
			color: '#49454F'
		}
	},
	credits: {
		enabled: 0,
	}

};

// Apply the theme
var highchartsOptions = Highcharts.setOptions(Highcharts.theme);
