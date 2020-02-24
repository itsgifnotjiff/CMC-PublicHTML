<?php

require_once "Handler.php";

class InventoryHandler extends Handler {
   public function __construct($conn, $method, $args, $body) {
      parent::__construct($conn, $method, $args, $body);
      $this->attNeedsQuote = Array('name' => true, 'description' => true);
   }

   protected function get() {
      $query = "SELECT id, name, description FROM inventory ";
      return $this->baseGet($query, 'name', 'name');
   }
}

?>