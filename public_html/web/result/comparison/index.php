<!DOCTYPE html>
<html>
<head>
   <meta charset="utf-8">
   <title>VAQUM Comparison results</title>
   <link rel="stylesheet" href="../../vaqum.css">
</head>


<body>
<?php
   //---------------------------------------------------------------------------
   // M A I N
   //---------------------------------------------------------------------------
   if ( $_SERVER['REQUEST_METHOD'] <> 'GET' ) {
      $errorMsg = gmdate("Y-m-d H:i:s") . " - " . __FILE__ . "\n";
      $errorMsg .= "This script should only be called with an HTTP GET!\n";
      $errorMsg .= "It got called with : " . $_SERVER['REQUEST_METHOD'] . "\n";
      die($errorMsg);
   }

   require_once "../../include/VaqumDb.php";
   $conn = new VaqumDb;

   print("\t<h1>Comparison results</h1>\n");

   print("\t<table>\n");
   print("\t\t<thead>\n");
   print("\t\t\t<tr>\n");
   print("\t\t\t\t<th>Id</th>\n");
   print("\t\t\t\t<th>Base</th>\n");
   print("\t\t\t\t<th>Test</th>\n");
   print("\t\t\t\t<th>Begin Date</th>\n");
   print("\t\t\t\t<th>End Date</th>\n");
   print("\t\t\t\t<th>Test Integration Offset</th>\n");
   print("\t\t\t\t<th>Creation Date</th>\n");
   print("\t\t\t</tr>\n");
   print("\t\t</thead>\n");
   print("\t\t<tbody>\n");

   $query = "
      SELECT
         comparison.id,
         base.id AS base,
         base.name AS baseName,
         test.id AS test,
         test.name AS testName,
         comparison.dateBegin,
         comparison.dateEnd,
         comparison.integrationOffset,
         comparison.creationDate
      FROM
         (
            SELECT DISTINCT
               comparison
            FROM
               result
         ) AS result
      INNER JOIN
         (
            SELECT DISTINCT
               comparison
            FROM
               contingency
         ) AS contingency
      ON
         result.comparison = contingency.comparison
      INNER JOIN
         comparison
      ON
         result.comparison = comparison.id
      INNER JOIN
         serie AS base
      ON
         comparison.base = base.id
      INNER JOIN
         serie AS test
      ON
         comparison.test = test.id
      ORDER BY
         comparison.id";
   foreach ($conn->query($query) as $comparisonRow) {
      print("\t\t\t<tr>\n");
      print("\t\t\t\t<td><a href=view.php?id={$comparisonRow['id']}>{$comparisonRow['id']}</a></td>\n");
      print("\t\t\t\t<td><a href=\"../../serie.html#id={$comparisonRow['base']}\">{$comparisonRow['basename']}</a></td>\n");
      print("\t\t\t\t<td><a href=\"../../serie.html#id={$comparisonRow['test']}\">{$comparisonRow['testname']}</a></td>\n");
      print("\t\t\t\t<td>{$comparisonRow['datebegin']}</td>\n");
      print("\t\t\t\t<td>{$comparisonRow['dateend']}</td>\n");
      print("\t\t\t\t<td>{$comparisonRow['integrationoffset']}</td>\n");
      print("\t\t\t\t<td>{$comparisonRow['creationdate']}</td>\n");
      print("\t\t\t</tr>\n");
   }
   print("\t\t</tbody>\n");
   print("\t</table>\n");

   // Selon la doc de PDO, ceci devrait fermer la connexion, mais ça ne fonctionne pas
   // http://www.php.net/manual/en/pdo.connections.php
   $conn = NULL;
?>
</body>
</html>