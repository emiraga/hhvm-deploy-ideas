#!/bin/bash
set -e
readonly HHVM_VAR=/hhvm/var
readonly STATUS_FILE=$HHVM_VAR/stopping
readonly ADMIN_PASSWORD=insecure

# In order for load balancer to register that server is going down,
# we wait at least 5 seconds (and at most 30 seconds for all traffic to drain)
readonly STOP_MIN_SLEEP_TIMER=5
readonly STOP_MAX_SLEEP_TIMER=30

# After server start, wait some seconds for the health check
readonly START_TIMEOUT_STATUS=10

start() {
  if pgrep hhvm > /dev/null; then
    echo 'Found an instance of hhvm running already'
    return 1
  fi

  latest=`readlink -f /hhvm/releases/latest`
  build_id=`cat $latest/build_id`
  if [ -z "$build_id" ]; then
    echo 'Could not find the latest build_id'
    return 1
  fi

  # point /var/www to latest sources
  ln -nfs $latest/static /var/www
  # remove temporary bytecode
  rm -f /tmp/hhvm.dummy.repo.hhbc*
  # make sure we respond to status.php with 1-AM-ALIVE
  rm -f $STATUS_FILE
  # preload into the vm page cache
  cat $latest/hhvm $latest/hhvm.hhbc >/dev/null

  central_repo=/tmp/hhvm.dummy.repo.hhbc
  if [ -e $latest/cache_priming.hhbc ]; then
    central_repo=$latest/cache_priming.hhbc
  fi

  apc_prime=''
  if [ -e $latest/cache_priming.so ]; then
    apc_prime=-vServer.APC.PrimeLibrary=$latest/cache_priming.so
  fi

  # start the daemon
  $latest/hhvm \
    --mode=daemon \
    --config=$latest/config.hdf \
    --extra-header=$build_id \
    --build-id=$build_id \
    -vRepo.Commit=false \
    -vRepo.Authoritative=true \
    -vRepo.Local.Mode=r- \
    -vRepo.Local.Path=$latest/hhvm.hhbc \
    -vRepo.Central.Path=$central_repo \
    $apc_prime

  echo 'Running health checks for the new hhvm instance'
  i=0
  started=0
  while [ $i -lt $START_TIMEOUT_STATUS ]; do
    running_build=`curl -s -m 1 http://127.0.0.1:8001/build-id?auth=$ADMIN_PASSWORD`
    if [ "x$running_build" = "x$build_id" ]; then
      started=1
      break
    fi
    sleep 1
    i=`expr $i + 1`
  done

  if [ $started -eq 0 ]; then
    echo "Error: Failed to get the correct build_id after the timeout."
    echo "  Expected: $build_id"
    echo "  Found: $running_build"
    return 1
  fi

  alive=`curl -m 1 -s http://127.0.0.1:80/status.php`
  if [ "x$alive" != "x1-AM-ALIVE" ]; then
    echo "Error: Failed health status check."
    echo "  Expected: 1-AM-ALIVE"
    echo "  Found: $alive"
    return 1
  fi

  echo 'HHVM server started'
}

stop() {
  mkdir -p $HHVM_VAR
  touch $STATUS_FILE

  if pgrep hhvm > /dev/null; then
    # hhvm process is alive be sure to stop it gracefully
    i=1
    while [ $i -lt ${STOP_MAX_SLEEP_TIMER} ]; do
      load=`curl -s -m 1 http://127.0.0.1:8001/check-load?auth=$ADMIN_PASSWORD`
      echo "HHVM load: ${load}"
      if [ $i -ge ${STOP_MIN_SLEEP_TIMER} ]; then
        if [ -z "${load}" ] || [ "${load}" -lt 1 ]; then
          break
        fi
      fi
      sleep 1
      i=`expr $i + 1`
    done
    echo "Waited ${i} seconds for load to drop."
  fi
  echo "Sending stop request..."
  curl -s http://127.0.0.1:8001/stop?auth=$ADMIN_PASSWORD >/dev/null || true

  pkill hhvm || true

  i=0
  while [ $i -lt ${STOP_MAX_SLEEP_TIMER} ] && \
          curl -s -f -m 1 http://127.0.0.1:80/status.php >/dev/null; do
    sleep 1
    i=`expr $i + 1`
  done
  echo "Waited $i seconds for port 80 to stop taking traffic."

  pkill -9 hhvm || true
  rm -f $STATUS_FILE
}

status() {
  alive=`curl -m 1 -s http://127.0.0.1:80/status.php`
  if [ "x$alive" != "x1-AM-ALIVE" ]; then
    return 1
  fi

  echo OK
}

usage() {
  echo "Valid options are stop, start, restart and status."
  exit 1
}

case $1 in
  start) start
         ;;

  stop) stop
        ;;

  restart)
           stop
           start
           ;;

  status) status
         ;;

  *) usage
     ;;
esac
