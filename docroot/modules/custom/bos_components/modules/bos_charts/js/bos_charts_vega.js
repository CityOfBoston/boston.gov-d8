jQuery(document).ready(function ($) {
  console.log(drupalSettings.cob.chartobj);
  var VegaLiteSpec = JSON.parse(drupalSettings.cob.chartobj);
  var opt = {
    "renderer": "svg",
    "actions": false
  };
  vegaEmbed('#' + drupalSettings.cob.chartid , VegaLiteSpec, opt)
    .then(function (result) {
    })
    .catch(console.error);
});
