<?php

require_once "Handler.php";

class MonthlyStatMonth extends Handler {
   public function __construct($conn, $method, $args, $body) {
      parent::__construct($conn, $method, $args, $body);
      $this->attNeedsQuote = Array('monthlyStatConfig' => false);
   }

   public function get() {
      $argsOk = $this->checkBodyParts();
      if ($argsOk === true) {
         $query = "
            SELECT DISTINCT
               to_char(month, 'YYYY-MM') AS month
            FROM
               monthlyStat
            WHERE
               monthlyStatConfig = {$this->args['monthlyStatConfig']}
            ORDER BY
               month DESC";

         $res = $this->conn->query($query);
         if ($res === false) {
            $response = new Response();
            $response->code = 404;
            $drvMsg = $this->conn->errorinfo();
            $erreur = array("message" => "Erreur lors de l'exécution de la requête!", "query" => $query, "driverMsg" => $drvMsg['2']);
            $response->body = json_encode($erreur);
            return $response;
         }

         return new Response(200, json_encode($res->fetchAll(PDO::FETCH_COLUMN, 0)));
      } else {
         return $argsOk;
      }
   }
}

?>