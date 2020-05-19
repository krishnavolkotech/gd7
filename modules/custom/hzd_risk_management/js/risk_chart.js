(function(Drupal, drupalSettings) {
  Drupal.behaviors.hzd_risk_management = {
    attach: function(context, settings) {
      // console.log(drupalSettings);
      var measureData = drupalSettings.hzd_risk_management.statusCounts;
      // var statusLabels = measureData.statusLabels();
      var statusLabels = [];
      var statusCounts = [];
      var translatedStatusLabels = [];
      for (var key in measureData) {
        statusLabels.push(key);
        // translatedStatusLabels.push(Drupal.t(key));
        statusCounts.push(measureData[key]);
      }
      console.log(translatedStatusLabels);
      var ctx = document.getElementById('myChart').getContext('2d');
      var myChart = new Chart(ctx, {
        type: 'pie',
        data: {
          // labels: ['Red', 'Blue', 'Yellow'],
          labels: statusLabels,
          datasets: [{
            label: '# of Votes',
            data: [4, 2, 3, 1, 2],
            // data: statusCounts,
            backgroundColor: [
              'rgba(217, 84, 79, 1)',
              'rgba(240, 173, 78, 1)',
              'rgba(255, 218, 86, 1)',
              'rgba(148, 148, 148, 1)',
              'rgba(92, 184, 92, 1)'
            ],
            // borderColor: [
            //   'rgba(255, 99, 132, 1)',
            //   'rgba(54, 162, 235, 1)',
            //   'rgba(255, 206, 86, 1)',
            //   'rgba(75, 192, 192, 1)',
            //   'rgba(153, 102, 255, 1)'
            // ],
            // borderWidth: 1
          }]
        },
        options: {
          legend: {
            display: false
          },
          scales: {
            yAxes: [{
              ticks: {
                beginAtZero: true
              },
              display: false
            }]
          }
        }
      });
    }
  }
})(Drupal, drupalSettings);

