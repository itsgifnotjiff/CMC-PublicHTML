<?php

require_once "Handler.php";

class MethodHandler extends Handler {
   public function __construct($conn, $method, $args, $body) {
      parent::__construct($conn, $method, $args, $body);
      $this->attNeedsQuote = Array('id' => false, 'source' => false, 'dateaquired' => true, 'description' => true, 'version' => true);
   }


   public function get() {
      $query = "
         SELECT
            *
         FROM
            (
               SELECT
                  method.id,
                  method.instrument,
                  method.variable,
                  method.description,
                  method.epacode,
                  CAST(method.id AS TEXT) || ' - ' || variable.abreviation || ' - ' || instrument.name AS display
               FROM
                  method
               INNER JOIN
                  variable
               ON
                  method.variable = variable.id
               INNER JOIN
                  instrument
               ON
                  method.instrument = instrument.id
            ) AS method";
      return $this->baseGet($query, 'display', 'id');
   }
}

?>