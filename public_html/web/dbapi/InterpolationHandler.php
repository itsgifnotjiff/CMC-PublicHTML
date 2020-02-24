<?php

require_once "Handler.php";

class InterpolationHandler extends Handler {
   public function get() {
      $query = "
         SELECT
            id,
            name
         FROM
            interpolation";
      return $this->baseGet($query, 'name', 'name');
   }
}

?>