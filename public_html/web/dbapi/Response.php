<?php

//! Response object for JSON/Rest requests
class Response {
   //! Response HTTP status code
   public $code;
   //! Response body in JSON
   public $body;

   //! Default constructor
   //! \param $code HTTP status code of the response.  Defaults to null
   //! \param $body Response body in JSON.  Defaults to null
   public function __construct($code = null, $body = null) {
      $this->code = $code;
      $this->body = $body;
   }
}

?>