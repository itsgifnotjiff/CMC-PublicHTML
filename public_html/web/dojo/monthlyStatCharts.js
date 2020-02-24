define([
   "dojo/_base/declare",
   "dojo/dom-construct",
   "dojo/_base/lang",
   "dojo/request",
   "vaqum/monthlyStatChart"
],
function(declare, domConstruct, lang, request, MonthlyStatChart) {
   return declare(null, {
      charts: null,

      constructor: function(monthlyStatConfig, plotAreaNode) {
         this.charts = [];

         request.get(
            "../../rodb/monthlyStatConfig",
            {
               handleAs: "json",
               query: {
                  id: monthlyStatConfig
               }
            }
         ).then(lang.hitch(this, function(reply) {
            var myself = this;
            reply.variables.forEach(function (variable) {
               var containerNode = domConstruct.create("div", {class: 'monthlyStatGraphContainer'}, plotAreaNode);
               myself.charts.push(new MonthlyStatChart(containerNode, variable.id, variable.name));
            });
         }));
      },

      update: function(monthlyStatConfig, region, forecastDay, monthMin, monthMax, stat, groups) {
         this.charts.forEach(function(chart) {
            chart.update(monthlyStatConfig, region, forecastDay, monthMin, monthMax, stat, groups);
         });
      },

      updateGroupVisibility: function(group, visible) {
         if (this.charts) {
            this.charts.forEach(function(chart) {
               chart.updateGroupVisibility(group, visible);
            });
         }
      }

   });
});