<?php

require_once "Handler.php";

class IntegrationHandler extends Handler {
   public function get() {
      // FIXME : Sanatize input!!!
      $query = "
         SELECT
            baseInt.dateStart
         FROM
            integration AS baseInt
         INNER JOIN
            serie AS baseSerie
         ON
            baseInt.serie = baseSerie.id
         INNER JOIN
            integration AS testInt
         ON
            baseInt.dateStart = testInt.dateStart
         INNER JOIN
            serie AS testSerie
         ON
            testInt.serie = testSerie.id
         WHERE
            baseSerie.stationsnapshot = testSerie.stationsnapshot AND
            baseInt.serie = " . $this->args['base'] . " AND
            testInt.serie = " . $this->args['test'] . "
         ORDER BY
            dateStart";
      $res = $this->conn->query($query);
      if ($res === false) {
         $response = new Response();
         $response->code = 404;
         $drvMsg = $this->conn->errorinfo();
         $erreur = array("message" => "Erreur lors de l'exécution de la requête!", "query" => $query, "driverMsg" => $drvMsg['2']);
         $response->body = json_encode($erreur);
         return $response;
      } else {
         return new Response(200, json_encode($res->fetchAll(PDO::FETCH_NAMED)));
      }
   }
}

?>