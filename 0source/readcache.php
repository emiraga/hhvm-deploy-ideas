<?php

echo "Just an example endpoint that reads the cache: <br>";
$primedcache = require 'cache_priming.php';
echo var_export($primedcache, true);
