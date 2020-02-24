<?php

require_once "Handler.php";

class IntegrationStatSeries extends Handler {
   public function __construct($conn, $method, $args, $body) {
      parent::__construct($conn, $method, $args, $body);
      $this->attNeedsQuote = Array('dateMin' => true, 'dateMax' => true, variable => false);
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
                     name
                  FROM
                     serie
                  WHERE
                     id IN (
                        SELECT
                           integration.serie
                        FROM
                           integrationStat
                        INNER JOIN
                           integration
                        ON
                           integrationStat.integration = integration.id
                        WHERE
                           integration.dateStart >= '{$this->args['dateMin']}' AND
                           date_trunc('day', integration.dateStart) <= '{$this->args['dateMax']}' AND
                           integrationStat.variable = {$this->args['variable']}
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