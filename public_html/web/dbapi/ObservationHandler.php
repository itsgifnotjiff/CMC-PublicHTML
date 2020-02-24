<?php

require_once "Handler.php";

class ObservationHandler extends Handler {
   public function __construct($conn, $method, $args, $body) {
      parent::__construct($conn, $method, $args, $body);
      $this->attNeedsQuote = Array('dataset' => false, 'duration' => true, 'method' => false, 'station' => false, 'dateMin' => true, 'dateMax' => true);
   }

   public function get() {
      $argsOk = $this->checkBodyParts();
      if ($argsOk === true) {

         $lowerDate = "startDate";
         if ( strlen($this->args['dateMin']) <= 10) {
            $lowerDate = "date_trunc('day', startDate)";
         }

         $upperDate = "startDate";
         if ( strlen($this->args['dateMax']) <= 10) {
            $upperDate = "date_trunc('day', startDate)";
         }

         $query = "
            SELECT
               to_char(startDate, 'YYYY-MM-DD\"T\"HH24:MI:SS') AS startDateISO,
               startDate,
               value
            FROM
               observation
            WHERE
               dataset = {$this->args['dataset']} AND
               duration = '{$this->args['duration']}' AND
               method = {$this->args['method']} AND
               station = {$this->args['station']} AND
               ${lowerDate} >= '{$this->args['dateMin']}' AND
               ${upperDate} <= '{$this->args['dateMax']}'";
         return $this->baseGet($query, null, 'startDate');
      } else {
         return $argsOk;
      }
   }

   public function put() {
      $this->attNeedsQuote = Array('currentDataset' => false, 'newDataset' => false, 'duration' => true, 'method' => false, 'station' => false, 'startDate' => true);
      $argsOk = $this->checkBodyParts();
      if ($argsOk === true) {
         $query = "
            UPDATE observation SET
               dataset = {$this->body->newDataset}
            WHERE
               dataset = {$this->body->currentDataset} AND
               duration = '{$this->body->duration}' AND
               method = {$this->body->method} AND
               station = {$this->body->station} AND
               startDate = '{$this->body->startDate}'
            RETURNING
               *
         ";
         $response = new Response();
         $res = $this->conn->query($query);
         if ($res === false) {
            $driverMsg = $this->conn->errorInfo();
            $erreur = array("message" => "Erreur lors de l'insertion dans \"Projet\"!", "query" => $query, "driverMsg" => $driverMsg['2']);
            $response->code = 400;
            $response->body = json_encode($erreur);
         } else {
            $response->code = 200;
            $results = $res->fetchAll(PDO::FETCH_NAMED);
            $response->body = json_encode($results);
         }
         return $response;
      } else {
         return $argsOk;
      }
   }
}

?>