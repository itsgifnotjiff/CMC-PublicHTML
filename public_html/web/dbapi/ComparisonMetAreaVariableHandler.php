<?php

require_once "Handler.php";

class ComparisonMetAreaVariableHandler extends Handler {
   public function __construct($conn, $method, $args, $body) {
      parent::__construct($conn, $method, $args, $body);
      $this->attNeedsQuote = Array('comparison' => false);
   }

   public function get() {
      $argsOk = $this->checkBodyParts();
      if ($argsOk === true) {
         $query = "
            SELECT
               metarea.id AS metAreaId,
               metarea.name AS metArea,
               variable.id AS variableId,
               variable.abreviation AS variable
            FROM
               (
                  SELECT DISTINCT
                     metArea,
                     variable
                  FROM
                     metareats
                  WHERE
                     comparison = {$this->args['comparison']}
               ) AS metareats
            INNER JOIN
               metarea
            ON
               metareats.metarea = metarea.id
            INNER JOIN
               variable
            ON
               metareats.variable = variable.id
            ORDER BY
               ST_X(ST_Centroid(metarea.geometry)) DESC,
               variable;";
         return $this->baseGet($query, null, null);
      } else {
         return $argsOk;
      }
   }
}

?>