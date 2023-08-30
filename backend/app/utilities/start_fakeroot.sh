#!/bin/bash
TIMEOUT=120
TOKEN="$1"

php81 /app/utilities/check_token.php $TOKEN
if [ $? -ne 0 ];then
    echo "Invalid token, Please restart session"
    exit 1
fi;

echo "Your session will logout in $TIMEOUT seconds"
echo ""
echo "Current session enable following command(s):"
echo " - ping"
echo " - traceroute"
echo " - mtr"
echo " - nexttrace ( https://github.com/nxtrace/Ntrace-V1 )"
echo ""
echo "Kindly notice: Be nice, don't be evil."

cd /mnt/fakeroot
env -i PATH="/mnt/fakebin" HOME="/mnt/fakeroot" PWD="/mnt/fakeroot" \
    /sbin/runuser -u r00t -- /bin/bash -c "unset LOGNAME && unset USER && export PS1=\"[root@localhost ~]# \" && export TERM=\"$TERM\" && ulimit -u 20 && /usr/bin/timeout -s 9 $TIMEOUT /bin/rbash"
