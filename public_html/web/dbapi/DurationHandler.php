<?php

require_once "Handler.php";

class DurationHandler extends Handler {
   public function get() {
      $query = "
         SELECT
            *
         FROM
            (
               SELECT
                  1 AS id,
                  '01:00:00'::TEXT AS duration
            ) AS duration";
      return $this->baseGet($query, 'duration', 'duration');
   }
}

?>