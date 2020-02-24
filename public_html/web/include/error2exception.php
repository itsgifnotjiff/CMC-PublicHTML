<?php

function errHandle($errNo, $errStr, $errFile, $errLine) {
#----------------------------------------------------------------------------
# e r r H a n d l e
#----------------------------------------------------------------------------
# Arguments :
#  $errNo
#----------------------------------------------------------------------------
   $msg = "$errStr in $errFile on line $errLine";
   if ($errNo == E_NOTICE || $errNo == E_WARNING) {
      throw new ErrorException($msg, $errNo);
   } else {
      print("-- " . date("c") . " : " . $msg);
   }
}
set_error_handler('errHandle');

?>