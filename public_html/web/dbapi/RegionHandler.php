<?php

require_once "Handler.php";

class RegionHandler extends Handler {
   public function get() {
      $query = "
         SELECT
            *
         FROM
            (
               SELECT
                  id,
                  name_en_CA AS name
               FROM
                  region
            ) AS region";
      return $this->baseGet($query, 'name', 'name');
   }
}

?>