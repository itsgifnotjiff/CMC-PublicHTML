<?php

require_once "Handler.php";

class metAreaTsRegionHandler extends Handler {
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
                     name AS name_en_ca
                  FROM
                     metArea
                  WHERE
                     id IN (
                        SELECT DISTINCT
                           metArea
                        FROM
                           metAreaTs
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