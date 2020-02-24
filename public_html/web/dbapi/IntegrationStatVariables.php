<?php

require_once "Handler.php";

class IntegrationStatVariables extends Handler {
   public function __construct($conn, $method, $args, $body) {
      parent::__construct($conn, $method, $args, $body);
      $this->attNeedsQuote = Array('dateMin' => true, 'dateMax' => true);
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
                           integrationStat.variable
                        FROM
                           integrationStat
                        INNER JOIN
                           integration
                        ON
                           integrationStat.integration = integration.id
                        WHERE
                           integration.dateStart >= '{$this->args['dateMin']}' AND
                           date_trunc('day', integration.dateStart) <= '{$this->args['dateMax']}'
                     )
               ) AS pouet
            ";
         return $this->baseGet($query, 'name', 'name');
      } else {
         return $argsOk;
      }
   }
}

?>