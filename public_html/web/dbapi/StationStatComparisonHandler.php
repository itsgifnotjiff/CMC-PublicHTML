<?php

require_once "Handler.php";

class StationStatComparisonHandler extends Handler {
   public function get() {
      $query = "
         SELECT
            *
         FROM
            (
               SELECT
                  c.id,
                  c.id || ' : ' || (SELECT name FROM serie WHERE serie.id = c.base) || ' vs ' ||
                     (SELECT name FROM serie WHERE serie.id = c.test) || ' ' ||
                     datebegin || ' - ' || dateend || ' - ' ||
                     (SELECT abreviation FROM region WHERE region.id = c.domain) AS
                     display
               FROM
                  comparison AS c
               WHERE
                  c.id IN (SELECT comparison FROM stationstat)
            ) AS comparison";
      return $this->baseGet($query, 'display', 'display');
   }
}

?>