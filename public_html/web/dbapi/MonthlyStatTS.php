<?php

require_once "Handler.php";
require_once "montlyStatConfigArray.php";

class MonthlyStatTS extends Handler {
   public function __construct($conn, $method, $args, $body) {
      parent::__construct($conn, $method, $args, $body);
      $this->attNeedsQuote = Array('monthlyStatConfig' => false, 'variable' => false, 'region' => true, 'forecastDay' => false, 'monthMin' => true, 'monthMax' => true, 'stat' => true);
   }


   public function get() {
      $argsOk = $this->checkBodyParts();
      if ($argsOk === true) {
         $result = Array();

         $monthMinStr = $this->conn->quote($this->args['monthMin'] . "-01");
         $monthMaxStr = $this->conn->quote($this->args['monthMax'] . "-01");

         # serie -> forecastDay -> variable -> region
         $query = "
            SELECT
               serieMonth.name AS serie,
               to_char(serieMonth.month, 'YYYY-MM') AS x,
               ts.stat AS y,
               ts.group as group
            FROM
               (
                  SELECT
                     serie.id,
                     serie.name,
                     months.months AS month
                  FROM
                     monthlyStatConfigSerie AS mscs
                  CROSS JOIN
                     generate_series(
                        {$monthMinStr}::TIMESTAMP,
                        {$monthMaxStr}::TIMESTAMP,
                        '1 month'::INTERVAL
                     ) AS months
                  INNER JOIN
                     serie
                  ON
                     mscs.serie = serie.id
                  WHERE
                     mscs.monthlyStatConfig = {$this->args['monthlyStatConfig']}
               ) AS serieMonth
            LEFT OUTER JOIN
               (
                  SELECT
                     tmp.serie,
                     tmp.month,
                     tmp.{$this->args['stat']} AS stat,
                     tmp.group
                  FROM
                     (
                        SELECT
                           serie,
                           month,
                           (stats).*,
                           aqpi((stats).fac2, (stats).corr, (stats).fb),
                           station_pop_class_group as group
                        FROM
                           monthlyStat
                        WHERE
                           monthlyStatConfig = {$this->args['monthlyStatConfig']} AND
                           variable = {$this->args['variable']} AND
                           region = {$this->args['region']} AND
                           forecastDay = {$this->args['forecastDay']} AND
                           month >= {$monthMinStr} AND
                           month <= {$monthMaxStr}
                     ) AS tmp
               ) AS ts
            ON
               serieMonth.id = ts.serie AND
               serieMonth.month = ts.month
            ORDER BY
               serie,
               x,
               \"group\"";
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
         $result = Array();
         foreach ($this->conn->query($query) as $row) {
            if ( is_null($row['group']) ) {// no data for this month
               foreach ($cnts[$row['serie']] as $group => $count ) {
                  $cnts[$row['serie']] [$group]++;

                  $result[$row['serie']] [$group] [$cnts[$row['serie']][$group]] ['x'] = $row['x'];
                  $result[$row['serie']] [$group] [$cnts[$row['serie']][$group]] ['y'] = $row['y']; // should be null
               }
            } else {
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
               $result[$row['serie']] [$row['group']] [$cnts[$row['serie']][$row['group']]] ['y'] = $row['y'];
            }
         }

         return new Response(200, json_encode($result));
      } else {
         return $argsOk;
      }
   }
}

?>