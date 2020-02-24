define([
   "dojo/_base/declare",
   "dojo/dom-construct",
   "vaqum/metAreaTsChart"
],
function(declare, domConstruct, metAreaTsChart) {
   return declare(null, {
      charts: null,

      constructor: function(plotAreaNode, legendNode) {
         this.charts = [];
         var containerNode = domConstruct.create("div", {class: 'metAreaTsGraphContainer'}, plotAreaNode);
         this.charts.push(metAreaTsChart(containerNode));

         legendNode.innerHTML = `
<table>
   <tr>
      <th>Base</th>
      <th>Test</th>
      <th>Obs</th>
   </tr>
   <tr>
      <td class="Base"></td>
      <td class="Test"></td>
      <td class="Obs"></td>
   </tr>
</table>`;
      },

      update: function(comparison, region, variable) {
         // In this context, the region is the metArea
         this.charts.forEach(function(chart) {
            chart.update(comparison, region, variable);
         });
      },

      // Wacky hack!  I don't know why "this" is defined in the update fuction,
      // but not in resize
      resize: function(myself, width) {
         myself.charts.forEach(function(chart) {
            chart.resize(width);
         });
      }
   });
});