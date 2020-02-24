<?php

require_once "Handler.php";

class MetAreaTsHandler extends Handler {
   public function __construct($conn, $method, $args, $body) {
      parent::__construct($conn, $method, $args, $body);
      $this->attNeedsQuote = Array('comparison' => false, 'metArea' => false, 'variable' => false);
   }

   public function get() {
      $argsOk = $this->checkBodyParts();
      if ($argsOk === true) {
         $query = "
            SELECT
               min(startDate),
               max(startDate)
            FROM
               metAreats
            WHERE
               comparison = {$this->args['comparison']} AND
               metArea = {$this->args['metArea']} AND
               variable = {$this->args['variable']}";
         $statement = $this->conn->query($query) or die( date("c") . " : " . "Query execution failed! Offending query :\n" . $query . "\n" . print_r($conn->errorinfo(), true) );
         $res = $statement->fetchAll();

         $query = "
            SELECT
               daterange.date,
               metAreats.obs,
               metAreats.base,
               metAreats.test
            FROM
               generate_series(
                  '{$res[0]['min']}'::TIMESTAMPTZ,
                  '{$res[0]['max']}'::TIMESTAMPTZ,
                  '1 hour'::INTERVAL
               ) AS daterange(date)
            LEFT OUTER JOIN
               (
                  SELECT
                     startDate,
                     obs,
                     base,
                     test
                  FROM
                     metAreaTs
                  WHERE
                     comparison = {$this->args['comparison']} AND
                     metArea = {$this->args['metArea']} AND
                     variable = {$this->args['variable']}
               ) AS metAreats
            ON
               daterange.date = metAreats.startDate";
         return $this->baseGet($query, null, 'startDate');
      } else {
         return $argsOk;
      }
   }
}

?>