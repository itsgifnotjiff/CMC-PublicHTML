<?php

require_once "Handler.php";

class StationStatVariableHandler extends Handler {
   public function __construct($conn, $method, $args, $body) {
      parent::__construct($conn, $method, $args, $body);
      $this->attNeedsQuote = Array('comparison' => false);
   }

   public function get() {
      $argsOk = $this->checkBodyParts();
      if ($argsOk === true) {
         $query = "
            SELECT
               *
            FROM
               (
                  SELECT
                     id,
                     abreviation AS name
                  FROM
                     variable
                  WHERE
                     id IN (
                        SELECT
                           variable
                        FROM
                           stationStat
                        WHERE
                           comparison = {$this->args['comparison']}
                     )
               ) AS variable";
         return $this->baseGet($query, 'name', 'name');
      } else {
         return $argsOk;
      }
   }
}

?>