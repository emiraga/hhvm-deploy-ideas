<?php

# In production environment, this data will be available when required
#    $primed_cache = require ROOT.'/cache_priming.php';

function get_cache_data() {
  return array (
    'val1' => array (
       30,
       60,
       140,
     ),
     'val2' => array (
       150,
     ),
  );
}

echo "<?php\nreturn ";
var_export(get_cache_data());
echo ";\n";
