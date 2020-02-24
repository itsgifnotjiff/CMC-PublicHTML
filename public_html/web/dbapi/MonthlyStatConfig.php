<?php

require_once "Handler.php";
require_once "montlyStatConfigArray.php";

class MonthlyStatConfig extends Handler {
   public function __construct($conn, $method, $args, $body) {
      parent::__construct($conn, $method, $args, $body);
      $this->attNeedsQuote = Array('id' => false);
   }

   public function get() {
      $id = null;
      if (array_key_exists('id', $this->args)) {
         $id = $this->args['id'];
      }

      return new Response(200, json_encode(configArray($this->conn, $id)));
   }
}

?>