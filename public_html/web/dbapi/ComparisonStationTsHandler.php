<?php

require_once "Handler.php";

class ComparisonStationReply {
   public $stationFound = false;
   public $stationInfo = null;
   public $integrations = null;
   public $observations = null;
}

class ComparisonStationTsHandler extends Handler {
   public function __construct($conn, $method, $args, $body) {
      parent::__construct($conn, $method, $args, $body);
      $this->attNeedsQuote = Array('comparison' => false, 'variable' => false, 'lon' => false, 'lat' => false, 'res' => false);
   }


   protected function get() {
      $cpr = new ComparisonStationReply();

      $query = "
         SELECT
            *
         FROM
            (
               SELECT
                  id,
                  ST_distance_spheroid(position, ST_SetSRID(ST_MakePoint({$this->args['lon']}, {$this->args['lat']}), 4326), 'SPHEROID[\"GRS_1980\",6378137,298.257222101]') AS distance,
                  name,
                  ST_X(position) AS lon,
                  ST_Y(position) AS lat,
                  (SELECT value FROM code_station WHERE code = 1 AND station = ssc.id) AS aqsid
               FROM
                  stationSnapshotContent AS ssc
               WHERE
                  stationSnapshot = (SELECT stationsnapshot FROM serie WHERE id = (SELECT base FROM comparison WHERE id = {$this->args['comparison']})) AND
                  id IN (
                     SELECT
                        station
                     FROM
                        stationstat
                     WHERE
                        stationstat.comparison = {$this->args['comparison']} AND
                        stationstat.variable = {$this->args['variable']} AND
                        stationstat.stationSnapshot = (SELECT stationsnapshot FROM serie WHERE id = (SELECT base FROM comparison WHERE id = {$this->args['comparison']})) AND
                        stationstat.serie = (SELECT base FROM comparison WHERE id = {$this->args['comparison']})
                  )
               ORDER BY
                  distance
               LIMIT
                  1
            ) AS stuff
         WHERE
            distance <= {$this->args['res']} * 10";
      $res = $this->conn->query($query);
      if ($res === false) {
         $response = new Response();
         $response->code = 400;
         $drvMsg = $this->conn->errorinfo();
         $erreur = array("message" => "Erreur lors de l'exécution de la requête!", "query" => $query, "driverMsg" => $drvMsg['2']);
         $response->body = json_encode($erreur);
         return $response;
      } else {
         if ( $res->rowCount() > 0 ) {
            $cpr->stationFound = true;

            $results = $res->fetchAll(PDO::FETCH_NAMED);
            $cpr->stationInfo = $results[0];
            # Get all the forecasts for the comparison, station and variable
            $query = "
               SELECT
                  baseInt.datestart,
                  baseFct.validityDate,
                  baseFct.value AS base,
                  testFct.value AS test
               FROM
                  comparison
               INNER JOIN
                  integration AS baseInt
               ON
                  comparison.base = baseInt.serie AND
                  comparison.datebegin <= baseInt.datestart AND
                  comparison.dateend >= baseInt.datestart
               INNER JOIN
                  integration AS testInt
               ON
                  comparison.test = testInt.serie AND
                  comparison.datebegin <= testInt.datestart AND
                  comparison.dateend >= testInt.datestart AND
                  baseInt.datestart = testInt.datestart
               INNER JOIN
                  forecast AS baseFct
               ON
                  baseFct.integration = baseInt.id AND
                  baseFct.variable = {$this->args['variable']} AND
                  baseFct.stationSnapshot = (SELECT stationSnapshot FROM serie WHERE id = comparison.base) AND
                  baseFct.station = {$cpr->stationInfo['id']}
               INNER JOIN
                  forecast AS testFct
               ON
                  testFct.integration = testInt.id AND
                  testFct.variable = {$this->args['variable']} AND
                  testFct.stationSnapshot = (SELECT stationSnapshot FROM serie WHERE id = comparison.test) AND
                  testFct.station = {$cpr->stationInfo['id']} AND
                  testFct.validityDate = baseFct.validityDate
               WHERE
                  comparison.id = {$this->args['comparison']}
               ORDER BY
                  datestart,
                  validityDate;
            ";
            $first = true;
            $minDate = null;
            $maxDate = null;
            $intStartDate = null;
            $cpr->integrations = Array();
            foreach ($this->conn->query($query) as $row) {
               if ($first) {
                  $first = false;
                  $minDate = $row['validitydate'];
               }
               $maxDate = $row['validitydate'];
               if ( $row['datestart'] != $intStartDate ) {
                  $intStartDate = $row['datestart'];
                  $cpr->integrations[$intStartDate] = Array();
               }
               array_push($cpr->integrations[$intStartDate], Array('date' => $row['validitydate'], 'base' => $row['base'], 'test' => $row['test']));
            }
            $query = "
               SELECT
                  dates.dates AS date,
                  obs.value
               FROM
                  generate_series(
                     '{$minDate}'::TIMESTAMPTZ,
                     '{$maxDate}'::TIMESTAMPTZ,
                     '1 hour'::INTERVAL
                  ) AS dates
               LEFT OUTER JOIN
                  (
                     SELECT
                        startdate AS date,
                        value
                     FROM
                        observation
                     INNER JOIN
                        comparison_region_dataset_method AS crdm
                     ON
                        crdm.comparison = {$this->args['comparison']} AND
                        crdm.region = (SELECT domain FROM comparison WHERE id = {$this->args['comparison']}) AND
                        crdm.dataset = observation.dataset AND
                        crdm.method = observation.method AND
                        (SELECT variable FROM method WHERE id = crdm.method) = {$this->args['variable']}
                     WHERE
                        duration = '1 hour' AND
                        station = {$cpr->stationInfo['id']} AND
                        startdate >= '{$minDate}' AND
                        startdate <= '{$maxDate}'
                  ) AS obs
               ON
                  dates.dates = date
               ORDER BY
                  date
            ";
            $res = $this->conn->query($query);
            if ($res === false) {
               $response = new Response();
               $response->code = 400;
               $drvMsg = $this->conn->errorinfo();
               $erreur = array("message" => "Erreur lors de l'exécution de la requête!", "query" => $query, "driverMsg" => $drvMsg['2']);
               $response->body = json_encode($erreur);
               return $response;
            } else {
               $cpr->observations = $res->fetchAll(PDO::FETCH_NAMED);
            }
         } // $res->rowCount() > 0

         return new Response(200, json_encode($cpr));
      }
   }
}

?>