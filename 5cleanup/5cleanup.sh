#!/bin/bash

remove_except_latest() {
  num=$1
  shift

  if [ -e $1 ]; then
    /bin/ls -1rtd $@ | /usr/bin/head --lines=-$num \
      | /usr/bin/xargs --no-run-if-empty /bin/rm -rf
  fi
}

remove_except_latest 5 /hhvm/releases/release-*
