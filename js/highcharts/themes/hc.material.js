/**
 * Mikhmon Material theme for Highcharts JS
 * @author Laksamadi Guko
 */

Highcharts.theme = {
	colors: ["#1976D2", "#D32F2F"],
	chart: {
		backgroundColor: '#FFFFFF',
		borderColor: '#FFFFFF',
		borderWidth: 1,
		className: 'material-cart',
		plotBackgroundColor: '#FFFFFF',
		plotBorderColor: '#CAC4D0',
		plotBorderWidth: 1,
		height: '300px'
	},
	title: {
		style: {
			color: '#1C1B1F',
			font: 'bold 14px "Inter", "Trebuchet MS", Verdana, sans-serif, Roboto,"Seggoe UI"'
		}
	},
	subtitle: {
		style: {
			color: '#1C1B1F',
			font: 'bold 12px "Inter", "Trebuchet MS", Verdana, sans-serif'
		}
	},
	xAxis: {
		gridLineColor: '#CAC4D0',
		gridLineWidth: 1,
		labels: {
			style: {
				color: '#49454F'
			}
		},

		lineColor: '#CAC4D0',
		tickColor: '#CAC4D0',
		title: {
			style: {
				color: '#1C1B1F',
				fontWeight: 'bold',
				fontSize: '12px',
				fontFamily: 'bold 16px "Inter", "Trebuchet MS", Verdana, sans-serif, Roboto,"Seggoe UI"'

			}
		}
	},
	yAxis: {
		gridLineColor: '#CAC4D0',
		labels: {
			style: {
				color: '#49454F'
			}
		},
		lineColor: '#CAC4D0',
		minorTickInterval: null,
		tickColor: '#CAC4D0',
		tickWidth: 1,
		title: {
			style: {
				color: '#1C1B1F',
				fontWeight: 'bold',
				fontSize: '12px',
				fontFamily: 'bold 16px "Inter", "Trebuchet MS", Verdana, sans-serif, Roboto,"Seggoe UI"'
			}
		}
	},
	plotOptions: {
		series: {
			fillOpacity: 0.1
		}
	},
	tooltip: {
		backgroundColor: 'rgba(255, 255, 255, 0.92)',
		style: {
			color: '#1C1B1F'
		}
	},
	legend: {
		itemStyle: {
			font: '9pt Inter, Trebuchet MS, Verdana, sans-serif',
			color: '#49454F'
		},
		itemHoverStyle: {
			color: '#1976D2'
		},
		itemHiddenStyle: {
			color: '#CAC4D0'
		}
	},
	credits: {
		enabled: 0,
	}

};

// Apply the theme
var highchartsOptions = Highcharts.setOptions(Highcharts.theme);
