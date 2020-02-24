<?php

function configArray($conn, $id) {
   $result = Array();

   $whereClause = "";
   if ($id != null) {
      $whereClause = "WHERE
               id = {$id}";
   }

   $query = "
      SELECT
         id,
         firsthour,
         hourstep,
         lasthour
      FROM
         monthlyStatConfig
      {$whereClause}
      ORDER BY
         id";
   $msc = $conn->query($query);
   $rowNb = 0;
   foreach ($msc as $row) {
      foreach ($row as $field => $value) {
         $result[$rowNb]["{$field}"] = $value;
      }

      // ForecastDays
      $query = "
         SELECT
            generate_series(1, lasthour / 24) AS forecastDay
         FROM
            monthlyStatConfig
         WHERE
            id = {$row['id']}";
      $res = $conn->query($query);
      $result[$rowNb]['forecastDays'] = $res->fetchAll(PDO::FETCH_COLUMN, 0);

      // Regions
      $query = "
         SELECT
            region.id,
            region.abreviation AS name
         FROM
            monthlyStatConfigRegion AS mscr
         INNER JOIN
            region
         ON
            mscr.region = region.id
         WHERE
            mscr.monthlyStatConfig = {$row['id']}";
      $res = $conn->query($query);
      $result[$rowNb]['regions'] = $res->fetchAll(PDO::FETCH_NAMED);

      // Series
      $query = "
         SELECT
            serie.id,
            serie.name
         FROM
            monthlyStatConfigSerie AS mscs
         INNER JOIN
            serie
         ON
            mscs.serie = serie.id
         WHERE
            mscs.monthlyStatConfig = {$row['id']}
         ORDER BY
            name";
      $res = $conn->query($query);
      $result[$rowNb]['series'] = $res->fetchAll(PDO::FETCH_NAMED);

      // Variables
      $query = "
         SELECT DISTINCT
            variable.id,
            variable.abreviation AS name
         FROM
            monthlyStatConfigmethod AS mscm
         INNER JOIN
            method
         ON
            mscm.method = method.id
         INNER JOIN
            variable
         ON
            method.variable = variable.id
         WHERE
            monthlyStatConfig = {$row['id']}";
      $res = $conn->query($query);
      $result[$rowNb]['variables'] = $res->fetchAll(PDO::FETCH_NAMED);

      $rowNb++;
   }

   if ($id != null) {
      $result = $result[0];
   }

   return $result;
}

?>