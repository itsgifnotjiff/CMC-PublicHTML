<?php

//! Factory for the different JSON/Rest handlers
class HandlerFactory {
   public static function create($vdb, $method, $args, $data) {
      switch ( $args['table'] ) {
         case 'binStat' :
            require_once "BinStatHandler.php";
            return new BinStatHandler($vdb, $method, $args, $data);
            break;
         case 'binStatComparison' :
            require_once "BinStatComparisonHandler.php";
            return new BinStatComparisonHandler($vdb, $method, $args, $data);
            break;
         case 'binStatRegion' :
            require_once "BinStatRegionHandler.php";
            return new BinStatRegionHandler($vdb, $method, $args, $data);
            break;
         case 'binStatVariable' :
            require_once "BinStatVariableHandler.php";
            return new BinStatVariableHandler($vdb, $method, $args, $data);
            break;
         case 'comparison' :
            require_once "ComparisonHandler.php";
            return new ComparisonHandler($vdb, $method, $args, $data);
            break;
         case 'comparisonMetAreaVariable' :
            require_once "ComparisonMetAreaVariableHandler.php";
            return new ComparisonMetAreaVariableHandler($vdb, $method, $args, $data);
            break;
         case 'comparisonStationTs' :
            require_once "ComparisonStationTsHandler.php";
            return new ComparisonStationTsHandler($vdb, $method, $args, $data);
            break;
         case 'executable' :
            require_once "ExecutableHandler.php";
            return new ExecutableHandler($vdb, $method, $args, $data);
            break;
         case 'dataset' :
            require_once "DatasetHandler.php";
            return new DatasetHandler($vdb, $method, $args, $data);
            break;
         case 'duration' :
            require_once "DurationHandler.php";
            return new DurationHandler($vdb, $method, $args, $data);
            break;
         case 'grid' :
            require_once "GridHandler.php";
            return new GridHandler($vdb, $method, $args, $data);
            break;
         case 'implementation' :
            require_once "ImplementationHandler.php";
            return new ImplementationHandler($vdb, $method, $args, $data);
            break;
         case 'inventory' :
            require_once "InventoryHandler.php";
            return new InventoryHandler($vdb, $method, $args, $data);
            break;
         case 'integration' :
            require_once "IntegrationHandler.php";
            return new IntegrationHandler($vdb, $method, $args, $data);
            break;
         case 'integrationStatConfigVariable' :
            require_once "IntegrationStatConfigVariableHandler.php";
            return new IntegrationStatConfigVariableHandler($vdb, $method, $args, $data);
            break;
         case 'integrationStatDates' :
            require_once "IntegrationStatDates.php";
            return new IntegrationStatDates($vdb, $method, $args, $data);
            break;
         case 'integrationStats' :
            require_once "IntegrationStats.php";
            return new IntegrationStats($vdb, $method, $args, $data);
            break;
         case 'integrationStatSeries' :
            require_once "IntegrationStatSeries.php";
            return new IntegrationStatSeries($vdb, $method, $args, $data);
            break;
         case 'integrationStatVariables' :
            require_once "IntegrationStatVariables.php";
            return new IntegrationStatVariables($vdb, $method, $args, $data);
            break;
         case 'interpolation' :
            require_once "InterpolationHandler.php";
            return new InterpolationHandler($vdb, $method, $args, $data);
            break;
         case 'metAreaTs' :
            require_once "MetAreaTsHandler.php";
            return new MetAreaTsHandler($vdb, $method, $args, $data);
            break;
         case 'method' :
            require_once "MethodHandler.php";
            return new MethodHandler($vdb, $method, $args, $data);
            break;
         case 'model' :
            require_once "ModelHandler.php";
            return new ModelHandler($vdb, $method, $args, $data);
            break;
         case 'monthlyFctHourStat' :
            require_once "MonthlyFctHourStat.php";
            return new MonthlyFctHourStat($vdb, $method, $args, $data);
            break;
         case 'monthlyStat' :
            require_once "MonthlyStat.php";
            return new MonthlyStat($vdb, $method, $args, $data);
            break;
         case 'monthlyStatConfig' :
            require_once "MonthlyStatConfig.php";
            return new MonthlyStatConfig($vdb, $method, $args, $data);
            break;
         case 'monthlyStatMonth' :
            require_once "MonthlyStatMonth.php";
            return new MonthlyStatMonth($vdb, $method, $args, $data);
            break;
         case 'monthlyStatGroup' :
            require_once "MonthlyStatGroup.php";
            return new MonthlyStatGroup($vdb, $method, $args, $data);
            break;
         case 'monthlyStatTS' :
            require_once "MonthlyStatTS.php";
            return new MonthlyStatTS($vdb, $method, $args, $data);
            break;
         case 'observation' :
            require_once "ObservationHandler.php";
            return new ObservationHandler($vdb, $method, $args, $data);
            break;
         case 'region' :
            require_once "RegionHandler.php";
            return new RegionHandler($vdb, $method, $args, $data);
            break;
         case 'serie' :
            require_once "SerieHandler.php";
            return new SerieHandler($vdb, $method, $args, $data);
            break;
         case 'station':
            include "StationHandler.php";
            return new StationHandler($vdb, $method, $args, $data);
            break;
         case 'stationSnapshot' :
            require_once "StationSnapshotHandler.php";
            return new StationSnapshotHandler($vdb, $method, $args, $data);
            break;
         case 'stationStatComparison' :
            require_once "StationStatComparisonHandler.php";
            return new StationStatComparisonHandler($vdb, $method, $args, $data);
            break;
         case 'stationStatVariable' :
            require_once "StationStatVariableHandler.php";
            return new StationStatVariableHandler($vdb, $method, $args, $data);
            break;
         case 'fctHourStat' :
            require_once "FctHourStatHandler.php";
            return new FctHourStatHandler($vdb, $method, $args, $data);
            break;
         case 'fctHourStatRegion' :
            require_once "FctHourStatRegionHandler.php";
            return new FctHourStatRegionHandler($vdb, $method, $args, $data);
            break;
         case 'fctHourStatVariable' :
            require_once "FctHourStatVariableHandler.php";
            return new FctHourStatVariableHandler($vdb, $method, $args, $data);
            break;
         case 'fctHourStatHours' :
            require_once "FctHourStatHoursHandler.php";
            return new FctHourStatHoursHandler($vdb, $method, $args, $data);
            break;
         case 'fctHourStatVariableRegion' :
            require_once "FctHourStatVariableRegionHandler.php";
            return new FctHourStatVariableRegionHandler($vdb, $method, $args, $data);
            break;
         case 'fctHourAvg' :
            require_once "FctHourAvgHandler.php";
            return new FctHourAvgHandler($vdb, $method, $args, $data);
            break;
         case 'fctHourAvgRegion' :
            require_once "FctHourAvgRegionHandler.php";
            return new FctHourAvgRegionHandler($vdb, $method, $args, $data);
         case 'fctHourAvgVariable' :
            require_once "FctHourAvgVariableHandler.php";
            return new FctHourAvgVariableHandler($vdb, $method, $args, $data);
            break;
         case 'metAreaTsRegion' :
            require_once "MetAreaTsRegionHandler.php";
            return new MetAreaTsRegionHandler($vdb, $method, $args, $data);
            break;
         case 'metAreaTsVariable' :
            require_once "MetAreaTsVariableHandler.php";
            return new MetAreaTsVariableHandler($vdb, $method, $args, $data);
            break;
         default :
            require_once "NotFoundHandler.php";
            return new NotFoundHandler($args['table']);
            break;
      }
   }
}

?>