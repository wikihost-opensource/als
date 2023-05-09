[![docker image build](https://github.com/wikihost-opensource/als/actions/workflows/docker-image.yml/badge.svg)](https://github.com/wikihost-opensource/als/actions/workflows/docker-image.yml)

# ALS - Another Looking-glass Server

## Quick start
```
docker run -d --name looking-glass --restart always --network host wikihostinc/looking-glass-server
```

[DEMO](http://lg.hk1-bgp.hkg.50network.com/)

## Host Requirements
 - Can run docker (yes, only docker is required)

## Image Environment Variables
| Key                       | Example                                                                | Default                                                    | Description                                                                             |
| ------------------------- | ---------------------------------------------------------------------- | ---------------------------------------------------------- | --------------------------------------------------------------------------------------- |
| LISTEN_IP                 | 127.0.0.1                                                              | (all ip)                                                   | which IP address will be listen use                                                     |
| HTTP_PORT                 | 80                                                                     | 80                                                         | which HTTP port should use                                                              |
| SPEEDTEST_FILE_LIST       | 100MB 1GB                                                              | 1MB 10MB 100MB 1GB                                         | size of static test files, separate with space                                          |
| LOCATION                  | "this is location"                                                     | (from maxmind database, ip via PUBLIC_IPV4 or PUBLIC_IPV6) | location string                                                                         |
| MAXMIND_KEY               | THE_KEY                                                                | (empty)                                                    | about more https://dev.maxmind.com/geoip/geolite2-free-geolocation-data                 |
| PUBLIC_IPV4               | 1.1.1.1                                                                | (fetch from http://ifconfig.co)                            | The IPv4 address of the server                                                          |
| PUBLIC_IPV6               | fe80::1                                                                | (fetch from http://ifconfig.co)                            | The IPv6 address of the server                                                          |
| DISPLAY_TRAFFIC           | true                                                                   | true                                                       | Toggle the streaming traffic graph                                                      |
| ENABLE_SPEEDTEST          | true                                                                   | true                                                       | Toggle the speedtest feature                                                            |
| UTILITIES_PING            | true                                                                   | true                                                       | Toggle the ping feature                                                                 |
| UTILITIES_SPEEDTESTDOTNET | true                                                                   | true                                                       | Toggle the speedtest.net feature                                                        |
| UTILITIES_FAKESHELL       | true                                                                   | true                                                       | Toggle the HTML Shell feature                                                           |
| UTILITIES_IPERF3          | true                                                                   | true                                                       | Toggle the iperf3 feature                                                               |
| UTILITIES_IPERF3_PORT_MIN | 30000                                                                  | 30000                                                      | iperf3 listen port range - from                                                         |
| UTILITIES_IPERF3_PORT_MAX | 31000                                                                  | 31000                                                      | iperf3 listen port range - to                                                           |
| SPONSOR_MESSAGE           | "Test message" or "/tmp/als_readme.md" or "http://some_host/114514.md" | ''                                                         | Show server sponsor message (support markdown file, required mapping file to container) |


## Features
- [x] HTML 5 Speed Test
- [x] Ping - IPv4
- [x] iPerf3 server
- [x] Streaming traffic graph
- [x] Speedtest.net Client
- [x] Online shell box (limited commands)

## Thanks to
librespeed/speedtest provide core html5 speedtest code
https://github.com/librespeed/speedtest
(backend/app/components/01-fake_file_server.php)

JetBrains provide IDE license
![JetBrains Logo (Main) logo](https://resources.jetbrains.com/storage/products/company/brand/logos/jb_beam.png)

## License

Code is licensed under MIT Public License.

* If you wish to support my efforts, keep the "Powered by LookingGlass" link intact.
