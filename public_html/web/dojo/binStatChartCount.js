define([
   "dojo/_base/declare",
   "vaqum/binStatChartBase"
],
function(declare, ChartBase) {
   return declare(ChartBase, {
      createSpecifics: function() {
         this.chart.addAxis("y", {
            vertical: true,
            title: "Value",
            min: 0,
            htmlLabels: false,
            fontColor: "#000000",
            font: "normal normal normal 10pt DejaVu Sans"
         });
      }
   });
});