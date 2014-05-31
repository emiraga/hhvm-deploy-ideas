#!/bin/bash

ADMIN_PASSWORD=insecure

alive=`curl -s -m 1 http://127.0.0.1:80/status.php`
if [ "x$alive" != "x1-AM-ALIVE" ]; then
  echo "Server is not alive, perhaps try restarting it."
  exit 2
fi

# somehow grab the list of comma-separated allowed versions
ALLOWED_IDS=2014-05-30-21-50-test-v1

# Multiple build ids could be valid.
# When we begin deploying, the new `build-id` is added to the list of builds
# and after we are done with deployment only latest build_id is present
# in the list of allowed ids.

current_build=`curl -s -m 1 http://127.0.0.1:8001/build-id?auth=$ADMIN_PASSWORD`

for allowed_id in `echo $ALLOWED_IDS | tr ",:" " "`; do
  if [ "x$allowed_id" = "x$current_build" ]; then
    echo "OK"
    exit
  fi
done

echo "Wrong build-id found running on this host"
echo "  Expected: $ALLOWED_IDS"
echo "  Found: $current_build"
echo "Raise some kind of an alarm, or maybe start to 'pull' the latest version"
echo "(pull operation *must* be rate limited to avoid killing the entire fleet)"
exit 3
