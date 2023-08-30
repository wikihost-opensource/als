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

install_as_fakebin(){
    SOURCE=$1
    NEED_SUDO=$2
    NAME=$(basename $1)

    mv $SOURCE /usr/local/bin/$NAME
    chmod +x /usr/local/bin/$NAME
    if [ -z "$NEED_SUDO" ] || [ "$NEED_SUDO" != "true" ] ;then
        ln -sf /usr/local/bin/$NAME /mnt/fakebin/$NAME
        exit 0
    fi;

    echo "r00t ALL = NOPASSWD: /usr/local/bin/$NAME" >> /etc/sudoers.d/app
    echo -en "#!/bin/sh\n/usr/bin/sudo /usr/local/bin/$NAME \$@\n" > /mnt/fakebin/$NAME
    chmod +x /mnt/fakebin/$NAME
}

install_from_github "nxtrace" "Ntrace-V1" "/tmp/nexttrace" `fix_arch`
install_as_fakebin "/tmp/nexttrace" "true"
