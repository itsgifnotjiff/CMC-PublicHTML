<?php

require_once "Handler.php";

class DatasetHandler extends Handler {
   public function get() {
      $query = "
         SELECT
            *
         FROM
            (
               SELECT
                  id,
                  name,
                  source,
                  (SELECT name FROM source WHERE id = dataset.source) AS sourceName,
                  dateaquired,
                  description,
                  version
               FROM
                  dataset 
            ) AS dataset";
      return $this->baseGet($query, 'name', 'id');
   }
}

?>