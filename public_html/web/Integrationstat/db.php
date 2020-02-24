 <?php
   require_once "../include/VaqumDb.php";

   $conn = new VaqumDb();

   function execQuery($conn, $query) {
      $result = $conn->query($query);
      if ($result) {
         return $result->fetchAll(PDO::FETCH_ASSOC);
      } else {
         die("Query error/No results");
      }
   }
   $query_serie = "
      SELECT DISTINCT
         integration.serie
      FROM
         integrationstat
      INNER JOIN
         integration
      ON
         integrationstat.integration = integration.id";

   switch($_GET["query"]){
      case "date":
         $query = "
            SELECT
               min(integration.datestart),
               max(integration.datestart)
            FROM
               integration
            INNER JOIN
               integrationstat
            ON
               integrationstat.integration = integration.id
            WHERE
               integrationstat.region = (SELECT region.id FROM region WHERE region.name_en_ca = " . $conn->quote($_GET["region"]) . ") AND
               integration.serie IN ( {$query_serie} )";
         $result = execQuery($conn, $query);
         echo json_encode($result);
         break;

      case "serie":
         $query = "
            SELECT
               serie.name as serie
            FROM
               serie
            WHERE
               serie.id IN( {$query_serie} )";
         $result = execQuery($conn, $query);
         echo json_encode($result);
         break;

      case "variable":
         $query = "
            SELECT DISTINCT
               variable.abreviation as variable
            FROM
               integrationstat
            INNER JOIN
               integration
            ON
               integration.id = integrationstat.integration
            INNER JOIN
               variable
            ON
               variable.id = integrationstat.variable
            WHERE
               integrationstat.region = (SELECT region.id FROM region WHERE region.name_en_ca = " . $conn->quote($_GET["region"]) . ") AND
               integration.serie IN ( {$query_serie} )";
         $result = execQuery($conn, $query);
         echo json_encode($result);
         break;

      case "region":
         $query = "
            SELECT
               region.name_en_ca as region
            FROM
               integrationstat
            INNER JOIN
               integration
            ON
               integration.id = integrationstat.integration
            INNER JOIN
               region
            ON
               region.id = integrationstat.region
            WHERE
               integration.serie IN ( {$query_serie} )
            GROUP BY
               region.name_en_ca
            ORDER BY
               max(integration.datestart) DESC";
         $result = execQuery($conn, $query);
         echo json_encode($result);
         break;

      case "data":
         $dateMinStr = $conn->quote($_GET["dateMin"]);
         $dateMaxStr = $conn->quote($_GET["dateMax"]);
         $variableStr = $conn->quote($_GET["variable"]);
         $regionStr = $conn->quote($_GET["region"]);
         $query = "
            SELECT
               serie.name as serie,
               integration.datestart,
               integrationstat.{$_GET["stat"]} AS value
            FROM
               integrationstat
            INNER JOIN
               integration
            ON
               integrationstat.integration = integration.id
            INNER JOIN
               serie
            ON
               integration.serie = serie.id
            WHERE
               integrationstat.variable = (SELECT id FROM variable WHERE abreviation = {$variableStr}) AND
               integrationstat.region IN (SELECT id FROM region WHERE name_en_ca = {$regionStr}) AND
               integration.datestart >= TO_DATE({$dateMinStr}, 'yyyy-mm-dd') AND
               integration.datestart < TO_DATE({$dateMaxStr}, 'yyyy-mm-dd')
            ORDER BY
               integration.datestart";
         $result = execQuery($conn, $query);
         $json = array();
         $i = 0;
         foreach($result as $row) {
            $json[$row["serie"]][$i++] = array("x" => $row["datestart"], "y" => $row["value"]);
         }
         echo json_encode($json);
         break;

      default:
         die("Requested data ({$_GET["query"]}) isn't valid!");
         break;
   }
 ?>