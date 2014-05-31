Some ideas about how to deploy HHVM in production (compile, restart, health-checks, monitoring, build-id check).

This is not complete in any way. And should be used only for ideas, not as a solution.


Overall story
=============

When compiling we will generate a build-id, which is meant to represent the build in some unique way. Build-ids can include current date, source control revision, product name, release version, i.e. relevant things that describes the release.

In addition to the standard port 80, admin server port (8001) is useful for checking the build-id, compiler-id of hhvm, collecting stats, stopping the server, etc.

`status.php` is the new endpoint (or it can be merged into an existing one) for performing health checks. We have the ability to change response of the endpoint from healthy to unhealthy with some simple operation (for example creating a file).

Releases are deployed to `/hhvm/releases/release-*` and symlink `/hhvm/releases/latest` points to the one we want to use. Webserver init script (used for restarts) will read the value of the `latest` symlink.

Nginx and hhvm will serve content from `/var/www` symlink, which points to `latest/static` directory.

One release
===========

A release/build artifacts are placed into a single directory, which contains: `hhvm` binary, bytecode repo `hhvm.hhbc`, `build_id` file, `static` directory, etc.

I am deploying `hhvm` binary together with the repo and the rest for the following reasons:

* Repo schema of `hhvm` and `hhvm.hhbc` have to match, if we upgrade only hhvm, then bytecode repo might not work as it's built for different repo schema.
* `/usr/bin/hhvm` is maintained by the operating systems package manager, which is out of our control.
* Reverting the release means that we only have to change one symlink and everything else (runtime binary and bytecode repo) will match.
* Static contents is tied together with the release, so that it makes sense to keep static contents in the same release directory as well.

Assumptions
===========

* Load balancers will make request to /status.php every 5 seconds and expect to see "1-AM-ALIVE", responses other than this will mean that server is down and load balancer should stop sending traffic to it temporarily.
* nginx is used as a webserver.
* nginx will listen on port 80 and port 8001 for admin server port.
* hhvm will use fastcgi on ports 9000 for regular traffic and 9001 for admin server traffic.
