define([
   "dojo/_base/declare",
   "dojo/dom-construct",
   "vaqum/binStatChartFactory"
],
function(declare, domConstruct, ChartFactory) {
   return declare(null, {
      charts: null,

      constructor: function(plotAreaNode, legendNode) {
         this.charts = [];
         var chartFactory = new ChartFactory();
         for (stat of ['count', 'rmse', 'mbias', 'corr', 'urmse']) {
            var containerNode = domConstruct.create("div", {class: 'fctHourAvgGraphContainer'}, plotAreaNode);
            this.charts.push(chartFactory.create(containerNode, stat));
         }

         legendNode.innerHTML = `
<table>
   <tr>
      <th>Base</th>
      <th>Test</th>
   </tr>
   <tr>
      <td class="Base"></td>
      <td class="Test"></td>
   </tr>
</table>`;
      },

      update: function(comparison, region, variable) {
         this.charts.forEach(function(chart) {
            chart.update(comparison, region, variable);
         });
      },

      // Wacky hack!  I don't know why "this" is defined in the update fuction,
      // but not in resize
      resize: function(myself, width) {

      }
   });
});