<?php

require_once "Handler.php";

class FctHourStatVariableRegionHandler extends Handler {
   public function __construct($conn, $method, $args, $body) {
      parent::__construct($conn, $method, $args, $body);
      $this->attNeedsQuote = Array('comparison' => false);
   }

   public function get() {
      $argsOk = $this->checkBodyParts();
      if ($argsOk === true) {
         $query = "
            SELECT
               variable,
               (SELECT abreviation FROM variable WHERE variable.id = hourStat.variable) AS variableAbrv,
               region,
               (SELECT abreviation FROM region WHERE region.id = hourStat.region) AS regionAbrv,
               min(forecasthour) AS minHour,
               max(forecasthour) AS maxHour
            FROM
               hourStat
            WHERE
               comparison = {$this->args['comparison']}
            GROUP BY
               variable,
               region
            ORDER BY
               variableAbrv,
               regionAbrv;";
         return $this->baseGet($query, NULL, NULL);
      } else {
         return $argsOk;
      }
   }
}

?>