define([
   "dojo/_base/declare",
   "vaqum/binStatChartBase",
   "dojox/charting/plot2d/Indicator"
],
function(declare, ChartBase, Indicator) {
   return declare(ChartBase, {

      createSpecifics: function() {
         this.chart.addAxis("y", {
            vertical: true,
            title: "Value",
            htmlLabels: false,
            fontColor: "#000000",
            font: "normal normal normal 10pt DejaVu Sans"
         });

         this.chart.addPlot("threshold", {
            type: "Indicator",
            vertical: false,
            lineStroke: { color: "#777777", style: "Lines"},
            labels: false,
            values: 0
         });
      }
   });
});