<?php

require_once "Handler.php";

class IntegrationStats extends Handler {
   public function __construct($conn, $method, $args, $body) {
      parent::__construct($conn, $method, $args, $body);
      $this->attNeedsQuote = Array('stat' => true, 'dateMin' => true, 'dateMax' => true, 'variable' => false);
   }

   public function get() {
      $argsOk = $this->checkBodyParts();
      if ($argsOk === true) {
         $results = array();
         $query = "
            SELECT
               rs.region,
               rs.serie,
               (SELECT abreviation FROM region WHERE id = rs.region) || ' - ' ||
               (SELECT name FROM serie WHERE id = rs.serie) AS name
            FROM
               (
                  SELECT DISTINCT
                     integrationstat.region,
                     integration.serie
                  FROM
                     integrationstat
                  INNER JOIN
                     integration
                  ON
                     integrationstat.integration = integration.id
                  WHERE
                     integrationstat.variable = {$this->args['variable']} AND
                     integration.datestart >= '{$this->args['dateMin']}' AND
                     date_trunc('day', integration.datestart) <= '{$this->args['dateMax']}'
               ) AS rs
            ORDER BY
               name";
         $rs = $this->conn->fetchAssoc($query);
         foreach($rs as $row) {
            $query = "
               SELECT
                  integration.datestart AS x,
                  integrationstat.{$this->args['stat']} AS y
               FROM
                  integrationstat
               INNER JOIN
                  integration
               ON
                  integrationstat.integration = integration.id
               WHERE
                  integration.datestart >= '{$this->args['dateMin']}' AND
                  date_trunc('day', integration.datestart) <= '{$this->args['dateMax']}' AND
                  integrationstat.variable = {$this->args['variable']} AND
                  integrationstat.region = {$row['region']} AND
                  integration.serie = {$row['serie']}
               ORDER BY
                  integration.datestart";
            $results[$row['name']] = $this->conn->fetchAssoc($query);
         }

         return new Response(200, json_encode($results));
      } else {
         return $argsOk;
      }
   }
}

?>