#!/bin/bash
set -e

release_name=${release_name-release-example-1}

if [ ! -d /hhvm/releases/$release_name ]; then
  echo Not found /hhvm/releases/$release_name
  exit 1
fi

for item in build_id hhvm hhvm.hhbc config.hdf; do
  if [ ! -f /hhvm/releases/$release_name/$item ]; then
    echo Not found /hhvm/releases/$release_name/$item
    exit 1
  fi
done

ln -nfs $release_name /hhvm/releases/latest
