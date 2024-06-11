<?php

class Model {


   // 
   // Child classes specify database columns permitted in building our WHERE clauses
   //
   protected $permitted_search_cols = ['id'];

   protected function set_permitted_search_cols($cols) {
      if(!is_array($cols)) return;
      $this->permitted_search_cols = array_unique(array_merge($this->permitted_search_cols,$cols));
   }

   protected function is_permitted_search_col($col) {
      return in_array($col, $this->permitted_search_cols);
   }

   
}
        
 