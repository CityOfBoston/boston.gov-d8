jQuery(document).ready(function ($) {
  var opt = {
    "renderer": "svg",
    "actions": false
  };
  for(const chartid in drupalSettings.cob.charts) {
    var chart = drupalSettings.cob.charts[chartid];
    var VegaLiteSpec = JSON.parse(chart.chartobj);
    vegaEmbed('#' + chart.chartid , VegaLiteSpec, opt)
      .then(function (result) {
      })
      .catch(console.error);
  }
});
