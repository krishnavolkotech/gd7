(function($, Drupal, drupalSettings) {
  Drupal.behaviors.hzd_risk_managementChart = {
    attach: function(context, settings) {

      if ($(context).find('.risk-management-chart-block').length) {
        $(".risk-management-chart-block").insertAfter($(".page-header"));
      }
      
      // Einzelrisiko: Maßnahmen Status
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
                'rgba(2, 117, 150, 1)', // Primär Blau
                'rgba(204, 227, 235, 1)', // Hellblau 35%
                'rgba(127, 181, 203, 1)', // Sekundär Hellblau
                'rgba(1, 83, 119, 1)', // B HB
                'rgba(63, 128, 162, 1)', // HB HB
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
                'rgba(2, 117, 150, 1)', // Primär Blau
                'rgba(204, 227, 235, 1)', // Hellblau 35%
                'rgba(127, 181, 203, 1)', // Sekundär Hellblau
                'rgba(1, 83, 119, 1)', // B HB
                'rgba(63, 128, 162, 1)', // HB HB
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
                'rgba(0, 78, 89, 1)', // grünlich dunkelblau
                'rgba(2, 117, 150, 1)', // Primär Blau 
                'rgba(127, 181, 203, 1)', // Sekundär Hellblau
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
                'rgba(2, 117, 150, 1)', // Primär Blau
                'rgba(204, 227, 235, 1)', // Hellblau 35%
                'rgba(127, 181, 203, 1)', // Sekundär Hellblau
                'rgba(1, 83, 119, 1)', // B HB
                'rgba(63, 128, 162, 1)', // HB HB
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
                'rgba(0, 78, 89, 1)', // grünlich dunkelblau
                'rgba(2, 117, 150, 1)', // Primär Blau 
                'rgba(127, 181, 203, 1)', // Sekundär Hellblau
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

