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
   // p r i n t
   //---------------------------------------------------------------------------
   function printComparisonInfo($conn, $comparisonId) {
      $query = "
         SELECT
            bs.id AS base,
            bs.name AS baseName,
            ts.id AS test,
            ts.name AS testName,
            comp.dateBegin,
            comp.dateEnd,
            comp.creationDate,
            comp.integrationOffset,
            region.name_en_CA AS domain
         FROM
            comparison AS comp
         INNER JOIN
            serie AS bs
         ON
            comp.base = bs.id
         INNER JOIN
            serie AS ts
         ON
            comp.test = ts.id
         INNER JOIN
            region
         ON
            comp.domain = region.id
         WHERE
            comp.id = {$comparisonId}";
      $stm = $conn->query($query);
      $result = $stm->fetchAll();

      print("\t<h1>Comparison Info</h1>\n");
      print("\t<dl>\n");
      print("\t\t<dt>ID</dt>\n");
      print("\t\t<dd>{$comparisonId}</dd>\n");
      print("\t\t<dt>Base</dt>\n");
      print("\t\t<dd><a href=\"../../serie.html#id={$result[0]['base']}\">{$result[0]['basename']}</a></dd>\n");
      print("\t\t<dt>Test</dt>\n");
      print("\t\t<dd><a href=\"../../serie.html#id={$result[0]['test']}\">{$result[0]['testname']}</a></dd>\n");
      print("\t\t<dt>Start date</dt>\n");
      print("\t\t<dd>{$result[0]['datebegin']}</dd>\n");
      print("\t\t<dt>End date</dt>\n");
      print("\t\t<dd>{$result[0]['dateend']}</dd>\n");
      print("\t\t<dt>Creation date</dt>\n");
      print("\t\t<dd>{$result[0]['creationdate']}</dd>\n");
      print("\t\t<dt>Test integration offset</dt>\n");
      print("\t\t<dd>{$result[0]['integrationoffset']}</dd>\n");
      print("\t\t<dt>Domain</dt>\n");
      print("\t\t<dd>{$result[0]['domain']}</dd>\n");
      print("\t</dl>\n");

      print("\t<h1>Other Results</h1>\n");
      print("\t<ul>\n");
      print("\t\t<li><a href=\"charts.html#comparison={$comparisonId}&chartType=fctHourStat\">Per hour statistics graphs</a></li>\n");
      print("\t\t<li><a href=\"charts.html#comparison={$comparisonId}&chartType=fctHourAvg\">Per hour averages graphs</a></li>\n");
      print("\t\t<li><a href=\"charts.html#comparison={$comparisonId}&chartType=binStat\">Per observation value bin graphs</a></li>\n");
      print("\t\t<li><a href=\"charts.html#comparison={$comparisonId}&chartType=metAreaTs\">Metropolitan Areas Time Series</a></li>\n");
      print("\t</ul>\n");
   }

   //---------------------------------------------------------------------------
   // p r i n t M a x R e s u l t T a b l e
   //---------------------------------------------------------------------------
   function printMaxResultTable($conn, $comparisonId) {
      $query = "
         SELECT
            region.abreviation AS region,
            variable.abreviation AS variable,

            br.avgobs AS obsAvg,
            br.varobs AS obsVar,
            br.stdobs AS obsStd,
            br.count,

            br.avgmod AS baseAvg,
            br.stdmod AS baseStd,
            br.varmod AS baseVar,
            br.corr AS baseCorr,
            br.rmse AS baseRmse,
            br.urmse AS baseUrmse,
            br.mbias AS baseMbias,

            tr.avgmod AS testAvg,
            tr.stdmod AS testStd,
            tr.varmod AS testVar,
            tr.corr AS testCorr,
            tr.rmse AS testRmse,
            tr.urmse AS testUrmse,
            tr.mbias AS testMbias
         FROM
            comparison AS comp
         INNER JOIN
            result AS br
         ON
            comp.id = br.comparison AND
            comp.base = br.serie
         INNER JOIN
            result AS tr
         ON
            comp.id = tr.comparison AND
            comp.test = tr.serie AND
            br.region = tr.region AND
            br.variable = tr.variable AND
            br.timegrouping = tr.timegrouping AND
            br.count = tr.count
         INNER JOIN
            region
         ON
            br.region = region.id
         INNER JOIN
            variable
         ON
            br.variable = variable.id
         WHERE
            br.timegrouping = (SELECT id FROM timegrouping WHERE name = 'Day') AND
            comp.id = {$comparisonId}";
      $results = array();
      foreach($conn->query($query) as $row) {
         $results[$row['variable']][$row['region']] = $row;
      }

      $variables = array_keys($results);
      sort($variables);
      $regions =  array_keys( $results[$variables[0]] );
      sort($regions);

      print("\t<h1>Daily Maximums Statistics</h1>\n");

      print("\t<table>\n");
      print("\t\t<thead>\n");
      print("\t\t\t<tr>\n");
      print("\t\t\t\t<th colspan=\"2\">Region</th>\n");
      foreach($regions as $region) {
         print("\t\t\t\t<th colspan=\"2\">{$region}</th>\n");
      }
      print("\t\t\t</tr>\n");
      print("\t\t\t<tr>\n");
      print("\t\t\t\t<th>Pollutant</th>\n");
      print("\t\t\t\t<th>Statistic</th>\n");
      foreach($regions as $region) {
         print("\t\t\t\t<th>Base</th>\n");
         print("\t\t\t\t<th>Test</th>\n");
      }
      print("\t\t\t</tr>\n");
      print("\t\t</thead>\n");
      print("\t\t<tbody>\n");

      foreach($variables as $variable) {
         $first = true;
         foreach(array("mbias", "corr", "rmse", "urmse") as $stat) {
            print("\t\t\t<tr>\n");
            if ($first) {
               print("\t\t\t\t<th rowspan=4>{$variable}</th>\n");
               $first = false;
            }
            print("\t\t\t\t<th>{$stat}</th>\n");
            foreach($regions as $region) {
               $baseValue = $results[$variable][$region]["base".$stat];
               $testValue = $results[$variable][$region]["test".$stat];
               switch ($stat) {
                  case 'mbias' :
                     $style = abs($baseValue) < abs($testValue) ?  "baseBetter" : "testBetter";
                     break;
                  case 'corr' :
                     $style = $baseValue > $testValue ? "baseBetter" : "testBetter";
                     break;
                  case 'rmse' :
                     $style = $baseValue < $testValue ? "baseBetter" : "testBetter";
                     break;
                  case 'urmse' :
                     $style = $baseValue < $testValue ? "baseBetter" : "testBetter";
                     break;
               }
               print("\t\t\t\t<td class=\"{$style}\">{$baseValue}</td>\n");
               print("\t\t\t\t<td class=\"{$style}\">{$testValue}</td>\n");
            }
            print("\t\t\t</tr>\n");
         }
      }

      print("\t\t</tbody>\n");
      print("\t</table>\n");
   }


   //---------------------------------------------------------------------------
   // p r i n t C o n t i n g e n c y T a b l e
   //---------------------------------------------------------------------------
   function printContingencyTable($conn, $comparisonId, $regionId, $regionAbrv, $variableId, $variableAbrv, $pivotValue) {
      $query = "
         SELECT
            cont.*,
            baseCorrectLow + baseErrorHigh AS baseObsLow,
            to_char(100.0 * (baseCorrectLow + baseErrorHigh) / sumBase, 'FM990.00') AS baseObsLowRatio,
            baseErrorLow + baseCorrectHigh AS baseObsHigh,
            to_char(100.0 * (baseErrorLow + baseCorrectHigh) / sumBase, 'FM990.00') AS baseObsHighRation,
            baseCorrectLow + baseErrorLow AS baseFctLow,
            to_char(100.0 * (baseCorrectLow + baseErrorLow) / sumBase, 'FM990.00') AS baseFctLowRatio,
            baseErrorHigh + baseCorrectHigh AS baseFctHigh,
            to_char(100.0 * (baseErrorHigh + baseCorrectHigh) / sumBase, 'FM990.00') AS baseFctHighRatio,

            testCorrectLow + testErrorHigh AS testObsLow,
            to_char(100.0 * (testCorrectLow + testErrorHigh) / sumTest, 'FM990.00') AS testObsLowRatio,
            testErrorLow + testCorrectHigh AS testObsHigh,
            to_char(100.0 * (testErrorLow + testCorrectHigh) / sumTest, 'FM990.00') AS testObsHighRatio,
            testCorrectLow + testErrorLow AS testFctLow,
            to_char(100.0 * (testCorrectLow + testErrorLow) / sumTest, 'FM990.00') AS testFctLowRatio,
            testErrorHigh + testCorrectHigh AS testFctHigh,
            to_char(100.0 * (testErrorHigh + testCorrectHigh) / sumTest, 'FM990.00') AS testFctHighRatio,

            to_char(100.0 * baseCorrectLow / sumBase, 'FM990.00') AS baseCorrectLowRatio,
            to_char(100.0 * baseErrorLow / sumBase, 'FM990.00') AS baseErrorLowRatio,
            to_char(100.0 * baseCorrectHigh / sumBase, 'FM990.00') AS baseCorrectHighRatio,
            to_char(100.0 * baseErrorHigh / sumBase, 'FM990.00') AS baseErrorHighRatio,

            to_char(100.0 * testCorrectLow / sumTest, 'FM990.00') AS testCorrectLowRatio,
            to_char(100.0 * testErrorLow / sumTest, 'FM990.00') AS testErrorLowRatio,
            to_char(100.0 * testCorrectHigh / sumTest, 'FM990.00') AS testCorrectHighRatio,
            to_char(100.0 * testErrorHigh / sumTest, 'FM990.00') AS testErrorHighRatio,

            to_char(100.0 * (baseCorrectLow + baseCorrectHigh) / sumBase, 'FM990.00') AS basePC,
            CASE WHEN baseErrorLow + baseCorrectHigh = 0 THEN
               'N/A'
            ELSE
               to_char(100.0 * baseCorrectHigh / (baseErrorLow + baseCorrectHigh), 'FM990.00')
            END AS basePOD,
            CASE WHEN baseErrorHigh + baseCorrectHigh = 0 THEN
               'N/A'
            ELSE
               to_char(100.0 * baseErrorHigh / (baseErrorHigh + baseCorrectHigh), 'FM990.00')
            END AS baseFAR,
            CASE WHEN baseCorrectHigh + baseErrorHigh + baseErrorLow = 0 THEN
               'N/A'
            ELSE
               to_char(100.0 * baseCorrectHigh / (baseCorrectHigh + baseErrorHigh + baseErrorLow), 'FM990.00')
            END AS baseCSI,

            to_char(100.0 * (testCorrectLow + testCorrectHigh) / sumTest, 'FM990.00') AS testPC,
            CASE WHEN testErrorLow + testCorrectHigh = 0 THEN
               'N/A'
            ELSE
               to_char(100.0 * testCorrectHigh / (testErrorLow + testCorrectHigh), 'FM990.00')
            END AS testPOD,
            CASE WHEN testErrorHigh + testCorrectHigh = 0 THEN
               'N/A'
            ELSE
               to_char(100.0 * testErrorHigh / (testErrorHigh + testCorrectHigh), 'FM990.00')
            END AS testFAR,
            CASE WHEN testCorrectHigh + testErrorHigh + testErrorLow = 0 THEN
               'N/A'
            ELSE
               to_char(100.0 * testCorrectHigh / (testCorrectHigh + testErrorHigh + testErrorLow), 'FM990.00')
            END AS testCSI
         FROM
            (
               SELECT
                  bc.correctlow AS baseCorrectLow,
                  bc.errorlow AS baseErrorLow,
                  bc.correcthigh AS baseCorrectHigh,
                  bc.errorhigh AS baseErrorHigh,
                  bc.correctlow + bc.errorlow + bc.correcthigh + bc.errorhigh AS sumBase,
                  tc.correctlow AS testCorrectLow,
                  tc.errorlow AS testErrorLow,
                  tc.correcthigh AS testCorrectHigh,
                  tc.errorhigh AS testErrorHigh,
                  tc.correctlow + tc.errorlow + tc.correcthigh + tc.errorhigh AS sumTest
               FROM
                  comparison AS comp
               INNER JOIN
                  contingency AS bc
               ON
                  comp.id = bc.comparison AND
                  comp.base = bc.serie
               INNER JOIN
                  contingency AS tc
               ON
                  comp.id = tc.comparison AND
                  comp.test = tc.serie AND
                  bc.region = tc.region AND
                  bc.variable = tc.variable AND
                  bc.pivot = tc.pivot
               INNER JOIN
                  variable
               ON
                  bc.variable = variable.id
               WHERE
                  comp.id = {$comparisonId} AND
                  bc.region = {$regionId} AND
                  bc.variable = {$variableId} AND
                  bc.pivot = {$pivotValue}
            ) AS cont";
//       print("\t<pre>{$query}</pre>\n");
      $stm = $conn->query($query);
      $result = $stm->fetchAll();
      $result = $result[0];

      print("\t<h2>{$regionAbrv} - {$variableAbrv} - Pivot = {$pivotValue}</h2>\n");

      print("\t<table>\n");
      print("\t\t<thead>\n");
      print("\t\t\t<tr>\n");
      print("\t\t\t\t<th>Forecast</th>\n");
      print("\t\t\t\t<th colspan=\"2\">No</th>\n");
      print("\t\t\t\t<th colspan=\"2\">Yes</th>\n");
      print("\t\t\t\t<th colspan=\"2\">Observation Total</th>\n");
      print("\t\t\t</tr>\n");
      print("\t\t\t<tr>\n");
      print("\t\t\t\t<th>observation</th>\n");
      print("\t\t\t\t<th>Base</th>\n");
      print("\t\t\t\t<th>Test</th>\n");
      print("\t\t\t\t<th>Base</th>\n");
      print("\t\t\t\t<th>Test</th>\n");
      print("\t\t\t\t<th>Base</th>\n");
      print("\t\t\t\t<th>Test</th>\n");
      print("\t\t\t</tr>\n");
      print("\t\t</thead>\n");
      print("\t\t<tbody>\n");
      print("\t\t\t<tr>\n");
      print("\t\t\t\t<th>No</th>\n");
      print("\t\t\t\t<td>{$result['basecorrectlow']} ({$result['basecorrectlowratio']}%)</td>\n");
      print("\t\t\t\t<td>{$result['testcorrectlow']} ({$result['testcorrectlowratio']}%)</td>\n");
      print("\t\t\t\t<td>{$result['baseerrorhigh']} ({$result['baseerrorhighratio']}%)</td>\n");
      print("\t\t\t\t<td>{$result['testerrorhigh']} ({$result['testerrorhighratio']}%)</td>\n");
      print("\t\t\t\t<td>{$result['baseobslow']} ({$result['baseobslowratio']}%)</td>\n");
      print("\t\t\t\t<td>{$result['testobslow']} ({$result['testobslowratio']}%)</td>\n");
      print("\t\t\t</tr>\n");
      print("\t\t\t<tr>\n");
      print("\t\t\t\t<th>Yes</th>\n");
      print("\t\t\t\t<td>{$result['baseerrorlow']} ({$result['baseerrorlowratio']}%)</td>\n");
      print("\t\t\t\t<td>{$result['testerrorlow']} ({$result['testerrorlowratio']}%)</td>\n");
      print("\t\t\t\t<td>{$result['basecorrecthigh']} ({$result['basecorrecthighratio']}%)</td>\n");
      print("\t\t\t\t<td>{$result['testcorrecthigh']} ({$result['testcorrecthighratio']}%)</td>\n");
      print("\t\t\t\t<td>{$result['baseobshigh']} ({$result['baseobshighration']}%)</td>\n");
      print("\t\t\t\t<td>{$result['testobshigh']} ({$result['testobshighratio']}%)</td>\n");
      print("\t\t\t</tr>\n");
      print("\t\t\t<tr>\n");
      print("\t\t\t\t<th>Forecast Total</th>\n");
      print("\t\t\t\t<td>{$result['basefctlow']} ({$result['basefctlowratio']}%)</td>\n");
      print("\t\t\t\t<td>{$result['testfctlow']} ({$result['testfctlowratio']}%)</td>\n");
      print("\t\t\t\t<td>{$result['basefcthigh']} ({$result['basefcthighratio']}%)</td>\n");
      print("\t\t\t\t<td>{$result['testfcthigh']} ({$result['testfcthighratio']}%)</td>\n");
      print("\t\t\t\t<td>{$result['sumbase']} (100.00%)</td>\n");
      print("\t\t\t\t<td>{$result['sumtest']} (100.00%)</td>\n");
      print("\t\t\t</tr>\n");
      print("\t\t</tbody>\n");
      print("\t</table>\n");

      print("\t<br/>\n");

      print("\t<table>\n");
      print("\t\t<thead>\n");
      print("\t\t\t<tr>\n");
      print("\t\t\t\t<th>Statistic</th>\n");
      print("\t\t\t\t<th>Base</th>\n");
      print("\t\t\t\t<th>Test</th>\n");
      print("\t\t\t</tr>\n");
      print("\t\t</thead>\n");
      print("\t\t<tbody>\n");
      print("\t\t\t<tr>\n");
      print("\t\t\t\t<th>Percentage Correct</th>\n");
      print("\t\t\t\t<td>{$result['basepc']} %</td>\n");
      print("\t\t\t\t<td>{$result['testpc']} %</td>\n");
      print("\t\t\t</tr>\n");
      print("\t\t\t<tr>\n");
      print("\t\t\t\t<th>Probability of detection</th>\n");
      print("\t\t\t\t<td>{$result['basepod']} %</td>\n");
      print("\t\t\t\t<td>{$result['testpod']} %</td>\n");
      print("\t\t\t</tr>\n");
      print("\t\t\t<tr>\n");
      print("\t\t\t\t<th>False Alarm Ratio</th>\n");
      print("\t\t\t\t<td>{$result['basefar']} %</td>\n");
      print("\t\t\t\t<td>{$result['testfar']} %</td>\n");
      print("\t\t\t</tr>\n");
      print("\t\t\t<tr>\n");
      print("\t\t\t\t<th>Critical Skill Index</th>\n");
      print("\t\t\t\t<td>{$result['basecsi']} %</td>\n");
      print("\t\t\t\t<td>{$result['testcsi']} %</td>\n");
      print("\t\t\t</tr>\n");
      print("\t\t</tbody>\n");
      print("\t</table>\n");
   }


   //---------------------------------------------------------------------------
   // p r i n t C o n t i n g e n c y T a b l e s
   //---------------------------------------------------------------------------
   function printContingencyTables($conn, $comparisonId) {
      print("\t<h1>Contingency Tables</h1>\n");

      // Option 1
      // Faire des boucles imbriquées Region -> Variable -> Pivot fait plusieurs requêtes redontantes avec des JOINs

      // Option 2
      // Faire une seule requête avec toutes les données puis la parcourir pour obtenir tous les (region, variable, pivot)

      // Option 3
      // Créer une talbe temporaire avec toutes les informations requises puis l'utiliser pour faire de multiples requêtes
      // Inconvénients : La table temporaire à une portée de session.  Si les connexions sont persistantes, il faut
      // absolument faire le ménage soit-même!
      $query = "
         SELECT DISTINCT
            region.id AS id,
            region.abreviation AS abrv
         FROM
            comparison AS comp
         INNER JOIN
            contingency AS bc
         ON
            comp.id = bc.comparison AND
            comp.base = bc.serie
         INNER JOIN
            contingency AS tc
         ON
            comp.id = tc.comparison AND
            comp.test = tc.serie AND
            bc.region = tc.region AND
            bc.variable = tc.variable AND
            bc.pivot = tc.pivot
         INNER JOIN
            region
         ON
            bc.region = region.id
         WHERE
            comp.id = {$comparisonId}";
      foreach ($conn->query($query) as $regionRow) {
         $query = "
            SELECT DISTINCT
               variable.id AS id,
               variable.abreviation AS abrv
            FROM
               comparison AS comp
            INNER JOIN
               contingency AS bc
            ON
               comp.id = bc.comparison AND
               comp.base = bc.serie
            INNER JOIN
               contingency AS tc
            ON
               comp.id = tc.comparison AND
               comp.test = tc.serie AND
               bc.region = tc.region AND
               bc.variable = tc.variable AND
               bc.pivot = tc.pivot
            INNER JOIN
               variable
            ON
               bc.variable = variable.id
            WHERE
               comp.id = {$comparisonId} AND
               bc.region = {$regionRow['id']}";
         foreach ($conn->query($query) as $variableRow) {
            $query = "
               SELECT DISTINCT
                  bc.pivot AS value
               FROM
                  comparison AS comp
               INNER JOIN
                  contingency AS bc
               ON
                  comp.id = bc.comparison AND
                  comp.base = bc.serie
               INNER JOIN
                  contingency AS tc
               ON
                  comp.id = tc.comparison AND
                  comp.test = tc.serie AND
                  bc.region = tc.region AND
                  bc.variable = tc.variable AND
                  bc.pivot = tc.pivot
               INNER JOIN
                  variable
               ON
                  bc.variable = variable.id
               WHERE
                  comp.id = {$comparisonId} AND
                  bc.region = {$regionRow['id']} AND
                  bc.variable = {$variableRow['id']}";
            foreach ($conn->query($query) as $pivotRow) {
               printContingencyTable($conn, $comparisonId, $regionRow['id'], $regionRow['abrv'], $variableRow['id'], $variableRow['abrv'], $pivotRow['value']);
            }
         }
      }
   }


   //---------------------------------------------------------------------------
   // M A I N
   //---------------------------------------------------------------------------
   if ( $_SERVER['REQUEST_METHOD'] <> 'GET' ) {
      $errorMsg = gmdate("Y-m-d H:i:s") . " - " . __FILE__ . "\n";
      $errorMsg .= "This script should only be called with an HTTP GET!\n";
      $errorMsg .= "It got called with : " . $_SERVER['REQUEST_METHOD'] . "\n";
      die($errorMsg);
   }

   if ( array_key_exists("id", $_GET) ) {
         $comparisonId = $_GET['id'];

      require_once "../../include/VaqumDb.php";
      $conn = new VaqumDb();

      $query = "SELECT count(*) AS count FROM comparison WHERE id = $comparisonId";
      $stm = $conn->query($query);
      $result = $stm->fetchAll();
      if ( $result[0]['count'] == 1 ) {
         printComparisonInfo($conn, $comparisonId);
         printMaxResultTable($conn, $comparisonId);
         printContingencyTables($conn, $comparisonId);
      } else {
         print("\t<h1>Error! ☹</h1>\n");
         print("\t<p class=\"error\">The specified comparison ({$comparisonId}) could not found!</p>\n");
      }

      // Selon la doc de PDO, ceci devrait fermer la connexion, mais ça ne fonctionne pas
      // http://www.php.net/manual/en/pdo.connections.php
      $conn = NULL;
   } else {
      print("<p>No comparison ID specified!</p>");
   }
?>
</body>
</html>