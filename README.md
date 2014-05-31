Some ideas about how to deploy HHVM in production (compile, restart, health-checks, monitoring, build-id check).

This is not complete in any way. And should be used only for ideas, not as a solution.

* Load balancers will make request to /status.php every 5 seconds and expect to see "1-AM-ALIVE", responses other than this will mean that server is down and load balancer should stop sending traffic to it temporarily.
* nginx is used as a webserver.
* nginx will listen on port 80 and port 8001 for admin server port.
* hhvm will use fastcgi on ports 9000 for regular traffic and 9001 for admin server traffic.
