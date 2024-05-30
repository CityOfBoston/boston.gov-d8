jQuery(document).ready(function ($) {
  var opt = {
    "renderer": "svg",
    "actions": false
  };
  for(const chartid in drupalSettings.cob.charts) {
    var chart = drupalSettings.cob.charts[chartid];
    var VegaLiteSpec = JSON.parse(chart.chartobj);

    // Replace dataURL in object if provided.
    if (typeof chart.dataUrl !== "undefined") {
      VegaLiteSpec.data.url = chart.dataUrl;
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
