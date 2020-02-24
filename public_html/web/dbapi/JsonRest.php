<?php

//! Class representing an HTTP request
class RestRequest {
   //! HTTP request variables
   private $request_vars;
   //! The body of the request, if any
   private $body;
   //! HTTP Accept
   private $accept;
   //! HTTP method
   private $method;

   //! Default constructor
   public function __construct() {
      $this->request_vars = array();
      $this->body = null;
      $this->accept = (strpos($_SERVER['HTTP_ACCEPT'], 'json')) ? 'json' : 'xml';
      $this->method = 'get';
   }

   public function setBody($body) {
      $this->body = $body;
   }

   public function setMethod($method) {
      $this->method = $method;
   }

   public function setRequestVars($request_vars) {
      $this->request_vars = $request_vars;
   }

   public function getData() {
      return $this->body;
   }

   public function getMethod() {
      return $this->method;
   }

   public function getAccept() {
      return $this->accept;
   }

   public function getRequestVars() {
      return $this->request_vars;
   }
}


//! A class of helper functions to reply to JSON/Rest requests
class JsonRest {
   //! Decode an incoming request
   //! \return A RestRequest object
   public static function processRequest() {
      $request_method = strtolower($_SERVER['REQUEST_METHOD']);
      $return_obj = new RestRequest();

      switch ($request_method) {
         case 'get':
            break;
         case 'post':
            $body = file_get_contents('php://input');
            break;
         case 'put':
            $body = file_get_contents('php://input');
            break;
         default:
            break;
      }

      $return_obj->setRequestVars($_REQUEST);
      $return_obj->setMethod($request_method);

      if ( isset($body) ) {
         $return_obj->setBody(json_decode($body));
      }
      return $return_obj;
   }


   //! Send a response to a JSON/Rest request
   //! \param $response A Response object that contains the content that will be sent back to the client
   public static function sendResponse($response) {
      $status_header = 'HTTP/1.1 ' . $response->code . ' ' . JsonRest::getStatusCodeMessage($response->code);
      header($status_header);
      header('Content-type: application/json; charset=utf-8');
      echo $response->body;
   }

   //! Get the textual representation of a HTTP status code
   //! \param $status HTTP status code
   //! \return A string containing the textual representation of the HTTP status code
   public static function getStatusCodeMessage($status) {
      // these could be stored in a .ini file and loaded
      // via parse_ini_file()... however, this will suffice
      // for an example
      $codes = Array(
         100 => 'Continue',
         101 => 'Switching Protocols',
         200 => 'OK',
         201 => 'Created',
         202 => 'Accepted',
         203 => 'Non-Authoritative Information',
         204 => 'No Content',
         205 => 'Reset Content',
         206 => 'Partial Content',
         300 => 'Multiple Choices',
         301 => 'Moved Permanently',
         302 => 'Found',
         303 => 'See Other',
         304 => 'Not Modified',
         305 => 'Use Proxy',
         306 => '(Unused)',
         307 => 'Temporary Redirect',
         400 => 'Bad Request',
         401 => 'Unauthorized',
         402 => 'Payment Required',
         403 => 'Forbidden',
         404 => 'Not Found',
         405 => 'Method Not Allowed',
         406 => 'Not Acceptable',
         407 => 'Proxy Authentication Required',
         408 => 'Request Timeout',
         409 => 'Conflict',
         410 => 'Gone',
         411 => 'Length Required',
         412 => 'Precondition Failed',
         413 => 'Request Entity Too Large',
         414 => 'Request-URI Too Long',
         415 => 'Unsupported Media Type',
         416 => 'Requested Range Not Satisfiable',
         417 => 'Expectation Failed',
         500 => 'Internal Server Error',
         501 => 'Not Implemented',
         502 => 'Bad Gateway',
         503 => 'Service Unavailable',
         504 => 'Gateway Timeout',
         505 => 'HTTP Version Not Supported'
      );

      return (isset($codes[$status])) ? $codes[$status] : '';
   }
}

?>