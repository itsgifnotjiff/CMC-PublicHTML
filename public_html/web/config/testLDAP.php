<?php
   require_once "../include/Ldap.php";

Class TestLdap {
   public static function test($userName) {
      // $email = Ldap::getEmail($_SERVER['PHP_AUTH_USER']);
      // $home = Ldap::getServerUserHomeDir($_SERVER['PHP_AUTH_USER']);
      $email = Ldap::getEmail($userName);
      $home = Ldap::getServerUserHomeDir($userName);

      print(" This is your LDAP information for user: ");
      print($userName);
      print("</br>");
      print("   Email: ");
      print($email);
      print("</br>");
      print("   Home dir: ");
      print($home);
      print("</br></br><h3>");
      print("If you aren't getting any VAQUM automated emails, it's possible that your email is misconfigured. If so, contact the EC support Desk to change the LDAP server settings.</h3>");
   }
}

TestLdap::test($_SERVER['PHP_AUTH_USER']);
?>
