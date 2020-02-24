<?php

class NotFoundHandler {
   //! NotFoundHandler constructor
   //! \param $requestedTarget The name of the requested virtual table
   public function __construct($requestedTarget) {
      $this->requestedTarget = $requestedTarget;
   }

   //! Respond the JSON/Rest request
   //! \return A Response object representing the requested information or an error
   public function respond() {
      return new Response(404, json_encode(array("message" => "{$this->requestedTarget} was not found!")));
   }
}

?>