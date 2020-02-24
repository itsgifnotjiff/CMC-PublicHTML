<?php

   if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
      require_once "../include/Ldap.php";

      $implementation = "default";
      if ( is_int($_POST['implementationId']) ) {
         $implementation = $_POST['implementationId'];
      }

      $cmd = "nohup " . getcwd() . "/loadForecast.bash ";
      $cmd .= escapeshellarg( $_POST['serieId'] ) . " ";
      $cmd .= escapeshellarg( $_POST['loadSerieName'] ) . " ";
      $cmd .= escapeshellarg( $implementation ) . " ";
      $cmd .= escapeshellarg( $_POST['surfaceLevel'] ) . " ";
      $cmd .= escapeshellarg( $_POST['filePattern'] ) . " ";
      $cmd .= escapeshellarg( Ldap::getEmail($_SERVER['PHP_AUTH_USER']) ) . " ";
      $cmd .= '> /dev/null 2>&1 & echo $!';
      $pid = shell_exec($cmd);

      echo "Le processus de chargement à été lancé ($pid)";
   } else {
      die("Erreur Fatale :\n\nCe processus modifie l'état du système et devrait donc uniquement être invoqué avec la méthode HTTP POST!");
   }

?>