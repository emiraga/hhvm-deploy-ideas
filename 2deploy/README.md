Artifacts of the compile step should be deployed to all webserver hosts to destination:

    /hhvm/releases/release-example-1/

After which calling `release_name=release-example-1 ./2deploy.sh`
would update the main symlink `/hhvm/releases/latest`.

When we are done with this script, we can execute the script that restarts the webserver.

TODO
====

* Find a way to actually deploy build artifacts to destination hosts.
* Save a record to the database with relevant data about your deployment.
