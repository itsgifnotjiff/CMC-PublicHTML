<?php

require_once "Handler.php";

class MonthlyStatGroup extends Handler {
   public function __construct($conn, $method, $args, $body) {
      parent::__construct($conn, $method, $args, $body);
      $this->attNeedsQuote = Array('monthlyStatConfig' => false, 'monthMin' => true, 'monthMax' => true);
   }

   public function get() {
      $argsOk = $this->checkBodyParts();
      if ($argsOk === true) {
         $minMonthStr = $this->conn->quote($this->args['monthMin'] . "-01");
         $maxMonthStr = $this->conn->quote($this->args['monthMax'] . "-01");
         // only include station groups available in entire month range
         $query = "
            WITH monthgroups AS (
               SELECT
                  DISTINCT month,
                  station_pop_class_group
               FROM
                  monthlyStat ms
               WHERE
                  monthlyStatConfig = {$this->args['monthlyStatConfig']} AND 
                  <timeFilter> )
            SELECT
               id,
               description
            FROM
               station_pop_class_group
            WHERE
               id IN (
                  SELECT
                     station_pop_class_group
                  FROM
                     monthgroups
                  GROUP BY
                     station_pop_class_group
                  HAVING
                     COUNT(*) = (
                     SELECT
                        COUNT( DISTINCT month )
                     FROM
                        monthgroups)
               )
            ORDER BY id";

         $timeFilter = " month >= date_trunc('month', {$minMonthStr}::TIMESTAMPTZ) AND month <= date_trunc('month', {$maxMonthStr}::TIMESTAMPTZ) ";
         $query = str_replace("<timeFilter>", $timeFilter, $query);

         $res = $this->conn->query($query);
         if ($res === false) {
            $response = new Response();
            $response->code = 404;
            $drvMsg = $this->conn->errorinfo();
            $erreur = array("message" => "Erreur lors de l'exécution de la requête!", "query" => $query, "driverMsg" => $drvMsg['2']);
            $response->body = json_encode($erreur);
            return $response;
         }

         return new Response(200, json_encode($res->fetchAll()));
      } else {
         return $argsOk;
      }
   }
}

?>