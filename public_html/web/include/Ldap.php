<?php

Class Ldap {
   public static function getEmail($userName) {
      // ugly hack to avoid 3 weeks to change the LDAP server erroneous data 
      // TODO make a service ticket to change the LDAP
      if ($userName == "afsusjp") {
         return "sijun.peng3@canada.ca";
      }

      $email = "";

      $ldapHost = "ldaps://ldap.cmc.ec.gc.ca";

      $ldapConn = ldap_connect($ldapHost) or die("Could not connect to {$ldapHost}");

      ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
      $ldapbind = ldap_bind($ldapConn) or  die("Could not bind to {$ldapHost}");;

      $result = ldap_search($ldapConn, "ou=people,dc=ec,dc=gc,dc=ca", "uid={$userName}", array("mail"));
      $info = ldap_get_entries($ldapConn, $result);
      if ( $info['count'] == 1 ) {
         $email = $info['0']['mail']['0'];
      } else {
         die("Unexpected number of results to LDAP query : {$info['count']}");
      }

      ldap_close($ldapConn);

      return $email;
   }


   public static function getServerUserHomeDir($userName) {
      $ldapHost = "ldaps://ldap.cmc.ec.gc.ca";
      $baseDn = "ou=people,dc=ec,dc=gc,dc=ca";

      $ldapConn = ldap_connect($ldapHost) or die("Could not connect to {$ldapHost}");

      ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
      $ldapbind = ldap_bind($ldapConn) or  die("Could not bind to {$ldapHost}");;

      $result = ldap_search($ldapConn, $baseDn, "uid={$userName}", array("homeDirectory"));
      $info = ldap_get_entries($ldapConn, $result);
      if ( $info['count'] == 1 ) {
         $homeDirectory = $info['0']['homedirectory']['0'];
      } else {
         die("Unexpected number of results to LDAP query : {$info['count']}");
      }

      ldap_close($ldapConn);

      return $homeDirectory;
   }
}
?>