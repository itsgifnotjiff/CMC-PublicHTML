<?php
   require_once "dbapi/JsonRest.php";
   require_once "dbapi/Response.php";
   require_once "dbapi/HandlerFactory.php";
   require_once "include/VaqumDb.php";

   $vdb = new VaqumDb();

   $restRequest = JsonRest::processRequest();

   if ( $restRequest->getMethod() === 'get' ) {
      $hadnler = HandlerFactory::create($vdb, $restRequest->getMethod(), $restRequest->getRequestVars(), $restRequest->getData());

      JsonRest::sendResponse($hadnler->respond());
   } else {
      die("This is the read-only versions; only GETs are allowed!");
   }
?>