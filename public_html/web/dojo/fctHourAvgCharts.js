define([
   "dojo/_base/declare",
   "dojo/dom-construct",
   "vaqum/fctHourAvgChart"
],
function(declare, domConstruct, fctHourAvgChart) {
   return declare(null, {
      charts: null,

      constructor: function(plotAreaNode, legendNode) {
         this.charts = [];
         for (startHour of ["00", "12"]) {
            var containerNode = domConstruct.create("div", {class: 'fctHourGraphContainer'}, plotAreaNode);
            this.charts.push(fctHourAvgChart(containerNode, startHour));
         }

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
         this.charts.forEach(function(chart) {
            chart.update(comparison, region, variable);
         });
      }
   });
});