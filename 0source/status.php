<?php

if (file_exists('/hhvm/var/stopping')) {
  echo '1-AM-STOPPING';
} else {
  echo '1-AM-ALIVE';
}
