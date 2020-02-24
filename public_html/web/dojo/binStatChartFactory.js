define([
   "dojo/_base/declare",
   "vaqum/binStatChartCount",
   "vaqum/binStatChartCorr",
   "vaqum/binStatChartMbias",
   "vaqum/binStatChartRmse"
],
function(declare, ChartCount, ChartCorr, ChartMbias, ChartRmse) {
   return declare(null, {
      create: function(chartNode, stat) {
         switch (stat) {
            case 'count' :
               return new ChartCount(chartNode, stat);
               break;
            case 'mbias' :
               return new ChartMbias(chartNode, stat);
               break;
            case 'corr' :
               return new ChartCorr(chartNode, stat);
               break;
            case 'rmse' :
               return new ChartRmse(chartNode, stat);
               break;
            case 'urmse' :
               return new ChartRmse(chartNode, stat);
               break;
            default :
               console.error('Unknown binStatChart type ', stat);
         }
      }
   });
});