<?php

require_once "Handler.php";

class MonthlyFctHourStat extends Handler {
   public function __construct($conn, $method, $args, $body) {
      parent::__construct($conn, $method, $args, $body);
      $this->attNeedsQuote = Array('monthlyStatConfig' => false, 'region' => false, 'month'
      => true, 'variable' => false, 'stat' => true);
      $this->fctObsStats = Array('avg', 'std');
   }


   public function get() {
      $argsOk = $this->checkBodyParts();
      if ($argsOk === true) {
         $result = Array();

         $quotedMonthStr = $this->conn->quote($this->args['month'] . "-01");

         $statExtra = '';
         if ( in_array($this->args['stat'], $this->fctObsStats)  ) {
            $statExtra = 'y';
            $query = "
               SELECT
                  forecastHour AS x,
                  {$this->args['stat']}X AS y,
                  tmp.group
               FROM
                  (
                     SELECT
                        serie,
                        forecastHour,
                        (stats).*,
                        aqpi((stats).fac2, (stats).corr, (stats).fb),
                        station_pop_class_group as group
                     FROM
                        monthlyFctHourStat
                     WHERE
                        monthlyStatConfig = {$this->args['monthlyStatConfig']} AND
                        month = {$quotedMonthStr} AND
                        region = {$this->args['region']} AND
                        variable = {$this->args['variable']} AND
                        serie = (
                              SELECT
                                 serie
                              FROM
                                 monthlyStatConfigSerie
                              WHERE
                                 monthlyStatConfig = {$this->args['monthlyStatConfig']}
                              LIMIT
                                 1
                           ) -- We take the a series sinces obs should be the same for all of them
                  ) AS tmp
               ORDER BY
                  forecastHour;";
            $res = $this->conn->query($query);
            if ($res === false) {
               $response = new Response();
               $response->code = 404;
               $drvMsg = $this->conn->errorinfo();
               $erreur = array("message" => "Erreur lors de l'exécution de la requête!", "query" => $query, "driverMsg" => $drvMsg['2']);
               $response->body = json_encode($erreur);
               return $response;
            }

            $obsCnt = Array();
            foreach ($res as $row) {
               if (!array_key_exists($row['group'], $obsCnt)) {
                  $obsCnt[$row['group']] = 0;
               } else {
                  $obsCnt[$row['group']]++;
               }

               $result['Observations'] [$row['group']] [$obsCnt[$row['group']]] ['x'] = $row['x'];
               $result['Observations'] [$row['group']] [$obsCnt[$row['group']]] ['y'] = floatval($row['y']);
            }
         }

         $query = "
            SELECT
               serie.name AS serie,
               tmp.forecastHour AS x,
               {$this->args['stat']}{$statExtra} AS y,
               tmp.group
            FROM
               (
                  SELECT
                     serie,
                     forecastHour,
                     (stats).*,
                     aqpi((stats).fac2, (stats).corr, (stats).fb),
                     station_pop_class_group as group
                  FROM
                     monthlyFctHourStat
                  WHERE
                     monthlyStatConfig = {$this->args['monthlyStatConfig']} AND
                     month = {$quotedMonthStr} AND
                     region = {$this->args['region']} AND
                     variable = {$this->args['variable']}
               ) AS tmp
            INNER JOIN
               serie
            ON
               tmp.serie = serie.id
            ORDER BY
               serie.name,
               tmp.forecastHour;";
         $res = $this->conn->query($query);
         if ($res === false) {
            $response = new Response();
            $response->code = 404;
            $drvMsg = $this->conn->errorinfo();
            $erreur = array("message" => "Erreur lors de l'exécution de la requête!", "query" => $query, "driverMsg" => $drvMsg['2']);
            $response->body = json_encode($erreur);
            return $response;
         }

         $cnts = Array();
         foreach ($res as $row) {
            if (!array_key_exists($row['serie'], $cnts)) {
               $cnts[$row['serie']] [$row['group']] = 0;
            } else {
               if (!array_key_exists($row['group'], $cnts[$row['serie']])) {
                  $cnts[$row['serie']] [$row['group']] = 0;
               } else {
                  $cnts[$row['serie']] [$row['group']]++;
               }
            }

            $result[$row['serie']] [$row['group']] [$cnts[$row['serie']][$row['group']]] ['x'] = $row['x'];
            $result[$row['serie']] [$row['group']] [$cnts[$row['serie']][$row['group']]] ['y'] = floatval($row['y']);
         }

         return new Response(200, json_encode($result));
      } else {
         return $argsOk;
      }
   }
}

?>