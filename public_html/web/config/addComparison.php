<?php

//------------------------------------------------------------------------------
// c o n f i g u r e 4 D a t a s e t
//------------------------------------------------------------------------------
function configure4Dataset($conn, $comparisonId, $datasetId, $domainId) {
   $regionIds = array(2, 3, 4, 5, 7, 8, $domainId);
   foreach ($regionIds as $regionId) {
      foreach (array(128, 129, 131) as $methodId) {
         $query = "
            INSERT INTO comparison_region_dataset_method (
               comparison,
               region,
               dataset,
               method
            ) VALUES (
               $comparisonId,
               $regionId,
               $datasetId,
               $methodId
            )";
         $conn->niceExec($query);
      }
   }
}


//------------------------------------------------------------------------------
// M A I N
//------------------------------------------------------------------------------
if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
   require_once "../include/VaqumDb.php";
   $conn = new VaqumDb();

   // FIXME : Sanitze input!
   $dateBeginStr = $_POST['dateBegin'];
   $dateEndStr = $_POST['dateEnd'];
   $baseSerie = $_POST['baseSerie'];
   $testSerie = $_POST['testSerie'];
   $integrationOffset = $_POST['integrationOffset'];

   $conn->niceExec("BEGIN");

   $query = "SELECT create_comparaison($baseSerie, $testSerie, '$dateBeginStr', '$dateEndStr', '{$integrationOffset} hours');";
   $res = $conn->query($query);
   $tmpArr = $res->fetchAll(PDO::FETCH_COLUMN);
   // Return the new comparison ID
   $comparisonId = $tmpArr[0];

   $query = "SELECT domain FROM comparison WHERE id = {$comparisonId}";
   $res = $conn->query($query);
   $tmpArr = $res->fetchAll(PDO::FETCH_COLUMN);
   $domainId = $tmpArr[0];

   // FIXME : Hard coded for CPOP verification
   configure4Dataset($conn, $comparisonId, 9, $domainId);
   configure4Dataset($conn, $comparisonId, 8, $domainId);
   configure4Dataset($conn, $comparisonId, 6, $domainId);
   configure4Dataset($conn, $comparisonId, 4, $domainId);
   configure4Dataset($conn, $comparisonId, 3, $domainId);

   // FIXME : Some more ugly hard-coded config that should be done through a nice Webapp
   $query = "
      INSERT INTO metareatsconfig (
         comparison,
         metarea,
         variable
      ) SELECT
         {$comparisonId},
         metarea.id,
         variable.id
      FROM
         metarea
      CROSS JOIN
         variable
      WHERE
         variable.abreviation IN ('O3', 'NO2', 'PM2.5') AND
         metarea.name IN ('Vancouver', 'Edmonton', 'Calgary', 'Winnipeg', 'Saskatoon', 'Toronto', 'Ottawa - Gatineau', 'Halifax', 'Montréal', 'Québec', 'Moncton', 'Hamilton')";
   $conn->niceExec($query);

   $conn->niceExec("COMMIT");

   require_once "../include/Ldap.php";

   $cmd = ". /users/dor/afsu/dev/.profile.d/postgres.azathoth;";
   $cmd .= "nohup /usr/bin/php5 " . getcwd() . "/genBoot.php ";
   $cmd .= escapeshellarg( $comparisonId ) . " ";
   $cmd .= escapeshellarg( Ldap::getEmail($_SERVER['PHP_AUTH_USER']) ) . " ";
   $cmd .= '> /dev/null 2>&1 & echo -n $!';
   $pid = shell_exec($cmd);
   echo "Le processus de calcul des statistiques à été lancé ($pid)\n\n" . $cmd;
} else {
   $errorMsg = gmdate("Y-m-d H:i:s") . " - " . __FILE__ . "\n";
   $errorMsg .= "This script should only be called with an HTTP POST!\n";
   $errorMsg .= "It got called with : " . $_SERVER['REQUEST_METHOD'] . "\n";
   die($errorMsg);
}
?>