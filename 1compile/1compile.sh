#!/bin/bash
set -e

input=${input-/home/emir/test-source}
config=${config-/home/emir/test-config/config.hdf}
output=${output-`pwd`}
use_php_cache_priming=${use_php_cache_priming-0}

installed_hhvm=`which hhvm`
if [ -z "$installed_hhvm" ]; then
  echo 'instaled hhvm not found'
  exit 1
fi

mkdir -p $output
find $output -mindepth 1 -delete

# generate build id
build_id=release-`date '+%Y-%m-%d-%H-%M'`-test-v1
echo $build_id > $output/build_id
echo Compiling $build_id from $input into $output

# config.hdf
rsync -a $config $output/config.hdf

# hhvm runtime, and hphp is the compiler
rsync -a $installed_hhvm $output/hhvm
ln -nfs hhvm $output/hphp

# /static/ folder would be served via nginx,
# we can exclude any files that are not needed.
mkdir -p $output/static
rsync --delete -a \
  --exclude .git \
  --exclude .hg \
  --exclude .svn \
  $input/ $output/static/


if [ $use_php_cache_priming -gt 0 ]; then
  rm -f $input/cache_priming.php  # this will be compiled in a separate repo
fi

# produces: hhvm.hhbc, CodeError.js, Stats.js,
# which is a bytecode repo and some stats
$output/hphp --target=hhbc --format=binary \
  --log=3 --force=1 --keep-tempdir=1 --gen-stats 1 \
  --input-dir=$input \
  --output-dir=$output \
  --exclude-dir .git \
  --exclude-dir .hg \
  --exclude-dir .svn


if [ $use_php_cache_priming -gt 0 ]; then

  # produces cache_priming.hhbc
  #
  # TODO: this compile step (which generates and compiles php cache)
  #       can be run in parallel with the one that produces hhvm.hhbc
  cache_source_tmp=`mktemp -d`
  cache_output_tmp=`mktemp -d`

  $output/hhvm \
    $input/scripts/generate_cache_priming_php.php \
      > $cache_source_tmp/cache_priming.php

  $output/hphp --target=hhbc --format=binary --program=cache_priming.hhbc \
    --log=3 --force=1 --keep-tempdir=1 \
    --input-dir=$cache_source_tmp \
    --output-dir=$cache_output_tmp
  mv $cache_output_tmp/cache_priming.hhbc $output/cache_priming.hhbc

  # Make hhvm see that "cache_priming.php" is a valid file that can be used
  touch $output/static/cache_priming.php
fi
