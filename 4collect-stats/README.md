Admin port of HHVM provides lots of useful stats to be collected. For example:

    curl -s 127.0.0.1:8001/check-load?auth=insecure

gives a number of requests hhvm instance is currently processing.
List of these commands is available just by opening the main page on admin port.

For simplicity, I am reproducing only part that list in here:

    /                 a list of all commands
    /build-id:        returns build id that's passed in from command line
    /instance-id:     instance id that's passed in from command line
    /compiler-id:     returns the compiler id that built this app
    /repo-schema:     return the repo schema id used by this app
    /check-load:      how many threads are actively handling requests
    /check-queued:    how many http requests are queued waiting to be
                      handled
    /check-health:    return json containing basic load/usage stats
    /check-ev:        how many http requests are active by libevent
    /check-pl-load:   how many pagelet threads are actively handling
                      requests
    /check-pl-queued: how many pagelet requests are queued waiting to
                      be handled
    /check-mem:       report memory quick statistics in log file
    /check-sql:       report SQL table statistics
    /check-sat        how many satellite threads are actively handling
                      requests and queued waiting to be handled
    /status.xml:      show server status in XML
    /status.json:     show server status in JSON
    /status.html:     show server status in HTML
    /stats-on:        main switch: enable server stats
    /stats-off:       main switch: disable server stats
    /stats-clear:     clear all server stats
    /stats-web:       turn on/off server page stats (CPU and gen time)
    /stats-mem:       turn on/off memory statistics
    /stats-mcc:       turn on/off memcache statistics
    /stats-sql:       turn on/off SQL statistics
    /stats-mutex:     turn on/off mutex statistics
        sampling      optional, default 1000
    /stats.keys:      list all available keys
        from          optional, <timestamp>, or <-n> second ago
        to            optional, <timestamp>, or <-n> second ago
    /stats.xml:       show server stats in XML
        from          optional, <timestamp>, or <-n> second ago
        to            optional, <timestamp>, or <-n> second ago
        agg           optional, aggragation: *, url, code
        keys          optional, <key>,<key/hit>,<key/sec>,<:regex:>
        url           optional, only stats of this page or URL
        code          optional, only stats of pages returning this code
    /stats.json:      show server stats in JSON
        (same as /stats.xml)
    /stats.kvp:       show server stats in key-value pairs
        (same as /stats.xml)
    /stats.html:      show server stats in HTML
        (same as /stats.xml)
    /const-ss:        get const_map_size
    /static-strings:  get number of static strings
    /dump-apc:        dump all current value in APC to /tmp/apc_dump
    /dump-apc-meta:   dump meta infomration for all objects in APC to
                      /tmp/apc_dump_meta
    /dump-const:      dump all constant value in constant map to
                      /tmp/const_map_dump
    /dump-file-repo:  dump file repository to /tmp/file_repo_dump
    /pcre-cache-size: get pcre cache map size
    /start-stacktrace-profiler: set enable_stacktrace_profiler to true
    /vm-tcspace:      show space used by translator caches
    /vm-tcaddr:       show addresses of translation cache sections
    /vm-dump-tc:      dump translation cache to /tmp/tc_dump_a and
                      /tmp/tc_dump_astub
    /vm-namedentities:show size of the NamedEntityTable
    /jemalloc-stats:  get internal jemalloc stats
    /jemalloc-stats-print:
                      get comprehensive jemalloc stats in
                      human-readable form
    /jemalloc-prof-activate:
                      activate heap profiling
    /jemalloc-prof-deactivate:
                      deactivate heap profiling
    /jemalloc-prof-dump:
                      dump heap profile
        file          optional, filesystem path


TODO
====

Depending on your data collection software, write scripts that would perform this collection.
