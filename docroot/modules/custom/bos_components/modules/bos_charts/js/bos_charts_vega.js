jQuery(document).ready(function ($) {
  var opt = {
    "renderer": "svg",
    "actions": false
  };
  for(const chartid in drupalSettings.cob.charts) {
    var chart = drupalSettings.cob.charts[chartid];
    var VegaLiteSpec = JSON.parse(chart.chartobj);

    // Replace dataURL in object if provided.
    if (typeof chart.dataType !== "undefined" && typeof chart.data !== "undefined") {
      if (chart.dataType == "url") {
        VegaLiteSpec.data = {};
        VegaLiteSpec.data.url = chart.data;
      }
      else if (chart.dataType == "json_values") {
        VegaLiteSpec.data = {};
        VegaLiteSpec.data = JSON.parse(chart.data);
      }
    }

    // Ensure the background is set to "none" for theming.
    if (typeof VegaLiteSpec.config === "undefined") {
      VegaLiteSpec.config = {};
    }
    VegaLiteSpec.config.background = "none";

    vegaEmbed('#' + chart.chartid , VegaLiteSpec, opt)
      .then(function (result) {
      })
      .catch(console.error);
  }
});
