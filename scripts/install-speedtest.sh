#!/bin/sh
wget -O /tmp/speedtest.tgz https://install.speedtest.net/app/cli/ookla-speedtest-1.2.0-linux-`uname -m`.tgz
tar zxf /tmp/speedtest.tgz -C /tmp
mv /tmp/speedtest /usr/local/bin/speedtest
rm -rf /tmp/*