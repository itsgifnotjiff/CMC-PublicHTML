<?php

require_once "Handler.php";

class BinStatHandler extends Handler {
   public function __construct($conn, $method, $args, $body) {
      parent::__construct($conn, $method, $args, $body);
      $this->attNeedsQuote = Array('comparison' => false, 'region' => false, 'variable' => false, 'stat' => true);
   }

   public function get() {
      $argsOk = $this->checkBodyParts();
      if ($argsOk === true) {
         $result = Array();
         foreach ( Array('Base', 'Test') as $resultType ) {
            $query = "
               SELECT
                  rank() OVER (ORDER BY bin.lowerBound) AS x,
                  binStat.{$this->args['stat']} AS y,
                  '[' || bin.lowerBound || ', ' || bin.upperBound || '[' AS label
               FROM
                  binStat
               INNER JOIN
                  bin
               ON
                  binStat.bin = bin.id
               WHERE
                  binStat.comparison = {$this->args['comparison']} AND
                  binStat.serie = (SELECT {$resultType} FROM comparison WHERE id = {$this->args['comparison']}) AND
                  binStat.region = {$this->args['region']} AND
                  bin.variable = {$this->args['variable']}
               ORDER BY
                  lowerBound
            ";
            $res = $this->conn->query($query);
            if ($res === false) {
               $response = new Response();
               $response->code = 404;
               $drvMsg = $this->conn->errorinfo();
               $erreur = array("message" => "Erreur lors de l'exécution de la requête!", "query" => $query, "driverMsg" => $drvMsg['2']);
               $response->body = json_encode($erreur);
               return $response;
            }
            $result[$resultType] = $res->fetchAll(PDO::FETCH_ASSOC);
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