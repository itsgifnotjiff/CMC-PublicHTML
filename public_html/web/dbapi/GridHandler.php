<?php
//! \file "GridHandler.php" Implementation of GridHandler class

require_once "Handler.php";

//! JSON/Rest handler for grid type objects
class GridHandler extends Handler {
   public function __construct($conn, $method, $args, $body) {
      parent::__construct($conn, $method, $args, $body);
      $this->attNeedsQuote = Array('gridName' => true, 'gridFilePath' => true, 'fieldVarName' => true, 'gridDescription' => true);
   }


   //! Overloaded get method
   protected function get() {
      $query = "SELECT id, name, description FROM grid ";
      return $this->baseGet($query, 'name', 'name');
   }

   //! Overloaded post method
   //! \return Response object containing the newly created grid or an error message on failure
   protected function post() {
      $argsOk = $this->checkBodyParts();
      if ($argsOk === true) {
         if ( is_readable( $this->body['gridFilePath'] ) ) {
            // FIXME : Sanitize input!
            $cmd = "/users/dor/afsu/air/verification/bin/gridLoader ";
            $cmd .= escapeshellarg( $this->body['gridName'] ) . " ";
            $cmd .= escapeshellarg( $this->body['gridFilePath'] ) . " ";
            $cmd .= escapeshellarg( $this->body['fieldVarName'] ) . " ";
            $cmd .= '""';
            $gridId = shell_exec($cmd);

            $query = "UPDATE grid SET description = ";
            $query .= $this->conn->quote($this->body['gridDescription']) . " WHERE id = " . $gridId;
            $res = niceExec($query, false, false, false);
            if ($res === false) {
               $response = new Response();
               $response->code = 400;
               $drvMsg = $this->conn->errorinfo();
               $erreur = array("message" => "Erreur lors de l'exécution de la requête!", "query" => $query, "driverMsg" => $drvMsg['2']);
               $response->body = json_encode($erreur);
               return $response;
            } else {
               $this->args = Array('id' => $gridId);
               return $this->get();
            }
         } else {
            $msg = "\"{$this->body['gridFilePath']}\" n'est pas lisible!";
            return new Response(400, json_encode(array("message" => $msg)));
         }
      } else {
         return $argsOk;
      }
   }
}

?>