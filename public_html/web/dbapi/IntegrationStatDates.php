<?php

require_once "Handler.php";

class IntegrationStatDates extends Handler {
   public function get() {
      $query = "
         WITH statIntegrations AS (
            SELECT DISTINCT integration AS id FROM integrationStat
         )
         SELECT
            (
               SELECT
                  dateStart
               FROM
                  integration
               WHERE
                  id IN (SELECT id FROM statIntegrations)
               ORDER BY
                  dateStart
               LIMIT
                  1
            ) AS min,
            (
               SELECT
                  dateStart
               FROM
                  integration
               WHERE
                  id IN (SELECT id FROM statIntegrations)
               ORDER BY
                  dateStart DESC
               LIMIT
                  1
            ) AS max";
      return $this->baseGet($query, NULL, NULL);
   }
}

?>