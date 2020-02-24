<?php

include "Handler.php";

class StationHandler extends Handler {
   public function get() {
      $query = "
         SELECT
            *
         FROM
            (
               SELECT
                     id,
                     aqsid,
                     name,
                     city,
                     address,
                     comment,
                     position,
                     environment,
                     modificationdate,
                     timezone,
                     aqsid || ' (' || name || ')' AS display
               FROM
                     aqsstation
            ) AS station";
      return $this->baseGet($query, 'display', 'aqsid');
   }
}

?>