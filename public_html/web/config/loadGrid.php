<?php

   require_once "../include/VaqumDb.php";

   $conn = new VaqumDb();

   if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
      if ( is_readable( $_POST['gridFilePath'] ) ) {
         // FIXME : Sanitize input!
         $cmd = ". /users/dor/afsu/dev/.profile.d/postgres.azathoth; ";
         // The environment doesn't even contain HOME.  We need it to allow
         // libVaqum to find the configuration file
         $cmd .= "export HOME=/users/dor/afsu/dev; ";
         $cmd .= "/users/dor/afsu/air/verification/bin/gridLoader ";
         $cmd .= escapeshellarg( $_POST['gridName'] ) . " ";
         $cmd .= escapeshellarg( $_POST['gridFilePath'] ) . " ";
         $cmd .= escapeshellarg( $_POST['fieldVarName'] ) . " ";
         $cmd .= '""';
         $gridId = shell_exec($cmd);

         if ($gridId === null) {
            error_log("Erreur lors de l'exécution de gridLoader!  Commande :");
            error_log($cmd);
            die("Un problème s'est produit lors de l'exécution sur le serveur! Veillez faire un rapport de bogue s.v.p.");
         }
         $query = "UPDATE grid SET description = " . $conn->quote($_POST['gridDescription']) . " WHERE id = " . $gridId;
         $conn->query($query);
         echo "Grille chargée avec l'identificateur : $gridId";
      } else {
         die("Le fichier désigné par le chemin d'accès spécifié n'est pas lisible!");
      }
   } else {
      $errorMsg = gmdate("Y-m-d H:i:s") . " - " . __FILE__ . "\n";
      $errorMsg .= "This script should only be called with an HTTP POST!\n";
      $errorMsg .= "It got called with : " . $_SERVER['REQUEST_METHOD'] . "\n";
      die($errorMsg);
   }

?>