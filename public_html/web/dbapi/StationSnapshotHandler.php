<?php

require_once "Handler.php";

class StationSnapshotHandler extends Handler {
   public function get() {
      $query = "SELECT
            *
         FROM
            (
               SELECT
                  id,
                  creationdate,
                  description,
                  cast(creationdate AS TEXT) AS datestr
               FROM
                  stationsnapshot
            ) AS stationsnapshot";
      return $this->baseGet($query, 'datestr', 'creationdate');
   }

   public function post() {
      $response = new Response();
      $des = $this->conn->quote($this->body->description);
      $query = "SELECT create_station_snapshot({$des})";
      $res = $this->conn->query($query);
      if ($res === false) {
         $driverMsg = $this->conn->errorInfo();
         $error = array("message" => "Erreur lors de l'insertion dans \"Projet\"!", "query" => $query, "driverMsg" => $driverMsg['2']);
         $response->code = 400;
         $response->body = json_encode($error);
      } else {
         $new = $res->fetchAll(PDO::FETCH_NAMED);
         $response->code = 200;
         $response->body = json_encode($new[0]);
      }
      return $response;
   }


   public function put() {
      $response = new Response();

      $error = array("message" => "Il n'est pas possible de mettre à jour un snapshot de stations!");
      $response->body = json_encode($error);
      $response->code = 400;

      return $response;
   }
}

?>