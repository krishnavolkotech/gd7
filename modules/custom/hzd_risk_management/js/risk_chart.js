(function($, Drupal, drupalSettings) {
  Drupal.behaviors.hzd_risk_managementChart = {
    attach: function(context, settings) {

      if ($(context).find('.risk-management-chart-block').length) {
        $(".risk-management-chart-block").insertAfter($(".page-header"));
      }
      
      var statusColorScheme = [
        '#337ab7',
        '#337ab7',
        '#337ab7',
        '#337ab7',
        '#337ab7',
      ];
      
      var categoryColorScheme = [
        '#D9534F',
        '#FFDA56',
        '#5CB85C',
      ];
      
      // Chart.defaults.global.defaultFontColor = 'rgb(34, 34, 34)';
      
      // Einzelrisiko: Maßnahmen Status
      if ($(context).find('#measure-status-pie-chart').length) {
        var measureData = settings.hzd_risk_management.chartData['status'];
        var statusLabels = [];
        var statusCounts = [];
        var step = 1;
        for (var key in measureData) {
          statusLabels.push(key);
          statusCounts.push(measureData[key]);
          if (measureData[key] > 5) {
            step = 2;
          }
        }
        var ctx = $(context).find('#measure-status-pie-chart')[0].getContext('2d');
        var myChart = new Chart(ctx, {
          type: 'bar',
          data: {
            // labels: ['Red', 'Blue', 'Yellow'],
            labels: statusLabels,
            datasets: [{
              label: '',
              // data: [4, 2, 3, 1, 2],
              data: statusCounts,
              backgroundColor: statusColorScheme,
            }]
          },
          options: {
            responsive: false,
            legend: {
              display: false,
              position: "right",
              labels: {
                boxWidth: 12,
              }
            },
            scales: {
              yAxes: [{
                ticks: {
                  beginAtZero: true,
                  stepSize: step,
                },
                display: true
              }]
            },
            layout: {
              padding: {
                left: 0,
                right: 40,
                top: 0,
                bottom: 0
              }
            }

          }
        });
      }

      // Maßnahme: Risiken Status
      if ($(context).find("#risk-status-pie-chart").length) {
        var measureData = drupalSettings.hzd_risk_management.chartData['status'];
        var statusLabels = [];
        var statusCounts = [];
        var step = 1;
      
        for (var key in measureData) {
          statusLabels.push(key);
          statusCounts.push(measureData[key]);
          if (measureData[key] > 5) {
            step = 2;
          }
        }
        
        var ctx = $(context).find('#risk-status-pie-chart')[0].getContext('2d');
        var myChart = new Chart(ctx, {
          type: 'bar',
          data: {
            // labels: ['Red', 'Blue', 'Yellow'],
            labels: statusLabels,
            datasets: [{
              // label: '# of Votes',
              // data: [4, 2, 3, 1, 2],
              data: statusCounts,
              backgroundColor: statusColorScheme,
            }]
          },
          options: {
            responsive: false,
            legend: {
              display: false,
              position: "right",
              labels: {
                boxWidth: 12,
              }
            },
            scales: {
              yAxes: [{
                ticks: {
                  beginAtZero: true,
                  stepSize: step,
                },
                display: true
              }],
            },
            layout: {
              padding: {
                left: 0,
                right: 0,
                top: 0,
                bottom: 0
              }
            },
          }
        });
      }
            
      // Maßnahmen: Risikokategorien
      if ($(context).find("#risk-category-pie-chart").length) {
        var data = drupalSettings.hzd_risk_management.chartData['categories'];
        var statusLabels = [];
        var statusCounts = [];
        for (var key in data) {
          statusLabels.push(key);
          statusCounts.push(data[key]);
        }
        var ctx = $(context).find('#risk-category-pie-chart')[0].getContext('2d');
        var myChart = new Chart(ctx, {
          type: 'pie',
          data: {
            // labels: ['1', '2', '3', '4', '5'],
            labels: statusLabels,
            datasets: [{
              label: '',
              // data: [4, 2, 3, 1, 2],
              data: statusCounts,
              backgroundColor: categoryColorScheme,
            }]
          },
          options: {
            responsive: false,
            legend: {
              display: true,
              position: "right",
              labels: {
                boxWidth: 12,
              }
            },
            scales: {
              yAxes: [{
                ticks: {
                  beginAtZero: true
                },
                display: false
              }]
            },
            layout: {
              padding: {
                left: 0,
                right: 70,
                // right: 0,
                top: 0,
                bottom: 0
              }
            }
          }
        });
      }

      if ($(context).find('#front-measure-status-pie-chart').length) {
        var measureData = settings.hzd_risk_management.chartData['status'];
        var statusLabels = [];
        var statusCounts = [];
        var step = 1;
       for (var key in measureData) {
          statusLabels.push(key);
          statusCounts.push(measureData[key]);
          if (measureData[key] > 5) {
            step = 2;
          }
        }
        var ctx = $(context).find('#front-measure-status-pie-chart')[0].getContext('2d');
        var myChart = new Chart(ctx, {
          type: 'bar',
          data: {
            // labels: ['Red', 'Blue', 'Yellow'],
            labels: statusLabels,
            datasets: [{
              label: '',
              // data: [4, 2, 3, 1, 2],
              data: statusCounts,
              backgroundColor: statusColorScheme,
            }]
          },
          options: {
            title: {
              display: true,
              text: 'Maßnahmen: Status',
              fontSize: 14,
              fontFamily: 'Roboto, sans-serif',
              fontColor: 'rgb(34, 34, 34)',
              // fontColor: '#027596',
              // padding: 5,
            },
            responsive: false,
            legend: {
              display: false,
              position: "right",
              labels: {
                boxWidth: 12,
              }
            },
            scales: {
              yAxes: [{
                ticks: {
                  beginAtZero: true,
                  stepSize: step,
                },
                display: true
              }]
            },
            layout: {
              padding: {
                left: 40,
                right: 0,
                top: 0,
                bottom: 0
              }
            }
          }
        });
      }

      if ($(context).find('#front-risk-category-pie-chart').length) {
        var data = drupalSettings.hzd_risk_management.chartData['categories'];
        var statusLabels = [];
        var statusCounts = [];
        for (var key in data) {
          statusLabels.push(key);
          statusCounts.push(data[key]);
        }
        var ctx = $(context).find('#front-risk-category-pie-chart')[0].getContext('2d');
        var myChart = new Chart(ctx, {
          type: 'pie',
          data: {
            // labels: ['1', '2', '3', '4', '5'],
            labels: statusLabels,
            datasets: [{
              label: '',
              // data: [4, 2, 3, 1, 2],
              data: statusCounts,
              backgroundColor: categoryColorScheme,
            }]
          },
          options: {
            title: {
              display: true,
              text: 'Risiken: Kategorien',
              fontSize: 14,
              fontFamily: 'Roboto, sans-serif',
              fontColor: 'rgb(34, 34, 34)',
              // fontColor: '#027596',
              // padding: 5,
            },
            responsive: false,
            legend: {
              display: true,
              position: "right",
              labels: {
                boxWidth: 12,
              }
            },
            scales: {
              yAxes: [{
                ticks: {
                  beginAtZero: true
                },
                display: false
              }]
            },
            layout: {
              padding: {
                left: 0,
                right: 75,
                top: 0,
                bottom: 0
              }
            }
          }
        });
      }
    }
  }
})(jQuery, Drupal, drupalSettings);

