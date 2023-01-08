#!/bin/sh
sh /app/utilities/install_speedtest.sh

ln -sf /bin/bash /bin/rbash
mkdir /mnt/fakeroot
adduser -h /mnt/fakeroot -s /bin/rbash -D r00t
chown -R root:root /mnt/fakeroot

mkdir /mnt/fakebin

# fool hacker
echo -en '#/bin/sh\necho root' > /mnt/fakebin/whoami
echo -en '#/bin/sh\necho "ls: $1: No such file or directory"' > /mnt/fakebin/ls
echo -en '#/bin/sh\ntrue' > /mnt/fakebin/rm


# mod ping
echo -en '#!/bin/bash\nARG="${@: -1}"\n/bin/ping $ARG\n' > /mnt/fakebin/ping

chmod +x /mnt/fakebin/*

echo '' > /mnt/fakeroot/.bashrc
echo "alias export='echo \"Insufficient memory\" && false'" >> /mnt/fakeroot/.bashrc
echo "alias alias='echo \"Insufficient memory\" && false'" >> /mnt/fakeroot/.bashrc

# misc
ln -sf /usr/bin/free /mnt/fakebin/free
ln -sf /bin/df /mnt/fakebin/df

# word procress
ln -sf /bin/grep /mnt/fakebin/grep
ln -sf /usr/bin/awk /mnt/fakebin/awk
ln -sf /usr/bin/clear /mnt/fakebin/clear

# network tools
ln -sf /usr/sbin/mtr /mnt/fakebin/mtr
ln -sf /usr/sbin/mtr-packet /mnt/fakebin/mtr-packet
ln -sf /usr/bin/traceroute /mnt/fakebin/traceroute
