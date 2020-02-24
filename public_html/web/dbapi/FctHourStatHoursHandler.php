<?php

require_once "Handler.php";

class FctHourStatHoursHandler extends Handler {
   public function __construct($conn, $method, $args, $body) {
      parent::__construct($conn, $method, $args, $body);
      $this->attNeedsQuote = Array('comparison' => false, 'region' => false, 'variable' => false);
   }

   public function get() {
      $argsOk = $this->checkBodyParts();
      if ($argsOk === true) {
         $query = "
            SELECT
               min(forecasthour) AS minHour,
               max(forecasthour) AS maxHour
            FROM
               hourStat
            WHERE
               comparison = {$this->args['comparison']} AND
               region = {$this->args['region']} AND
               variable = {$this->args['variable']}
         ";
         return $this->baseGet($query, null, null);
      } else {
         return $argsOk;
      }
   }
}

?>