#!/bin/bash
set -e

input=${input-/home/emir/test-source}
config=${config-/home/emir/test-config/config.hdf}
output=${output-`pwd`}

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

# hhvm and hphp, runtime and compiler
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

# produces: hhvm.hhbc, CodeError.js, Stats.js,
# which is a bytecode repo and some stats
$output/hphp --target=hhbc --format=binary \
  --log=3 --force=1 --keep-tempdir=1 --gen-stats 1 \
  --input-dir=$input \
  -o $output \
  --exclude-dir .git \
  --exclude-dir .hg \
  --exclude-dir .svn
