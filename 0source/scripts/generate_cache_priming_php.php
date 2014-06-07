<?php

# In production environment, this data will be available when required
#    $primed_cache = require ROOT.'/cache_priming.php';
#
# It's best to only output static data in here -- arrays, strings, numbers.
#   <?php return array(...);
#

echo <<<'EOD'
<?php
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

EOD;
