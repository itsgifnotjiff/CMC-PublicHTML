<?php

require_once "Handler.php";

class FctHourStatHandler extends Handler {
   public function __construct($conn, $method, $args, $body) {
      parent::__construct($conn, $method, $args, $body);
      $this->attNeedsQuote = Array('comparison' => false, 'variable' => false, 'region' => false, 'stat' => false);
   }

   public function get() {
      $argsOk = $this->checkBodyParts();
      if ($argsOk === true) {
         $result = Array();

         $query = 
            "SELECT DISTINCT
               startHour
            FROM
               hourStat
            WHERE
               comparison = {$this->args['comparison']}
            ORDER BY
               startHour";
         $startHourRes = $this->conn->query($query) or die( date("c") . " : " . "Query execution failed! Offending query :\n" . $query . "\n" . print_r($conn->errorinfo(), true) );
         foreach ( $startHourRes as $startHourRow ) {
            $startHour = $startHourRow['starthour'];

            $query = "
               SELECT
                  forecastHour AS x,
                  {$this->args['stat']} AS y
               FROM
                  hourStat
               WHERE
                  startHour = {$startHour} AND
                  comparison = {$this->args['comparison']} AND
                  variable = {$this->args['variable']} AND
                  region = {$this->args['region']} AND
                  serie = (SELECT base FROM comparison WHERE id = {$this->args['comparison']})
               ORDER BY
                  forecastHour";
            $res = $this->conn->query($query);
            if ($res === false) {
               $response = new Response();
               $response->code = 404;
               $drvMsg = $this->conn->errorinfo();
               $erreur = array("message" => "Erreur lors de l'exécution de la requête!", "query" => $query, "driverMsg" => $drvMsg['2']);
               $response->body = json_encode($erreur);
               return $response;
            }
            $baseStats = $res->fetchAll(PDO::FETCH_ASSOC);

            $query = "
               SELECT
                  forecastHour AS x,
                  {$this->args['stat']} AS y
               FROM
                  hourStat
               WHERE
                  startHour = {$startHour} AND
                  comparison = {$this->args['comparison']} AND
                  variable = {$this->args['variable']} AND
                  region = {$this->args['region']} AND
                  serie = (SELECT test FROM comparison WHERE id = {$this->args['comparison']})
               ORDER BY
                  forecastHour";
            $res = $this->conn->query($query);
            if ($res === false) {
               $response = new Response();
               $response->code = 404;
               $drvMsg = $this->conn->errorinfo();
               $erreur = array("message" => "Erreur lors de l'exécution de la requête!", "query" => $query, "driverMsg" => $drvMsg['2']);
               $response->body = json_encode($erreur);
               return $response;
            }
            $testStats = $res->fetchAll(PDO::FETCH_ASSOC);

            $result[$startHour] = Array('Base' => $baseStats, 'Test' => $testStats);
         }
         $response = new Response();
         $response->code = 200;
         $response->body = json_encode($result);
         return $response;
      } else {
         return $argsOk;
      }
   }
}

?>