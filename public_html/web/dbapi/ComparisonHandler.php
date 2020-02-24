<?php

require_once "Handler.php";

class ComparisonHandler extends Handler {
   public function __construct($conn, $method, $args, $body) {
      parent::__construct($conn, $method, $args, $body);
      $this->attNeedsQuote = Array('base' => false, 'test' => false, 'dateBegin' => true, 'dateEnd' => true, 'domain' => false);
   }


   protected function get() {
      $query = "
         SELECT
            *
         FROM
            (
               SELECT
                  comparison.id,
                  base.name AS base,
                  test.name AS test,
                  comparison.dateBegin,
                  comparison.dateEnd,
                  region.name_en_ca AS domain,
                  comparison.creationdate
               FROM
                  comparison
            INNER JOIN
                  serie AS base
            ON
                  comparison.base = base.id
            INNER JOIN
                  serie AS test
            ON
                  comparison.test = test.id
               INNER JOIN
                  region
               ON
                  comparison.domain = region.id
         ) AS comparison";
      return $this->baseGet($query, 'display', 'creationdate');
   }
}

?>