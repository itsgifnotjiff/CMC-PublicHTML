<?php

require_once "Handler.php";

class FctHourStatRegionHandler extends Handler {
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
                     name_en_CA,
                     name_fr_CA,
                     abreviation
                  FROM
                     region
                  WHERE
                     id IN (
                        SELECT DISTINCT
                           region
                        FROM
                           hourStat
                        WHERE
                           comparison = {$this->args['comparison']}
                     )
               ) AS dummy";
         return $this->baseGet($query, 'name_en_ca', 'name_en_ca');
      } else {
         return $argsOk;
      }
   }
}

?>