<?php

echo "Just an example endpoint that reads the cache: <br>";

if (file_exists('cache_priming/data1.php')) {
  // Useful when code is compiled with: use_php_cache_priming=1
  $data1 = require 'cache_priming/data1.php';
  echo var_export($data1, true);
} else {
  // Useful when code is compiled with: use_so_cache_priming=1
  echo "APC val1 = ".var_export(apc_fetch('val1'), true)."<br/>";
  echo "APC cache1 = ".var_export(apc_fetch('cache1'), true)."<br/>";
}
