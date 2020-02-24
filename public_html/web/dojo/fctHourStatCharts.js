define([
   "dojo/_base/declare",
   "dojo/dom-construct",
   "vaqum/fctHourStatChart"
],
function(declare, domConstruct, fctHourStatChart) {
   return declare(null, {
      charts: null,

      constructor: function(plotAreaNode, legendNode) {
         this.charts = [];
         for (stat of ['mbias', 'rmse', 'corr']) {
            var containerNode = domConstruct.create("div", {class: 'fctHourGraphContainer'}, plotAreaNode);
            this.charts.push(fctHourStatChart(containerNode, stat));
         }
         legendNode.innerHTML = `
<table>
   <tr>
      <th>Run / Passe</th>
      <th>Base</th>
      <th>Test</th>
   </tr>
   <tr>
      <th>00</th>
      <td class="Base00"></td>
      <td class="Test00"></td>
   </tr>
   <tr>
      <th>12</th>
      <td class="Base12"></td>
      <td class="Test12"></td>
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