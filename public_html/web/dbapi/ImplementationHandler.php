<?php
//! \file "ImplementationHandler.php" Implementation of ImplementationHandler class

require_once "Handler.php";

//! JSON/Rest handler for grid type objects
class ImplementationHandler extends Handler {
   public function __construct($conn, $method, $args, $body) {
      parent::__construct($conn, $method, $args, $body);
      $this->attNeedsQuote = Array('serie' => false, 'name' => true, 'executable' => false, 'inventory' => false, 'description' => true);
   }


   //! Overloaded get method
   protected function get() {
      $query = "SELECT id, name, executable, inventory, description FROM implementation ";
      return $this->baseGet($query, 'name', 'name');
   }
}

?>