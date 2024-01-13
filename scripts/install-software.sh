#!/bin/bash

fix_arch(){
    ARCH=`uname -m`
    if [ "$ARCH" == "aarch64" ];then
        echo "arm64"
        return 0
    fi;

    if [ "$ARCH" == "x86_64" ];then
        echo "amd64"
        return 0
    fi;
    echo $ARCH
}

install_from_github(){
    OWNER=$1
    PROJECT=$2
    SAVE_AS=$3
    ARCH=$4
    if [ -z "$ARCH" ];then
        ARCH=`uname -m`
    fi;
    URL=$(wget -qO - https://api.github.com/repos/$1/$2/releases/latest | grep download_url | grep linux | grep $ARCH | awk -F'": "' '{print $2}' | tr '"' ' ')

    echo "Download $URL to $SAVE_AS"
    wget -O $SAVE_AS $URL
}

install_from_github "nxtrace" "Ntrace-V1" "/usr/local/bin/nexttrace" `fix_arch`
chmod +x "/usr/local/bin/nexttrace"

sh install-speedtest.sh