<?php

require_once "Handler.php";

class SerieHandler extends Handler {
   public function __construct($conn, $method, $args, $body) {
      parent::__construct($conn, $method, $args, $body);
      $this->attNeedsQuote = Array('name' => true, 'model' => false, 'grid' => false, 'stationsnapshot' => false, 'description' => true, 'creator' => true, 'currentimplementation' => false, 'interpolation' => false);
   }

   protected function get() {
      $query = "
         SELECT
            *
         FROM
            (
               SELECT
                  *,
                  name || ' (' || id || ')' AS display
               FROM
                  serie
            ) AS tmp";
      return $this->baseGet($query, 'display', 'display');
   }

   protected function post() {
      // Why re-implement the entire method when only one element is missing from the body?
      // Do a Mother Beautiful Hack -> Inject it in the body and call the parent!
      $this->body->creator = $_SERVER['PHP_AUTH_USER'];
      return parent::post();
   }
}

?>