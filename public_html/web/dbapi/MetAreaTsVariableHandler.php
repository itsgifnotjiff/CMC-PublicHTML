<?php

require_once "Handler.php";

class MetAreaTsVariableHandler extends Handler {
   public function __construct($conn, $method, $args, $body) {
      parent::__construct($conn, $method, $args, $body);
      $this->attNeedsQuote = Array('comparison' => false, 'region' => false);
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
                     abreviation
                  FROM
                     variable
                  WHERE
                     id IN (
                        SELECT DISTINCT
                           variable
                        FROM
                           metAreaTs
                        WHERE
                           comparison = {$this->args['comparison']} AND
                           metArea = {$this->args['region']}
                     )
               ) AS dummy";
         return $this->baseGet($query, 'abreviation', 'abreviation');
      } else {
         return $argsOk;
      }
   }
}

?>