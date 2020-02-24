<?php

require_once "Handler.php";
require_once "montlyStatConfigArray.php";

class MonthlyStat extends Handler {
   public function __construct($conn, $method, $args, $body) {
      parent::__construct($conn, $method, $args, $body);
      $this->attNeedsQuote = Array('monthlyStatConfig' => false, 'month' => true, 'group' => false);
   }

   public function get() {
      $argsOk = $this->checkBodyParts();
      if ($argsOk === true) {
         $monthStr = $this->conn->quote($this->args['month'] . "-01");
         $result = Array();
         $result['config'] = configArray($this->conn, $this->args['monthlyStatConfig']);
         $result['month'] = $this->args['month'];
         $result['group'] = $this->args['group'];

         $baseQuery = "
            SELECT
               serie.name AS serie,
               ms.forecastDay,
               variable.abreviation AS variable,
               region.abreviation AS region,
               (ms.stats).*,
               aqpi((ms.stats).fac2, (ms.stats).corr, (ms.stats).fb)
            FROM
               (
                  SELECT
                     serie,
                     forecastDay,
                     variable,
                     region,
                     avg(stats) AS stats
                  FROM
                     monthlyStat
                  WHERE
                     monthlyStatConfig = {$this->args['monthlyStatConfig']} AND
                     station_pop_class_group = {$this->args['group']} AND
                     <timeFilter> 
                  GROUP BY
                     serie,
                     forecastDay,
                     variable,
                     region
               ) AS ms
            INNER JOIN
               serie
            ON
               ms.serie = serie.id
            INNER JOIN
               variable
            ON
               ms.variable = variable.id
            INNER JOIN
               region
            ON
               ms.region = region.id
            ORDER BY
               serie,
               forecastDay,
               variable,
               region";


         $timeFilter = "
                     month = date_trunc('month', {$monthStr}::TIMESTAMPTZ)";

         $query = str_replace("<timeFilter>", $timeFilter, $baseQuery);
         $res = $this->conn->query($query);
         if ($res === false) {
            $response = new Response();
            $response->code = 404;
            $drvMsg = $this->conn->errorinfo();
            $erreur = array("message" => "Erreur lors de l'exécution de la requête!", "query" => $query, "driverMsg" => $drvMsg['2']);
            $response->body = json_encode($erreur);
            return $response;
         }

         foreach ($this->conn->query($query) as $row) {
            foreach ($row as $field => $value) {
               if ( $field != 'serie' && $field != 'forecastday' && $field != 'variable' && $field != 'region' ) {
                  $result['serie'][$row['serie']]['forecastDay'][$row['forecastday']]['variable'][$row['variable']]['region'][$row['region']]['current'][$field] = $value;
               }
            }
         }


         $timeFilter = "
                     date_part('month', month) = date_part('month', {$monthStr}::TIMESTAMPTZ) AND
                     month <= {$monthStr} AND
                     month > {$monthStr}::TIMESTAMPTZ - make_interval(years := 5)";
         $query = str_replace("<timeFilter>", $timeFilter, $baseQuery);
         $res = $this->conn->query($query);
         if ($res === false) {
            $response = new Response();
            $response->code = 404;
            $drvMsg = $this->conn->errorinfo();
            $erreur = array("message" => "Erreur lors de l'exécution de la requête!", "query" => $query, "driverMsg" => $drvMsg['2']);
            $response->body = json_encode($erreur);
            return $response;
         }

         // foreach ($this->conn->query($query) as $row) {
         foreach ($res as $row) {
            foreach ($row as $field => $value) {
               if ( $field != 'serie' && $field != 'forecastday' && $field != 'variable' && $field != 'region' ) {
                  $result['serie'][$row['serie']]['forecastDay'][$row['forecastday']]['variable'][$row['variable']]['region'][$row['region']]['avg5years'][$field] = $value;
               }
            }
         }

         return new Response(200, json_encode($result));
      } else {
         return $argsOk;
      }
   }
}

?>