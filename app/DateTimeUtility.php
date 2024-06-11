<?php

class DateTimeUtility {


   //
   // is_valid_date
   // We can specify format of a received date string
   // eg var_dump(validateDate('2012-02-28 12:12:12'));
   //    var_dump(validateDate('2012-02-28', 'Y-m-d'));
   //
   public static function is_valid_date($date, $format = 'Y-m-d H:i:s') {
      $d = DateTime::createFromFormat($format, $date);
      return $d && $d->format($format) == $date;
   }

   
}
        
 