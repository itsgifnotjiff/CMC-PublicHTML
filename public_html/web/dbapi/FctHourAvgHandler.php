<?php

require_once "Handler.php";

class FctHourAvgHandler extends Handler {
   public function __construct($conn, $method, $args, $body) {
      parent::__construct($conn, $method, $args, $body);
      $this->attNeedsQuote = Array('comparison' => false, 'variable' => false, 'region' => false, 'startHour' => false);
   }

   public function get() {
      $argsOk = $this->checkBodyParts();
      if ($argsOk === true) {
         $result = Array();

         $query = "
            SELECT
               forecastHour AS x,
               avgobs AS y
            FROM
               hourStat
            WHERE
               startHour = {$this->args['startHour']} AND
               comparison = {$this->args['comparison']} AND
               variable = {$this->args['variable']} AND
               region = {$this->args['region']} AND
               serie = (SELECT base FROM comparison WHERE id = {$this->args['comparison']})
            ORDER BY
               x";
         $res = $this->conn->query($query);
         if ($res === false) {
            $response = new Response();
            $response->code = 404;
            $drvMsg = $this->conn->errorinfo();
            $erreur = array("message" => "Erreur lors de l'exécution de la requête!", "query" => $query, "driverMsg" => $drvMsg['2']);
            $response->body = json_encode($erreur);
            return $response;
         }
         $result['Obs'] = $res->fetchAll(PDO::FETCH_ASSOC);

         foreach (array('Base', 'Test') as $component) {
            $query = "
               SELECT
                  forecastHour AS x,
                  avgmod AS y
               FROM
                  hourStat
               WHERE
                  startHour = {$this->args['startHour']} AND
                  comparison = {$this->args['comparison']} AND
                  variable = {$this->args['variable']} AND
                  region = {$this->args['region']} AND
                  serie = (SELECT {$component} FROM comparison WHERE id = {$this->args['comparison']})
               ORDER BY
                  x";
            $res = $this->conn->query($query);
            if ($res === false) {
               $response = new Response();
               $response->code = 404;
               $drvMsg = $this->conn->errorinfo();
               $erreur = array("message" => "Erreur lors de l'exécution de la requête!", "query" => $query, "driverMsg" => $drvMsg['2']);
               $response->body = json_encode($erreur);
               return $response;
            }
            $result[$component] = $res->fetchAll(PDO::FETCH_ASSOC);

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