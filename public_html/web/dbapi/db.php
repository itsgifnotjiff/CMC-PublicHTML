<?php
   require_once "JsonRest.php";
   require_once "Response.php";
   require_once "HandlerFactory.php";
   require_once "../include/VaqumDb.php";

   $vdb = new VaqumDb();

   $restRequest = JsonRest::processRequest();

   $handler = HandlerFactory::create($vdb, $restRequest->getMethod(), $restRequest->getRequestVars(), $restRequest->getData());

   JsonRest::sendResponse($handler->respond());
?>