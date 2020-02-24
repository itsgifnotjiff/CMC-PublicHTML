<?php

require_once "Handler.php";

class ExecutableHandler extends Handler {
   public function __construct($conn, $method, $args, $body) {
      parent::__construct($conn, $method, $args, $body);
      $this->attNeedsQuote = Array('model' => false, 'svnpath' => true, 'svnrevision' => false, 'buildhost' => true, 'comment' => true);
   }

   protected function get() {
      $query = "
         SELECT
            *
         FROM
            (
               SELECT
                  executable.id,
                  model.name AS model,
                  executable.svnpath,
                  executable.svnrevision,
                  executable.buildhost,
                  executable.comment,
                  executable.svnpath || '@' || executable.svnrevision AS display
               FROM
                  executable
               INNER JOIN
                  model
               ON
                  executable.model = model.id
            ) AS executable";
      return $this->baseGet($query, 'display', 'id');
   }
}

?>