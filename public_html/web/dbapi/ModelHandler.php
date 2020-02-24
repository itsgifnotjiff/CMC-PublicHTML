<?php

require_once "Handler.php";

class ModelHandler extends Handler {
   public function __construct($conn, $method, $args, $body) {
      parent::__construct($conn, $method, $args, $body);
      $this->attNeedsQuote = Array('name' => true);
   }


   public function get() {
      $query = "
         SELECT
            id,
            name
         FROM
            model ";
      return $this->baseGet($query, 'name', 'name');
   }
}

?>