List of allowed build-ids would have to live in some central location, and we would have a safe way of getting that list for all other hosts.

1. Compile the source
2. Append the new build-id to the list of allowed builds.
3. Deploy new build to produciton.
4. Set the new build-id to be the only allowed build.
5. Monitor for incorrect builds running in production, and define a procedure to resolve invalid build.
6. Don't allow resolution of build-id check to shutdown all of your webservers.

TODO
====

* Implement all operations mentioned.
