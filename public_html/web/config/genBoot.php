<?php
   #----------------------------------------------------------------------------
   # Constants
   #----------------------------------------------------------------------------
   $outputFolderPath = "/data/aqli06/afsudev/vaqum";
   $bootstrapScriptPath = "/home/afsu/air/verification/trunk/scripts/strappIt.sh";
   $statsBaseDirPath = "{$outputFolderPath}/stats";

   require_once(__DIR__ . "/../include/error2exception.php");

   function bootstrap($bootstrapScriptPath, $bootstrapInputPath, $statsDirPath, $strapDirPath) {
      $cmd  = "/bin/bash ";
      $cmd .= "{$bootstrapScriptPath} ";
      $cmd .= escapeshellarg( $bootstrapInputPath ) . " ";
      $cmd .= escapeshellarg( $statsDirPath ) . " ";
      $cmd .= escapeshellarg( $strapDirPath ) . " ";
      $cmd .= '2>&1';

      $before = time();
      exec($cmd, $cmdOutput, $retVal);
      $after = time();
      print("-- " . date("c") . " : " . "Bootstrap executation time = " . ($after - $before) . " s\n");
      if ($retVal != 0) {
         print("-- Execution of the bootstrap script failed!\n");
         print("-- Command issued :\n");
         print("-- {$cmd} \n");
         print("-- Output :\n");
         print_r($cmdOutput);
         exit(1);
      }
   }


   function mergeCsv($inputFolderPath, $seperator, $outputFilePath) {
      $outputFileHandle = fopen($outputFilePath, "w");
      $headerWritten = false;

      foreach (glob("{$inputFolderPath}/*") as $filePath) {
         $header = true;
         $fileName = basename($filePath);
         $fileBaseName = substr($fileName, 0, strpos($fileName, ".csv"));

         $fileHandle = fopen($filePath, "r");
         $lines = explode("\n", fread($fileHandle, filesize($filePath)));
         fclose($fileHandle);

         foreach ($lines as $line) {
            if (strlen($line) > 0) {
               if ($header) {
                  if (! $headerWritten) {
                     fwrite($outputFileHandle, "name{$seperator}{$line}\n");
                     $headerWritten = true;
                  }
                  $header = false;
               } else {
                  fwrite($outputFileHandle, "{$fileBaseName}{$seperator}{$line}\n");
               }
            }
         }
      }

      fclose($outputFileHandle);
   }


   function mergeStatCsv($statsBaseDirPath, $statsDirPath, $targetPath) {
      $outputFilePath = "{$statsBaseDirPath}/{$targetPath}.csv";
      mergeCsv($statsDirPath, ",", $outputFilePath);

      return $outputFilePath;
   }


   function renderGraph($graphOutputDirPath, $strapDirPath, $baseName, $testName) {
      $baseColMapping = array(
         "bias" => 4,
         "corr" => 6,
         "rmse" => 7,
         "urmse" => 8
      );
      $testColShift = 7;

      $descriptorspec = array(
         0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
         1 => array("file", "/dev/null", "a"),  // stdout is a file to write to
         2 => array("file", "/dev/null", "a") // stderr is a file to write to
      );

      foreach (glob("{$strapDirPath}/*") as $filePath) {
         $fileName = basename($filePath);

         $parts = explode("_", substr($fileName, 0, strpos($fileName, ".csv")));
         $region = $parts[0];
         $pollutant = $parts[1];

         foreach ( array("bias", "corr", "rmse", "urmse") as $statName ) {
            $baseCol = $baseColMapping[$statName];
            $testCol = $baseColMapping[$statName] + $testColShift;
            $rScript = "
               strap <- read.table(\"{$filePath}\",header=TRUE, sep=\",\", dec=\".\")

               tval1 <- sort(strap[,{$baseCol}])
               tval2 <- sort(strap[,{$testCol}])

               val1 <- tval1[26:975]
               val2 <- tval2[26:975]

               xmin <- min(val1, val2)
               xmax <- max(val1, val2)

               png(\"{$graphOutputDirPath}/{$pollutant}_{$region}_{$statName}.png\", width=720, height=480, units=\"px\", pointsize=16, type=\"cairo\")

               p1 <- hist(val1, breaks=40, freq=TRUE)
               p2 <- hist(val2, breaks=40, freq=TRUE)

               plot( p1, col=rgb(0,0,1,1/4), border=rgb(0,0,1,1/4), xlim=c(xmin,xmax), main=\"{$baseName} vs\n{$testName}\n{$pollutant}, {$region}\", sub=\"confiance = 95%\", xlab=\"{$statName}\")
               plot( p2, col=rgb(1,0,0,1/4), border=rgb(1,0,0,1/4), xlim=c(xmin,xmax), add=T)
               legend(\"topleft\", c(\"{$baseName}\",\"{$testName}\"), cex=0.8, fill=c(rgb(0,0,1,1/4),rgb(1,0,0,1/4)), border=c(rgb(0,0,1,1/4),rgb(1,0,0,1/4)))
               box()
               dev.off()
               q()";
            $processHandle = proc_open("R --vanilla", $descriptorspec, $pipes);
            if (is_resource($processHandle)) {
               fwrite($pipes[0], $rScript);
               fclose($pipes[0]);
               $retVal = proc_close($processHandle);
               if ($retVal != 0) {
                  print("-- " . date("c") . " : " . "R execution has failed!\n");
                  exit(1);
               }
            } else {
               print("-- " . date("c") . " : " . "Somewhere, something has gone terribly wrong!\n");
               exit(1);
            }
         }
      }
   }

   function calculateMetAreaStats($conn, $comparisonId, $variableId) {
      print("-- " . date("c") . " : ");
      print("-- calculateMetAreaStats :\n");
      $query = "
         INSERT INTO metareats (
            comparison,
            metarea,
            variable,
            startdate,
            obs,
            base,
            test
         ) SELECT
            {$comparisonId},
            metareatsconfig.metarea,
            {$variableId},
            triplet.date,
            avg(triplet.obs),
            avg(triplet.base),
            avg(triplet.test)
         FROM
            metareatsconfig
         INNER JOIN
            metarea
         ON
            metareatsconfig.metarea = metarea.id
         INNER JOIN
            domainstation
         ON
            ST_Intersects(metarea.geometry, domainstation.position)
         INNER JOIN
            triplet
         ON
            triplet.station = domainstation.id
         WHERE
            metareatsconfig.comparison = {$comparisonId} AND
            metareatsconfig.variable = {$variableId}
         GROUP BY
            metareatsconfig.metarea,
            date
            ";
      $nbInserts = $conn->niceExec($query, true, true);
      print("-- calculateMetAreaStats Inserts = $nbInserts\n");
   }


   function binStats($conn, $comparisonId, $regionId, $variableId, $role) {
      if ( $role != 'base' && $role != 'test' ) {
         die("Role must be 'base' or 'test'!  Got '{$role}'");
      }
      $query = "
         INSERT INTO binStat (
            comparison,
            serie,
            region,
            bin,
            count,
            varobs,
            varmod,
            stdobs,
            stdmod,
            avgobs,
            avgmod,
            corr,
            rmse,
            urmse,
            mbias)
         SELECT
            {$comparisonId},
            (SELECT {$role} FROM comparison WHERE id = {$comparisonId}),
            {$regionId},
            bin,
            (stats).count,
            (stats).varx,
            (stats).vary,
            (stats).stdx,
            (stats).stdy,
            (stats).avgx,
            (stats).avgy,
            (stats).corr,
            (stats).rmse,
            (stats).urmse,
            (stats).mbias
         FROM
            (
               SELECT
                  bin({$variableId}, obs),
                  stats(obs, {$role})
               FROM
                  triplet
               GROUP BY
                  bin
            ) AS tempy";
      $conn->niceExec($query, true, true);
   }

   function forecastHourStats($conn, $comparisonId, $regionId, $variableId, $role) {
      if ( $role != 'base' && $role != 'test' ) {
         die("Role must be 'base' or 'test'!  Got '{$role}'");
      }
      $query = "
         INSERT INTO hourStat (
            comparison,
            serie,
            region,
            variable,
            startHour,
            forecastHour,
            count,
            varobs,
            varmod,
            stdobs,
            stdmod,
            avgobs,
            avgmod,
            corr,
            rmse,
            urmse,
            mbias)
         SELECT
            {$comparisonId},
            (SELECT {$role} FROM comparison WHERE id = {$comparisonId}),
            {$regionId},
            {$variableId},
            startHour,
            forecastHour,
            (stats).count,
            (stats).varx,
            (stats).vary,
            (stats).stdx,
            (stats).stdy,
            (stats).avgx,
            (stats).avgy,
            (stats).corr,
            (stats).rmse,
            (stats).urmse,
            (stats).mbias
         FROM
            (
               SELECT
                  CAST(date_part('hour', datestart) AS INTEGER) AS startHour,
                  CAST(EXTRACT(epoch FROM date - datestart) / 3600 AS INTEGER) AS forecastHour,
                  stats(obs, {$role})
               FROM
                  triplet
               GROUP BY
                  CAST(date_part('hour', datestart) AS INTEGER),
                  CAST(EXTRACT(epoch FROM date - datestart) / 3600 AS INTEGER)
            ) AS tempy";
      $conn->niceExec($query, true, true);
   }


   function flushResults($conn, $comparisonId) {
      $query = "DELETE FROM contingency WHERE comparison = $comparisonId";
      $conn->niceExec($query, true, true);
      $query = "DELETE FROM distribution WHERE comparison = $comparisonId";
      $conn->niceExec($query, true, true);
      $query = "DELETE FROM result WHERE comparison = $comparisonId";
      $conn->niceExec($query, true, true);
      $query = "DELETE FROM hourStat WHERE comparison = $comparisonId";
      $conn->niceExec($query, true, true);
      $query = "DELETE FROM metareats WHERE comparison = $comparisonId";
      $conn->niceExec($query, true, true);
      $query = "DELETE FROM binStat WHERE comparison = $comparisonId";
      $conn->niceExec($query, true, true);
   }


   function generateBootsrapInput($conn, $bootstrapInputPath, $comparisonId) {
      # FIXME : Once again the ugly hack to prevent the planner from doing a very
      # stupid seq scan on forecast or some other huge table
      $query = "SET enable_seqscan = off";
      $conn->niceExec($query, true, true);

      flushResults($conn, $comparisonId);

      $query = "SELECT domain FROM comparison WHERE id = $comparisonId";
      $statement = $conn->query($query) or die( date("c") . " : " . "Query execution failed! Offending query :\n" . $query . "\n" . print_r($conn->errorinfo(), true) );
      $res = $statement->fetchAll();
      $domainId = $res[0]['domain'];

      $query = "
         CREATE TEMPORARY TABLE domainstation AS SELECT
            station.id,
            station.position
         FROM
            stationsnapshotcontent AS station
         INNER JOIN
            serie
         ON
            serie.stationsnapshot = station.stationsnapshot
         INNER JOIN
            comparison
         ON
            serie.id = comparison.base AND
            comparison.id = $comparisonId
         INNER JOIN
            region
         ON
            region.id = comparison.domain AND
            ST_Intersects(station.position, region.geometry)
         ORDER BY
            id;";
      print("-- " . date("c") . " : ");
      $conn->niceExec($query, true, true);

      $query = "
         CREATE TEMPORARY TABLE comparisonInt AS SELECT
            baseInt.id AS baseIntId,
            testInt.id AS testIntId,
            baseInt.dateStart
         FROM
            comparison
         INNER JOIN
            integration AS baseInt
         ON
            baseInt.serie = comparison.base AND
            baseInt.datestart >= comparison.datebegin AND
            baseInt.datestart <= comparison.dateend
         INNER JOIN
            integration AS testInt
         ON
            testInt.serie = comparison.test AND
            testInt.datestart >= comparison.datebegin AND
            testInt.datestart <= comparison.dateend AND
            baseInt.datestart = testInt.datestart + comparison.integrationOffset
         WHERE
            comparison.id = $comparisonId
         ORDER BY
            dateStart;";
      print("-- " . date("c") . " : ");
      $conn->niceExec($query, true, true);

      $conn->niceExec("CREATE INDEX ON comparisonInt (baseIntId);", true, true);
      $conn->niceExec("CREATE INDEX ON comparisonInt (testIntId);", true, true);
      $conn->niceExec("CREATE INDEX ON comparisonInt (datestart);", true, true);

      $query = "
         SELECT
            variable.id AS variable,
            variable.abreviation,
            pivot.value AS pivot
         FROM
            comparison_region_dataset_method AS crdm
         INNER JOIN
            method
         ON
            crdm.method = method.id
         INNER JOIN
            variable
         ON
            method.variable = variable.id
         LEFT OUTER JOIN
            pivot
         ON
            variable.id = pivot.variable
         WHERE
            crdm.comparison = $comparisonId
         GROUP BY
            variable.id,
            variable.abreviation,
            pivot.value;";
      print("-- " . date("c") . " : ");
      print("-- Looping on the results of :\n");
      print($query . "\n");

      $variableRes = $conn->query($query) or die( date("c") . " : " . "Query execution failed! Offending query :\n" . $query . "\n" . print_r($conn->errorinfo(), true) );
      foreach ( $variableRes as $variableRow ) {
         $variableId = $variableRow['variable'];
         $variableAbrv = $variableRow['abreviation'];
         $pivot = $variableRow['pivot'];

         $query = "
            CREATE TEMPORARY TABLE base AS SELECT
               comparisonInt.datestart,
               forecast.validityDate AS date,
               forecast.station,
               forecast.variable,
               forecast.value
            FROM
               comparisonInt
            INNER JOIN
               forecast
            ON
               forecast.integration = comparisonInt.baseIntId
            WHERE
               forecast.variable = {$variableId} AND
               forecast.station IN (SELECT id FROM domainstation);";
         $nbBaseRows = $conn->niceExec($query, true, true);

         $query = "
            CREATE TEMPORARY TABLE test AS SELECT
               comparisonInt.datestart,
               forecast.validityDate AS date,
               forecast.station,
               forecast.variable,
               forecast.value
            FROM
               comparisonInt
            INNER JOIN
               forecast
            ON
               forecast.integration = comparisonInt.testIntId
            WHERE
               forecast.variable = {$variableId} AND
               forecast.station IN (SELECT id FROM domainstation);";
         $nbTestRows = $conn->niceExec($query, true, true);

         if ( ($nbBaseRows > 0) && ($nbTestRows > 0) ) {
            $query = "SELECT min((SELECT max(date) FROM base), (SELECT max(date) FROM test)) AS maxdate;";
            print($query . "\n");
            $statement = $conn->query($query);
            if ($statement === false) {
               print("-- " . date("c") . " : " . "Query execution failed!  Offending query :\n");
               print($query . "\n");
            }
            $result = $statement->fetchAll();
            $maxDate = $result[0]['maxdate'];
            print("-- maxDate = $maxDate");

            # Loop on the regions
            $query = "
               SELECT DISTINCT
                  region
               FROM
                  comparison_region_dataset_method AS crdm
               INNER JOIN
                  method
               ON
                  crdm.method = method.id AND
                  method.variable = {$variableId}
               WHERE
                  comparison = {$comparisonId};";
            print("-- " . date("c") . " : ");
            print($query . "\n");
            $regionRes = $conn->query($query) or die( "Query execution failed! Offending query :\n" . $query . "\n" . print_r($conn->errorinfo(), true) );
            foreach ( $regionRes as $regionRow ) {
               $regionId = $regionRow['region'];

               $query = "SELECT abreviation FROM region WHERE id = {$regionId}";
               $statement = $conn->conn->prepare($query) or die( "Query execution failed! Offending query :\n" . $query . "\n" . print_r($conn->errorinfo(), true) );
               $statement->execute();
               $result = $statement->fetch(PDO::FETCH_ASSOC);
               $regionAbrv = $result['abreviation'];

               $filePath = "{$bootstrapInputPath}/{$regionAbrv}_{$variableAbrv}.csv";
               $fileHandle = fopen( $filePath, "w" );
               fwrite($fileHandle, "obs|base|test\n");

               $query = "
                  CREATE TEMPORARY TABLE obs AS SELECT
                     observation.station,
                     observation.startDate AS date,
                     observation.value
                  FROM
                     observation
                  INNER JOIN
                     comparison_region_dataset_method AS crdm
                  ON
                     crdm.comparison = {$comparisonId} AND
                     crdm.region = {$regionId} AND
                     observation.method = crdm.method AND
                     observation.dataset = crdm.dataset
                  INNER JOIN
                     method
                  ON
                     crdm.method = method.id AND
                     method.variable = {$variableId}
                  WHERE
                     observation.duration = '1 hour' AND
                     observation.startDate >= (SELECT datebegin FROM comparison WHERE id = {$comparisonId}) AND
                     observation.startDate <= '{$maxDate}' AND
                     observation.station IN (
                        SELECT
                           domainstation.id
                        FROM
                           domainstation
                        INNER JOIN
                           region
                        ON
                           region.id = {$regionId} AND
                           ST_Intersects(domainstation.position, region.geometry));";
               $conn->niceExec($query, true, true);

               $query = "
                  CREATE TEMPORARY TABLE triplet AS SELECT
                     base.station,
                     base.datestart,
                     base.date,
                     obs.value AS obs,
                     base.value AS base,
                     test.value AS test
                  FROM
                     obs
                  INNER JOIN
                     base
                  ON
                     obs.date = base.date AND
                     obs.station = base.station
                  INNER JOIN
                     test
                  ON
                     obs.date = test.date AND
                     base.datestart = test.datestart AND
                     base.station = test.station;";
               $conn->niceExec($query, true, true);

               // Only compute metareats if we are processing the complete domain
               if ( $regionId == $domainId ) {
                  print("-- " . date("c") . " : ");
                  print("-- regionId == domainId\n");
                  calculateMetAreaStats($conn, $comparisonId, $variableId);
               }

               binStats($conn, $comparisonId, $regionId, $variableId, 'base');
               binStats($conn, $comparisonId, $regionId, $variableId, 'test');

               forecastHourStats($conn, $comparisonId, $regionId, $variableId, 'base');
               forecastHourStats($conn, $comparisonId, $regionId, $variableId, 'test');



               $query = "
                  CREATE TEMPORARY TABLE dailymax AS SELECT
                     station,
                     date_trunc('day', date) AS day,
                     max(obs) AS obs,
                     max(base) AS base,
                     max(test) AS test
                  FROM
                     triplet
                  WHERE
                     date_part('hour', datestart) = 12 AND
                     datestart + '12 hour'::INTERVAL = date_trunc('day', date) AND
                     date_part('hour', date)::INTEGER <> 0
                  GROUP BY
                     station,
                     date_trunc('day', date)
                  UNION ALL SELECT
                     station,
                     date_trunc('day', date) AS day,
                     max(obs) AS obs,
                     max(base) AS base,
                     max(test) AS test
                  FROM
                     triplet
                  WHERE
                     date_part('hour', datestart) = 00 AND
                     date_part('hour', date)::INTEGER <> 0
                  GROUP BY
                     station,
                     date_trunc('day', date);";
               $conn->niceExec($query, true, true);

               $query = "
                  INSERT INTO result (
                     comparison,
                     serie,
                     region,
                     variable,
                     timegrouping,
                     count,
                     varobs,
                     varmod,
                     stdobs,
                     stdmod,
                     avgobs,
                     avgmod,
                     corr,
                     rmse,
                     urmse,
                     mbias)
                  SELECT
                     {$comparisonId},
                     (SELECT base FROM comparison WHERE id = {$comparisonId}),
                     {$regionId},
                     {$variableId},
                     2,
                     (stats(obs, base)).count,
                     (stats(obs, base)).varx,
                     (stats(obs, base)).vary,
                     (stats(obs, base)).stdx,
                     (stats(obs, base)).stdy,
                     (stats(obs, base)).avgx,
                     (stats(obs, base)).avgy,
                     (stats(obs, base)).corr,
                     (stats(obs, base)).rmse,
                     (stats(obs, base)).urmse,
                     (stats(obs, base)).mbias
                  FROM
                     dailymax;";
               $conn->niceExec($query, true, true);

               $query = "
                  INSERT INTO result (
                     comparison,
                     serie,
                     region,
                     variable,
                     timegrouping,
                     count,
                     varobs,
                     varmod,
                     stdobs,
                     stdmod,
                     avgobs,
                     avgmod,
                     corr,
                     rmse,
                     urmse,
                     mbias)
                  SELECT
                     {$comparisonId},
                     (SELECT test FROM comparison WHERE id = {$comparisonId}),
                     {$regionId},
                     {$variableId},
                     2,
                     (stats(obs, test)).count,
                     (stats(obs, test)).varx,
                     (stats(obs, test)).vary,
                     (stats(obs, test)).stdx,
                     (stats(obs, test)).stdy,
                     (stats(obs, test)).avgx,
                     (stats(obs, test)).avgy,
                     (stats(obs, test)).corr,
                     (stats(obs, test)).rmse,
                     (stats(obs, test)).urmse,
                     (stats(obs, test)).mbias
                  FROM
                     dailymax;";
               $conn->niceExec($query, true, true);


               if ( is_numeric($pivot) ) {
                  $query = "
                     INSERT INTO contingency (
                        comparison,
                        serie,
                        region,
                        variable,
                        pivot,
                        correctlow,
                        errorHigh,
                        errorLow,
                        correctHigh
                     ) SELECT
                        {$comparisonId},
                        (SELECT base FROM comparison WHERE id = {$comparisonId}),
                        {$regionId},
                        {$variableId},
                        {$pivot},
                        SUM(CASE WHEN obs < {$pivot} AND base < {$pivot} THEN 1 ELSE 0 END) AS correctLow,
                        SUM(CASE WHEN obs < {$pivot} AND base >= {$pivot} THEN 1 ELSE 0 END) AS errorHigh,
                        SUM(CASE WHEN obs >= {$pivot} AND base < {$pivot} THEN 1 ELSE 0 END) AS errorLow,
                        SUM(CASE WHEN obs >= {$pivot} AND base >= {$pivot} THEN 1 ELSE 0 END) AS correctHigh
                     FROM
                        triplet;";
                  $conn->niceExec($query, true, true);

                  $query = "
                     INSERT INTO contingency (
                        comparison,
                        serie,
                        region,
                        variable,
                        pivot,
                        correctlow,
                        errorHigh,
                        errorLow,
                        correctHigh
                     ) SELECT
                        {$comparisonId},
                        (SELECT test FROM comparison WHERE id = {$comparisonId}),
                        {$regionId},
                        {$variableId},
                        {$pivot},
                        SUM(CASE WHEN obs < {$pivot} AND test < {$pivot} THEN 1 ELSE 0 END) AS correctLow,
                        SUM(CASE WHEN obs < {$pivot} AND test >= {$pivot} THEN 1 ELSE 0 END) AS errorHigh,
                        SUM(CASE WHEN obs >= {$pivot} AND test < {$pivot} THEN 1 ELSE 0 END) AS errorLow,
                        SUM(CASE WHEN obs >= {$pivot} AND test >= {$pivot} THEN 1 ELSE 0 END) AS correctHigh
                     FROM
                        triplet;";
                  $conn->niceExec($query, true, true);
               }

               # Generate CSV file for legacy applications
               $query = "
                  SELECT
                     obs,
                     base,
                     test
                  FROM
                     triplet";
               $matchRes = $conn->query($query) or die( "Query execution failed! Offending query :\n" . $query . "\n" . print_r($conn->errorinfo(), true) );
               foreach ( $matchRes as $matchRow ) {
                  fwrite($fileHandle, "{$matchRow['obs']}|{$matchRow['base']}|{$matchRow['test']}\n");
               } # Loop on matches

               fclose($fileHandle);

               foreach ( array('obs', 'base', 'test') as $component ) {
                  $query = "
                     INSERT INTO distribution ( comparison, component, region, variable,  value, frequency ) SELECT
                        {$comparisonId},
                        (SELECT id FROM component WHERE name = '{$component}'),
                        {$regionId},
                        {$variableId},
                        round({$component})::INTEGER,
                        count(*)
                     FROM
                        triplet
                     GROUP BY
                        round({$component})
                     ORDER BY
                        round({$component});";
                  $conn->niceExec($query, true, true);
               }

               $conn->niceExec("DROP TABLE dailymax");
               $conn->niceExec("DROP TABLE triplet");
               $conn->niceExec("DROP TABLE obs");
            } # if we have base and test rows
         } # Loop on region
         $conn->niceExec("DROP TABLE base");
         $conn->niceExec("DROP TABLE test");
      } # Loop on variable
      $conn->niceExec("DROP TABLE domainstation");
   }


   function calculateStationStats($conn, $comparisonId) {
      $query = "SELECT calculate_station_stats({$comparisonId});";
      $conn->niceExec($query, true, true);
   }


   #----------------------------------------------------------------------------
   # M A I N
   #----------------------------------------------------------------------------
   if (PHP_SAPI == "cli") {
      if (3 == $argc) {
         ctype_digit( $argv[1] ) or die("Invalid comparison Id!");
         $comparisonId = $argv[1];
         $email = $argv[2];
      } else {
         print("Generate input for bootstrap for a give series\n\n");
         print("Usage :\n");
         print("\tphp5 {$argv[0]} <Comparison Id> <E-Mail address>\n");
         exit(1);
      }
   } else {
      die("This script is only meant to be executed from a shell!");
   }

   print("-- " . date("c") . " : " . "genBoot firing up...\n");

   umask(0022);

   require_once (__DIR__ . "/../include/VaqumDb.php");
   $conn = new VaqumDb(false);

   $query = "
      SELECT
         base.name AS baseName,
         test.name AS testName
      FROM
         comparison
      INNER JOIN
         serie AS base
      ON
         base.id = comparison.base
      INNER JOIN
         serie AS test
      ON
         test.id = comparison.test
      WHERE
         comparison.id = {$comparisonId}";
   $statement = $conn->conn->prepare($query) or die( "Query execution failed! Offending query :\n" . $query . "\n" . print_r($conn->errorinfo(), true) );
   $statement->execute();
   $result = $statement->fetch(PDO::FETCH_ASSOC);
   $baseName = $result['basename'];
   $testName = $result['testname'];
   print("-- baseName = $baseName\n");
   print("-- testName = $testName\n");

   $targetPath = "{$comparisonId}_{$baseName}_vs_{$testName}";
   $bootstrapInputPath = "{$outputFolderPath}/in/{$targetPath}";
   if ( ! file_exists($bootstrapInputPath) ) {
      mkdir($bootstrapInputPath, 0755, true);
   } else {
      array_map('unlink', glob($bootstrapInputPath . '/*'));
   }

   $statsDirPath = "{$outputFolderPath}/stats/{$targetPath}";
   if ( ! file_exists($statsDirPath) ) {
      mkdir($statsDirPath, 0755, true);
   } else {
      array_map('unlink', glob($statsDirPath . '/*'));
   }

   $strapDirPath = "{$outputFolderPath}/strap/{$targetPath}";
   if ( ! file_exists($strapDirPath) ) {
      mkdir($strapDirPath, 0755, true);
   } else {
      array_map('unlink', glob($strapDirPath . '/*'));
   }

   $graphOutputDirPath = "{$outputFolderPath}/graph/{$targetPath}";
   if ( ! file_exists($graphOutputDirPath) ) {
      mkdir($graphOutputDirPath, 0755, true);
   } else {
      array_map('unlink', glob($graphOutputDirPath . '/*'));
   }

   generateBootsrapInput($conn, $bootstrapInputPath, $comparisonId);


//    calculateStationStats($conn, $comparisonId);

   // Selon la doc de PDO, ceci devrait fermer la connexion, mais ça ne fonctionne pas
   // http://www.php.net/manual/en/pdo.connections.php
   $conn = NULL;

   print("-- " . date("c") . " : " . "Launching the bootstrap...\n");

   bootstrap($bootstrapScriptPath, $bootstrapInputPath, $statsDirPath, $strapDirPath);

   print("-- " . date("c") . " : " . "Bootstrap complete :-)\n");

   $statFilePath = mergeStatCsv($statsBaseDirPath, $statsDirPath, $targetPath);

   print("-- " . date("c") . " : " . "Merge complete\n");

   renderGraph($graphOutputDirPath, $strapDirPath, $baseName, $testName);

   $header = "Content-Type: text/plain; charset=\"utf-8\"\r\n" . 
             'Reply-To: samuel.gilbert@canada.ca, hugo.landry@canada.ca, paul-andre.beaulieu@canada.ca';

   $msg = "Bonjour,\n\nLa comparison entre {$baseName} et {$testName} ({$comparisonId}) est terminée.\n\n";
   $msg .= "Les statistiques sont disponibles dans le fichier :\n";
   $msg .= $statFilePath;
   $msg .= "\n\nLes graphiques des distributions des statistiques générées par le bootstrap sont dans le répertoire :\n";
   $msg .= $graphOutputDirPath;
   mail($email, "Comparison de {$baseName} vs {$testName}", $msg, $header);

   print("-- " . date("c") . " : " . "E-Mail sent.  Work complete :-)\n");
?>
