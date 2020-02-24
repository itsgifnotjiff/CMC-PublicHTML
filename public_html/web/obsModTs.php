<?php
   require_once("include/error2exception.php");

   #----------------------------------------------------------------------------
   # M A I N
   # 
   # Someday, we can move this to a python script with sanitized inputs
   # Until then, we can just pray that none of our users has read xkcd#327.
   #
   #----------------------------------------------------------------------------
   if (PHP_SAPI == "cli") {

      if (14 == $argc) {
         $serieIds = $argv[1];

         $numbersCsvPattern = "/(\d+,)*\d+/"; # numbers separated by commas
         preg_match( $numbersCsvPattern , $serieIds, $matches);
         if ($matches[0] != $serieIds) {
            die("\n\nInvalid serie Id(s)! use numbers only separated with commas eg. 1,2,3\n\n");
         }

         $stationsnapshot = $argv[2];
         $variableAbrv = $argv[3];

         $radius = $argv[4];
         $lat = $argv[5];
         $lon = $argv[6];

         $regionIDs = $argv[7];

         $matches = null;
         $regionsPattern = "/(\d+)(-(\d+))?(,(\d+)(-(\d+))?)*/";

         preg_match( $regionsPattern , $regionIDs, $matches);

         if ($regionIDs != "all") {
            if ( empty($matches) or $matches[0] != $regionIDs) {
               die("\n\nInvalid region Id(s)! use numbers and/or ranges separated with commas or the word 'all' eg. 1-3,7\n\n");
            }
         }

         $dateBegin = $argv[8];
         $dateEnd = $argv[9];
         $hourBegin = $argv[10];
         $hourEnd = $argv[11];
         $runs = $argv[12];
         $outputFilePath = $argv[13];
      } else {
         print("Generate time series of a specified series and observation over a given period\n\n");
         print("Usage :\n");
         print("\tphp5 {$argv[0]} \\\n");
         print("\t\t<List of Serie IDs separated by commas eg. 1,2,3> \\\n");
         print("\t\t<Station Snapshot ID for all series (e.g. 9)> \\\n");
         print("\t\t<Pollutant abbreviation> \\\n");
         print("\t\t<Maximum distance of stations (m), or 'infinite' for all stations> \\\n");
         print("\t\t<lat (if radius given is 'infinite', will be ignored)> \\\n");
         print("\t\t<lon (if radius given is 'infinite', will be ignored)> \\\n");
         print("\t\t<List of Region IDs separated by commas, can be in range form (e.g. 1-3,5), for stations only in given regions, or 'all' for all regions. 7 for Canada only, 8 for USA excluding US territories, 12-24 for canadian provinces, 37-87 for US states. See the region table in verif5 database for appropriate IDs> \\\n");
         print("\t\t<Date begin> \\\n");
         print("\t\t<Date end> \\\n");
         print("\t\t<Forecast begin hour (inclusive)> \\\n");
         print("\t\t<Forecast end hour (inclusive)> \\\n");
         print("\t\t<Runs (0|12|0-12)> \\\n");
         print("\t\t<Output File Path> \\\n");
         print("Example :\n");
         print("\tphp5 {$argv[0]} 398,401 9 O3 5000 45.515489 -73.561517 12-14,8 2017-01-01 2017-01-07 0 48 0-12 /tmp/ts.csv\n");
         exit(1);
      }
   } else {
      die("This script is only meant to be called in a shell!");
   }

   $outputFile = fopen($outputFilePath, "w") or die("Unable to open file {$outputFilePath}!");

   require_once "include/VaqumDb.php";
   $conn = new VaqumDb(false);

   $query = "SELECT id FROM variable WHERE abreviation = " . $conn->quote($variableAbrv);
   $statement = $conn->query($query);
   if ($statement === false) {
      print("Query execution failed! Offending query :\n" );
      print($query);
      print("\t");
      print_r($conn->errorinfo(), true);
      exit(1);
   }
   $result = $statement->fetchAll();
   $variableId = $result[0]['id'];

   $dateBeginSafe = $conn->quote($dateBegin);
   $dateEndSafe = $conn->quote($dateEnd);

   if ($radius != 'infinite') {
      $radiusCheck = " AND
         ST_distance_spheroid(
            ST_SetSRID(ST_MakePoint({$lon}, {$lat}), 4326),
            station.position,
            'SPHEROID[\"WGS 84\",6378137,298.257223563,AUTHORITY[\"EPSG\",\"7030\"]]'
         ) < {$radius} 
         ";
   } else {
      $radiusCheck = "";
   }
   

   if ($regionIDs != "all") {
      $regionIDsExploded = explode(',', $regionIDs);

      $regionJoin = "INNER JOIN region
      ON
         ";
      $regionIn = null;
      $regionBetween = null;
      foreach ($regionIDsExploded as $r) {

         if (strpos($r, '-') !== false) {
            $limits = explode('-', $r);
            $limitleft = $limits[0];
            $limitright = $limits[1];
            
            if (is_null($regionBetween)) {
               $regionBetween = " ( region.id BETWEEN ${limitleft} and ${limitright} ) ";
            } else {
               $regionBetween .= " OR ( region.id BETWEEN ${limitleft} and ${limitright} ) ";
            }
            
         } else {
            if (is_null($regionIn)) {
               $regionIn = " ( ${r}";
            } else {
               $regionIn .= ",${r}";
            }
         }
      } 
      if (!is_null($regionIn)) {
         $regionIn .= " ) ";
         $regionJoin .= " ( region.id in ${regionIn} ";
      }
      if (!is_null($regionBetween)) {
         if (!is_null($regionIn)) {
            $regionJoin .= " OR 
            ";
         } else {
            $regionJoin .= " ( ";
         }
         $regionJoin .= $regionBetween;
      }

      $regionJoin .= " ) AND ST_CONTAINS(region.geometry, station.position)";
   } else {
      $regionJoin = "";
   }

   $query = "
      CREATE TEMPORARY TABLE areaStation AS SELECT
         station.id,
         code_station.value AS code
      FROM
         stationsnapshotcontent AS station
      INNER JOIN
         code_station
      ON
         code_station.code = 1 AND
         code_station.station = station.id
      INNER JOIN
         observation
      ON
         station.id = observation.station
      ${regionJoin}
      WHERE
         observation.duration = '1 hour' AND
         observation.dataset IN (3, 4, 6, 8, 9) AND
         station.stationsnapshot = {$stationsnapshot} AND
         observation.method IN (SELECT id FROM method WHERE id IN (128, 129, 130, 131, 132, 134, 135, 137, 139) AND variable = {$variableId}) AND
         observation.startDate >= {$dateBeginSafe} AND
         observation.startDate < {$dateEndSafe} 
         ${radiusCheck}
      GROUP BY
         station.id,
         code_station.value
      HAVING
         count(*) > 0";
   print($query . "\n");
   $conn->niceExec($query);


   $dates = array();
   $query = "SELECT generate_series({$dateBeginSafe}::TIMESTAMP WITH TIME ZONE, {$dateEndSafe}::TIMESTAMP WITH TIME ZONE - '1 hour'::INTERVAL, '1 hour'::INTERVAL) AS date";
   print($query . "\n");
   $statement = $conn->query($query);
   if ($statement === false) {
      print("Query execution failed! Offending query :\n" );
      print($query);
      print("\n");
      print_r($conn->errorinfo(), true);
      exit(1);
   }
   foreach ( $statement as $row ) {
      array_push($dates, $row['date']);
   }

   $runFilter = "";
   switch ($runs) {
      case "0" :
         $runFilter = "date_part('hour', integration.dateStart) = 0 AND";
         break;
      case "12" :
         $runFilter = "date_part('hour', integration.dateStart) = 12 AND";
         break;
      case "0-12" :
         $runFilter = "";
         break;
      default:
         print("Invalid run filter: \"{$runs}\"!  Must be (0|12|0-12)!" );
         exit(1);
   }

   $query = "
      SELECT
         forecast.validityDate AS date,
         forecast.station,
         integration.serie,
         to_char(avg(forecast.value),  '999999.000000FM') AS value
      FROM
         forecast
      INNER JOIN
         integration
      ON
         forecast.integration = integration.id
      WHERE
         forecast.variable = {$variableId} AND
         forecast.station IN (SELECT id FROM areaStation) AND
         forecast.validityDate >= {$dateBeginSafe} AND
         forecast.validityDate < {$dateEndSafe} AND
         integration.serie in ({$serieIds}) AND
         {$runFilter}
         (forecast.validityDate - integration.dateStart) >= '{$hourBegin} hours' AND
         (forecast.validityDate - integration.dateStart) <= '{$hourEnd} hours'
      GROUP BY
         forecast.validityDate,
         forecast.station,
         integration.serie";
   print($query . "\n");
   $statement = $conn->query($query);
   if ($statement === false) {
      print("Query execution failed! Offending query :\n" );
      print($query);
      print("\n");
      print_r($conn->errorinfo(), true);
      exit(1);
   }
   foreach ( $statement as $row ) {
      $forecasts[$row['date']][$row['station']][$row['serie']] = $row['value'];
   }

   $query = "
      SELECT
         startdate AS date,
         station,
        to_char(avg(value),  '999999.000000FM') AS value
      FROM
         observation
      WHERE
         duration = '1 hour' AND
         method IN (SELECT id FROM method WHERE variable = {$variableId}) AND
         startDate >= {$dateBeginSafe} AND
         startDate < {$dateEndSafe} AND
         station IN (SELECT id FROM areaStation)
      GROUP BY
         startdate,
         station";
   print($query . "\n");
   $statement = $conn->query($query);
   if ($statement === false) {
      print("Query execution failed! Offending query :\n" );
      print($query);
      print("\n");
      print_r($conn->errorinfo(), true);
      exit(1);
   }
   foreach ( $statement as $row ) {
      $observations[$row['date']][$row['station']] = $row['value'];
   }

   $query = "SELECT id, code FROM areaStation";
   $statement = $conn->query($query);
   if ($statement === false) {
      print("Query execution failed! Offending query :\n" );
      print($query);
      print("\n");
      print_r($conn->errorinfo(), true);
   }
   foreach ( $statement as $row ) {
      $stations[$row['id']] = $row['code'];
   }
   if (!isset($stations)) {
      print("No station found!\n");
      $conn = null;
      exit(1);
   }

   # Print CSV header TODO loop series
   $seriesSplit = explode(",", $serieIds);
   fwrite($outputFile, "date|");
   foreach ( $stations as $station ) {
      fwrite($outputFile, $station . " (obs)|");
      foreach ($seriesSplit as $serie) {
         fwrite($outputFile, $station . " (fct-{$serie})|");
      }
   }
   fwrite($outputFile, "\n");

   foreach ( $dates as $date ) {
      fwrite($outputFile, $date . "|");
      foreach ( $stations as $idx => $code ) {
         if ( isset($observations[$date][$idx]) ) {
            fwrite($outputFile, $observations[$date][$idx]);
         }
         fwrite($outputFile, "|");
            # loop over all given series for forecasts
         foreach ($seriesSplit as $serie) {
            if ( isset($forecasts[$date][$idx][$serie]) ) {
               fwrite($outputFile, $forecasts[$date][$idx][$serie]);
            }
            fwrite($outputFile, "|");
         }
      }
      fwrite($outputFile, "\n");
   }
   
   fclose($outputFile);
   $conn = null;
?>