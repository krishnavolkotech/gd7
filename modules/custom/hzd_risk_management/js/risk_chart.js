(function($, Drupal, drupalSettings) {
  Drupal.behaviors.hzd_risk_managementChart = {
    attach: function(context, settings) {

      if ($(context).find('.risk-management-chart-block').length) {
        $(".risk-management-chart-block").insertAfter($(".page-header"));
      }
      // Einzelrisiko: Maßnahmen Status

      // BUG behoben (24.06.2020, Robin): Chart wurde zwei mal geladen. Führt zu 
      // seltsamen Verhalten, z.b. wurde der Chart zu groß oder zu klein und 
      // unscharf dargestellt, wenn die Seite gezoomt wurde.
      // Ursache: jQuery selector wurde auf gesamten DOM angewandt. Aufgrund eines
      // AJAX calls, wurde das behavior zwei mal attached. (Jeder AJAX call macht
      // das). Lösung: context als Selector verwenden. Bei AJAX Call ist das nur
      // der Inhalt der AJAX Antwort und unser Ziel wird nicht ein zweites mal
      // gefunden.

      if ($(context).find('#measure-status-pie-chart').length) {
        var measureData = settings.hzd_risk_management.chartData['status'];
        var statusLabels = [];
        var statusCounts = [];
        for (var key in measureData) {
          statusLabels.push(key);
          statusCounts.push(measureData[key]);
        }
        var ctx = $(context).find('#measure-status-pie-chart')[0].getContext('2d');
        var myChart = new Chart(ctx, {
          type: 'pie',
          data: {
            // labels: ['Red', 'Blue', 'Yellow'],
            labels: statusLabels,
            datasets: [{
              label: '# of Votes',
              // data: [4, 2, 3, 1, 2],
              data: statusCounts,
              backgroundColor: [
                'rgba(217, 84, 79, 1)', // rot
                'rgba(240, 173, 78, 1)', // orange
                'rgba(255, 218, 86, 1)', // gelb
                'rgba(148, 148, 148, 1)', // grau
                'rgba(92, 184, 92, 1)' // grün
              ],
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
          }
        });
      }

      // Maßnahme: Risiken Status
      if ($(context).find("#risk-status-pie-chart").length) {
      
        var measureData = drupalSettings.hzd_risk_management.chartData['status'];
        var statusLabels = [];
        var statusCounts = [];
        
        for (var key in measureData) {
          statusLabels.push(key);
          statusCounts.push(measureData[key]);
        }
        
        var ctx = $(context).find('#risk-status-pie-chart')[0].getContext('2d');
        var myChart = new Chart(ctx, {
          type: 'pie',
          data: {
            // labels: ['Red', 'Blue', 'Yellow'],
            labels: statusLabels,
            datasets: [{
              label: '# of Votes',
              // data: [4, 2, 3, 1, 2],
              data: statusCounts,
              backgroundColor: [
                '#00A4E3',
                '#77659A',
                '#ED2551',
                '#3B84BF',
                '#B24576',
                // 'rgba(148, 148, 148, 1)', // grau
                // 'rgba(255, 218, 86, 1)', // gelb
                // 'rgba(240, 173, 78, 1)', // orange
                // 'rgba(217, 84, 79, 1)', // rot
                // 'rgba(92, 184, 92, 1)', // grün
              ],
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
                right: 0,
                top: 0,
                bottom: 0
              }
            }
          }
        });
        // myChart.canvas.parentNode.style.height = '128px';
        // myChart.canvas.parentNode.style.width = '128px';
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
              label: '# of Votes',
              // data: [4, 2, 3, 1, 2],
              data: statusCounts,
              backgroundColor: [
                '#ED2551',
                '#77659A',
                '#00A4E3',
                // 'rgba(217, 84, 79, 1)', // rot
                // 'rgba(255, 218, 86, 1)', // gelb
                // 'rgba(92, 184, 92, 1)', // grün
              ],
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
        // myChart.canvas.parentNode.style.height = '128px';
        // myChart.canvas.parentNode.style.width = '128px';
      }

      if ($(context).find('#front-measure-status-pie-chart').length) {
        var measureData = settings.hzd_risk_management.chartData['status'];
        var statusLabels = [];
        var statusCounts = [];
        for (var key in measureData) {
          statusLabels.push(key);
          statusCounts.push(measureData[key]);
        }
        var ctx = $(context).find('#front-measure-status-pie-chart')[0].getContext('2d');
        var myChart = new Chart(ctx, {
          type: 'pie',
          data: {
            // labels: ['Red', 'Blue', 'Yellow'],
            labels: statusLabels,
            datasets: [{
              label: '# of Votes',
              // data: [4, 2, 3, 1, 2],
              data: statusCounts,
              backgroundColor: [
                'rgba(217, 84, 79, 1)', // rot
                'rgba(240, 173, 78, 1)', // orange
                'rgba(255, 218, 86, 1)', // gelb
                'rgba(148, 148, 148, 1)', // grau
                'rgba(92, 184, 92, 1)' // grün
              ],
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
              label: '# of Votes',
              // data: [4, 2, 3, 1, 2],
              data: statusCounts,
              backgroundColor: [
                'rgba(217, 84, 79, 1)', // rot
                'rgba(255, 218, 86, 1)', // gelb
                'rgba(92, 184, 92, 1)', // grün
              ],
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
                right: 75,
                // right: 0,
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

