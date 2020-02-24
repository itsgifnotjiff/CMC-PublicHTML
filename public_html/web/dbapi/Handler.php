<?php

class Handler {
   //! HTTP request method
   protected $method;
   //! Associative array of the HTTP request variables
   protected $args;
   //! The connexion to the database; a VaqumDb object
   protected $conn;
   //! Body of the HTTP request, if any
   protected $body;
   //! List of the attributes and if they require quotes in SQL.  This must be set by all implementing classes.
   protected $attNeedsQuote;

   public function __construct($conn, $method, $args, $body) {
      $this->conn = $conn;
      $this->method = $method;
      $this->args = $args;
      $this->body = $body;
      $this->attNeedsQuote = null;
   }


   //! Helper for the implementation the get function of a specific Handler
   //! \param $queryBase SQL query to retrive the entire table content.  This should not contain a WHERE clause.  If sorting or filtering is based on a calculated field it must be encapsulated in a sub-query
   //! \param $displayField Field that is displayed to the application end user.  This is also the field used for filtering.
   //! \param $orderField Field or fields on which the results must be sorted
   //! \return A Response object containing all the matchings objects
   protected function baseGet($queryBase, $displayField, $orderField) {
      $query = $queryBase;
      if (array_key_exists('id', $this->args)) {
         if ( is_numeric($this->args['id']) ) {
            $query .= " WHERE id = {$this->args['id']}";
         } else {
            return new Response(400, json_encode(array("message" => '"id" doit être numérique!')));
         }
      } else {
         if (! is_null($displayField)) {
            if (array_key_exists($displayField, $this->args)) {
               $valueStr = str_replace('*', '%', $this->args[$displayField]);
               $valueStr = strtolower($valueStr);
               $valueStr = $this->conn->quote($valueStr);
               $query .= " WHERE lower({$displayField}) LIKE {$valueStr}";
            }
         }
         if (! is_null($orderField)) {
            $query .= " ORDER BY {$orderField}";
         }
      }

      $res = $this->conn->query($query);
      if ($res === false) {
         $response = new Response();
         $response->code = 400;
         $drvMsg = $this->conn->errorinfo();
         $erreur = array("message" => "Erreur lors de l'exécution de la requête!", "query" => $query, "driverMsg" => $drvMsg['2']);
         $response->body = json_encode($erreur);
         return $response;
      } else {
         $results = $res->fetchAll(PDO::FETCH_NAMED);
         if (array_key_exists('id', $this->args)) {
            $results = $results[0];
         }
         return new Response(200, json_encode($results));
      }
   }

   //! Common skelleton for HTTP PUT handlers
   //! \return A Response object containing the updated object or an error
   protected function put() {
      $response = new Response();
      // Check if we have an ID.  It's essential to perform an update
      if (! isset($this->body->id)) {
         $msg = "Il faut absolument un id pour effectuer une mise à jour!";
         $response->code = 400;
         $response->body = json_encode(array("message" => $msg));
         return $response;
      }

      $msg = '';
      $hasUpdateProp = false;
      $hasUnknownPorp = false;
      $query = "UPDATE {$this->args['table']} SET ";
      foreach ($this->body as $property => $value) {
         if ( array_key_exists($property, $this->attNeedsQuote) ) {
            if ( $property != 'id' ) {
               if ($hasUpdateProp) {
                  $query .= ', ';
               }
               $hasUpdateProp = true;
               $query .= "{$property} = ";
               if ( $this->attNeedsQuote["{$property}"] ) {
                  $query .= $this->conn->quote($value);
               } else {
                  $query .= $value;
               }
               $query .= ' ';
            }
         } else {
            $hasUnknownPorp = true;
            $msg .= "{$property} n'est pas un attribut de la table {$this->args['table']}!\n";
         }
      }
      if ( ! $hasUpdateProp ) {
         $msg .= "Il n'y a aucun attribut à mettre à jour!\n";
      }
      if ( (! $hasUpdateProp) || $hasUnknownPorp ) {
         $response->code = 400;
         $response->body = json_encode(array("message" => $msg));
         return $response;
      }

      $query .= " WHERE id = {$this->body->id} RETURNING *";

      $res = $this->conn->query($query);
      if ($res === false) {
         $driverMsg = $this->conn->errorInfo();
         $erreur = array("message" => "Erreur lors de l'insertion dans \"Projet\"!", "query" => $query, "driverMsg" => $driverMsg['2']);
         $response->code = 400;
         $response->body = json_encode($erreur);
      } else {
         $new = $res->fetchAll(PDO::FETCH_NAMED);
         $response->code = 200;
         $response->body = json_encode($new[0]);
      }
      return $response;
   }


   protected function post() {
      $argsOk = $this->checkBodyParts();
      if ($argsOk === true) {
         $query = "INSERT INTO {$this->args['table']} ( ";
         $nbAtt = count($this->attNeedsQuote);
         $i = 0;
         foreach (array_keys($this->attNeedsQuote) as $property) {
            $i++;
            $query .= $property;
            if ($i < $nbAtt) {
               $query .= ",";
            }
            $query .= " ";
         }
         $query .= ") VALUES ( ";
         $i = 0;
         foreach ($this->attNeedsQuote as $property => $value) {
            $i++;
            if ($value) {
               $query .= $this->conn->quote($this->body->$property);
            } else {
               $query .= $this->body->$property;
            }
            if ($i < $nbAtt) {
               $query .= ",";
            }
            $query .= " ";
         }
         $query .= ") RETURNING *";

         $res = $this->conn->query($query);
         $response = new Response();
         if ($res === false) {
            $driverMsg = $this->conn->errorInfo();
            $erreur = array("message" => "Erreur lors de l'insertion dans la table {$this->args['table']}!", "query" => $query, "driverMsg" => $driverMsg['2']);
            $response->code = 400;
            $response->body = json_encode($erreur);
         } else {
            $new = $res->fetchAll(PDO::FETCH_NAMED);
            $response->code = 200;
            $response->body = json_encode($new[0]);
         }
         return $response;
      } else {
         return $argsOk;
      }
   }


   //! A generic get() function.  This will usually be overloaded in sub-classes
   //! \return A Response object containing all the matchings objects
   protected function get() {
      $arguments = $this->args;
      $query = "SELECT * FROM {$arguments['table']}";
      // enlever le parametre table pour la suite
      $arguments = array_diff_key($arguments, array('table' => ''));
      // TODO verif que les params sont tous valides
      // TODO verif qu'il n'y a pas de param dupliques
      // TODO verif la validite des valeurs pour chaque param?
      if (count($arguments) > 0) {
         $query = $query . " WHERE ";
         $dernier = end(array_keys($arguments));
         foreach ($arguments as $param => $value) {
            if (is_numeric($value)) {
               $query .= $param . ' = ' . $value;
            } else {
               // Remplacer * par % pour like, ajouter les '' et mettre en minuscules
               $value_str = str_replace('*', '%', $value);
               $value_str = $this->conn->quote($value_str);
               $value_str = strtolower($value_str);
               $query .= 'lower(' . $param . ') LIKE ' . $value_str;
            }
            if ($param != $dernier) {
               $query .= ' AND ';
            }
         }
      }
      $response = new Response();

      $result = $this->conn->query($query);
      if ( $result === false ) {
         $response->code = 404;
         $drvMsg = $this->conn->errorinfo();
         $erreur = array("message" => "Erreur lors de l'exécution de la requête!", "query" => $query, "driverMsg" => $drvMsg['2']);
         $response->body = json_encode($erreur);
      } else {
         $result = $result->fetchAll(PDO::FETCH_NAMED);
         if (array_key_exists('id', $arguments)) {
            if (count($result) > 0) {
               $response->body = json_encode($result[0]);
            } else {
               $response->body = json_encode(null);
            }
         } else {
            $response->body = json_encode($result);
         }
         $response->code = 200;
      }
      return $response;
   }

   //! Check that the provided properties are present in the HTTP request (body for POST and PUT, HEADERS for GET)
   //! \param $requiredProperties Array of the required properties
   //! \return true on if all the properties are present in the body. A Response object is generated on error.
   protected function checkBodyParts() {
      $ok = true;
      $msg = '';
      $args = null;
      if ( $this->method === 'get' || $this->method === 'delete' ) {
         $args = $this->args;
      } else {
         $args = $this->body;
      }
      foreach (array_keys($this->attNeedsQuote) as $prop) {
         if ( ! array_key_exists($prop, $args) ) {
            $ok = false;
            $msg .= "L'argument essentiel {$prop} est manquant!\n";
         }
      }
      if (! $ok) {
         return (new Response(400, json_encode(array("message" => $msg))));
      } else {
         return true;
      }
   }


   //! Respond the JSON/Rest request
   //! \return A Response object representing the requested information or an error
   public function respond() {
      switch ( $this->method ) {
         case 'get' :
            if (method_exists($this, "get")) {
               return $this->get();
            }
            break;
         case 'put' :
            if (method_exists($this, "put")) {
               return $this->put();
            }
            break;
         case 'post' :
            if (method_exists($this, "post")) {
               return $this->post();
            }
            break;
         case 'delete' :
            if (method_exists($this, "del")) {
               return $this->del();
            }
            break;
      }
      // We only arrive here if the method wasn't found, so return something nice to say it failed
      return new Response(501, json_encode(array("message" => "La méthode \"{$this->method}\" n'est pas implémentée!")));
   }
}

?>