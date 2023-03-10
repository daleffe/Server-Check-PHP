<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Server check helper
 */

 if (!function_exists('servercheck')) {
     function servercheck($filters = '') {
         define('SERVERCHECK_AS_LIB',TRUE);

         $server_check = require_once APPPATH . 'third_party/servercheck.php';

         if (!empty($filters)) {
             if (is_array($filters)) {
                 $results = array();

                 foreach ($filters as $filter) if (isset($server_check[$filter])) $results[$filter] = $server_check[$filter];

                 return $results;
             } else if (isset($server_check[$filters])) {
                 return $server_check[$filters];
             }
         }

         return $server_check;
     }
 }

/* End of file */