<?php

function get_all_cache_data() {
  $data1 = array (
    // Available at runtime via:
    //    $data1 = require ROOT.'/cache_priming/data1.php';
    'val1' => array (
      30,
      60,
      140,
    ),
    'val2' => array (
      150,
    ),
  );

  $data2 = array (
    // Also, available via:
    //    $data2 = require ROOT.'/cache_priming/data2.php';
    'cache1' => "test",
  );

  return array (
    'data1' => $data1,
    'data2' => $data2,
  );
}

function main() {
  foreach (get_all_cache_data() as $name => $data) {
    file_put_contents($name.'.php',
      sprintf("<?php\nreturn %s;\n", var_export($data, true)));
  }
}

main();
