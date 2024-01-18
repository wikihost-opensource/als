[![docker image build](https://github.com/wikihost-opensource/als/actions/workflows/docker-image.yml/badge.svg)](https://github.com/wikihost-opensource/als/actions/workflows/docker-image.yml)


Language: English | [简体中文](README_zh_CN.md)

# ALS - Another Looking-glass Server

## Quick start
```
docker run -d --name looking-glass --restart always --network host wikihostinc/looking-glass-server
```

[DEMO](http://lg.hk1-bgp.hkg.50network.com/)

If you don't want to use Docker , you can use the [compiled server](https://github.com/wikihost-opensource/als/releases)

## Host Requirements
 - RAM: 32MB or more

## How to change config
```
# you need pass -e KEY=VALUE to docker command
# you can find the KEY below the [Image Environment Variables]
# for example, change the listen port to 8080
docker run -d \
    --name looking-glass \
    -e HTTP_PORT=8080 \
    --restart always \
    --network host \
    wikihostinc/looking-glass-server
``` 

## Environment variable table
| Key                       | Example                                                                | Default                                                    | Description                                                                             |
| ------------------------- | ---------------------------------------------------------------------- | ---------------------------------------------------------- | --------------------------------------------------------------------------------------- |
| LISTEN_IP                 | 127.0.0.1                                                              | (all ip)                                                   | which IP address will be listen use                                                     |
| HTTP_PORT                 | 80                                                                     | 80                                                         | which HTTP port should use                                                              |
| SPEEDTEST_FILE_LIST       | 100MB 1GB                                                              | 1MB 10MB 100MB 1GB                                         | size of static test files, separate with space                                          |
| LOCATION                  | "this is location"                                                     | (request from http://ipapi.co) | location string                                                                         |
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
- [x] Ping - IPv4 / IPv6
- [x] iPerf3 server
- [x] Streaming traffic graph
- [x] Speedtest.net Client
- [x] Online shell box (limited commands)
- [x] [NextTrace](https://github.com/nxtrace/NTrace-core) Support
## Thanks to
https://github.com/librespeed/speedtest

https://www.jetbrains.com/

## License

Code is licensed under MIT Public License.

* If you wish to support my efforts, keep the "Powered by WIKIHOST Opensource - ALS" link intact.

## Star History

[![Star History Chart](https://api.star-history.com/svg?repos=wikihost-opensource/als&type=Date)](https://star-history.com/#wikihost-opensource/als&Date)
