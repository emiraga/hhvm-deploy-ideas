Duplication
===========

Normally, hhvm deployments rely on PHP files (in source form) as well as bytecode being available at the destination. This is kind of weird, since files are checked to exist at the destination, but their contents are read from the bytecode (in compiled form).

There is a way to avoid this duplication, but in order to keep things simple, I am not going to use "file cache", even though I would like to. Which means that .php files are deployed both in source form and in the compiled form.

Output artifacts
================

Static content will be served via nginx, and all files are rsynced into `static` directory. Static should have .php files as well, since hhvm will look at the disk to verify that they exist.

Runtime binary is `hhvm`. Compiler is `hphp`, it's sufficient to simply point the symlink with this name name and hhvm binary will behave as a compiler.

`hhvm.hhbc` is bytecode repo, where all sources are compiled.

`build_id` contains some string that identifies the build in some unique way, this is useful to check that our server is running the right code after the deployment, and periodically thoughout the lifetime.

`config.hdf` is configuration file for hhvm that we just copy from the source.

Optional output artifacts
=========================

`cache_priming.hhbc` is useful when you have some cache data that should be
compiled and deployed in parallel with the rest of your code.
Secondary repo is used by hhvm in addition with the primary code repo.
`use_php_cache_priming=1` env flag enables this.

`cache_priming.so`, not yet implemented.

TODO
====

* Save artifacts of this build.
* Save compile log.
* Save the record of this compile to a database
  (build-id, hhvm compiler-id, source code branch, source code revision, date, pusher name, etc.)
