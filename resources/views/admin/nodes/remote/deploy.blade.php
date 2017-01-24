#!/bin/bash
####
 # Pterodactyl - Panel
 # Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>
 #
 # Permission is hereby granted, free of charge, to any person obtaining a copy
 # of this software and associated documentation files (the "Software"), to deal
 # in the Software without restriction, including without limitation the rights
 # to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 # copies of the Software, and to permit persons to whom the Software is
 # furnished to do so, subject to the following conditions:
 #
 # The above copyright notice and this permission notice shall be included in all
 # copies or substantial portions of the Software.
 #
 # THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 # IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 # FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 # AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 # LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 # OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 # SOFTWARE.
####
set +e
export DEBIAN_FRONTEND=noninteractive

INSTALL_DIR="/srv/daemon"
DATA_DIR="{{ $node->daemonBase }}"
CURRENT_SYSTEM_KERNEL="$(uname -r)"
DL_VERSION="0.0.1"

command_exists() {
    command -v "$@" > /dev/null 2>&1
}

error_message() {
    echo -e "\e[1m\e[97m\e[41m$1\e[0m"
    exit 1
}

warning_message() {
    echo -e "\e[43m\e[30m$1\e[0m"
}

success_message() {
    echo -e "\e[32m$1\e[0m"
}

running_command() {
    echo -e " ;; \e[47m\e[30m$1\e[0m"
}

for i in "$@"
do
    case $i in
        -d|--directory)
            INSTALL_DIR="$2"
            ;;
        -a|--datadir)
            DATA_DIR="$2"
            ;;
        -g|--git)
            USE_GIT=true
            ;;
        -u|--unstable)
            USE_UNSTABLE=true
            USE_GIT=true
            ;;
        -v|--version)
            DL_VERSION="$2"
            ;;
        -h|--help)
            echo "./installer [opts]"
            echo "     -d | --directory    The directory to install the daemon into. (default: /srv/daemon)"
            echo "     -a | --datadir      The directory that daemon users will be stored in. (default: /srv/daemon-data)"
            echo "     -g | --git          Use this flag to download the daemon using a git clone. (default: false)"
            echo "     -u | --unstable     Install unstable version of the daemon, automatically uses --git flag. (default: false)"
            echo "     -v | --version      The version of the daemon to download."
            exit
            ;;
    esac
shift
done

warning_message "This program will automatically configure your system to run the Pterodactyl Daemon."
warning_message "      - Install Location: $INSTALL_DIR"
warning_message "      -    Data Location: $DATA_DIR"
warning_message "This script will continue in 10 seconds. Press CRTL+C to exit now."
sleep 10

# Super basic system detection
if command_exists apt-get; then
    INSTALL_CMD="apt-get -y"
elif command_exists yum; then
    INSTALL_CMD="yum -y"
else
    error_message "No supported repository manager was found."
fi

if ! command_exists curl; then
    warning_message "No file retrieval method found, installing curl now..."
    running_command "$INSTALL_CMD -y install curl"
    $INSTALL_CMD -y install curl
    if [ "$?" -ne "0" ]; then
        error_message "Unable to install curl and no other method was found for retrieving files."
    fi
fi

# Determine if the kernel is high enough version.
if command_exists awk; then
    PROCESSED_KERNEL_VERSION=$(awk -F. '{print $1$2}' <<< $CURRENT_SYSTEM_KERNEL)
elif command_exists cut; then
    PROCESSED_KERNEL_VERSION=$(cut -d. -f1-2 --output-delimiter='' <<< $CURRENT_SYSTEM_KERNEL)
else
    error_message "You seem to be missing some core tools that this script needs: awk (or) cut"
fi

if [ "$PROCESSED_KERNEL_VERSION" -lt "310" ]; then
    error_message "Your kernel version must be at least 3.10 or higher for the daemon to work. You are using $CURRENT_SYSTEM_KERNEL"
fi

check_cgroups() {
    # Check CGroups
    CGROUP_DIRECTORY_LISTING="$(awk '/[, ](cpu|cpuacct|cpuset|devices|freezer|memory)[, ]/ && $3 == "cgroup" { print $2 }' /proc/mounts | head -n1)"
    if [ ! -z $CGROUP_DIRECTORY_LISTING -a -d $CGROUP_DIRECTORY_LISTING ]; then
        CGROUP_DIRECTORY="$(dirname $CGROUP_DIRECTORY_LISTING 2>&1)"
        if [ -d "$CGROUP_DIRECTORY/cpu" -a -d "$CGROUP_DIRECTORY/cpuacct" -a -d "$CGROUP_DIRECTORY/cpuset" -a -d "$CGROUP_DIRECTORY/devices" -a -d "$CGROUP_DIRECTORY/freezer" -a -d "$CGROUP_DIRECTORY/memory" ]; then
            success_message "cgroups enabled and are valid on this machine."
        else
            error_message "You appear to be missing some important cgroups on this machine."
        fi
    else
        if [ ! -e "/proc/cgroups" ]; then
            error_message "This kernel does not appear to support cgroups! Please see https://gist.github.com/DaneEveritt/0f071f481b4d3fa637d4 for more information."
        elif [ ! -d "/sys/fs/cgroup" ]; then
            error_message "This kernel does not appear to support cgroups! Please see https://gist.github.com/DaneEveritt/0f071f481b4d3fa637d4 for more information."
        fi

        if [ ! -f "/tmp/mount_cgroup.sh" ]; then
            # Try to enable cgroups
            warning_message "Attempting to enable cgroups on this machine..."
            running_command "curl -L https://raw.githubusercontent.com/tianon/cgroupfs-mount/master/cgroupfs-mount > /tmp/mount_cgroup.sh"
            curl -L https://raw.githubusercontent.com/tianon/cgroupfs-mount/master/cgroupfs-mount > /tmp/mount_cgroup.sh

            running_command "chmod +x /tmp/mount_cgroup.sh"
            chmod +x /tmp/mount_cgroup.sh

            running_command "bash /tmp/mount_cgroup.sh"
            bash /tmp/mount_cgroup.sh
            check_cgroups
        else
            rm -rf /tmp/mount_cgroup.sh > /dev/null 2>&1
            error_message "Failed to enable cgroups on this machine."
        fi
    fi
}

# Check those groups.
check_cgroups

# Lets install the dependencies.
$INSTALL_CMD install linux-image-extra-$CURRENT_SYSTEM_KERNEL
if [ "$?" -ne "0" ]; then
    warning_message "You appear to have a non-generic kernel meaning we could not install extra kernel tools."
    warning_message "We will continue to install, but some docker enhancements might not work as expected."
    warning_message "Continuing in 10 seconds, press CTRL+C to cancel this script."
    sleep 10
fi

success_message "Installing Docker..."
running_command "curl -L https://get.docker.com/ | sh"
curl -L https://get.docker.com/ | sh
if [ "$?" -ne "0" ]; then
    error_message "Unable to install docker, an error occured!"
fi;

success_message "Installing NodeJS 5.x..."
running_command "curl -L https://deb.nodesource.com/setup_5.x | sudo -E bash -"
curl -L https://deb.nodesource.com/setup_5.x | sudo -E bash -
if [ "$?" -ne "0" ]; then
    error_message "Unable to configure NodeJS, an error occured!"
fi;

running_command "$INSTALL_CMD install tar nodejs"
$INSTALL_CMD install tar nodejs
if [ "$?" -ne "0" ]; then
    error_message "Unable to install NodeJS or Tar, an error occured!"
fi;

running_command "mkdir -p $INSTALL_DIR $DATA_DIR"
mkdir -p $INSTALL_DIR $DATA_DIR
cd $INSTALL_DIR

if [ -z $USE_UNSTABLE -a -z $USE_GIT ]; then
    CLEANUP_PROGRAMS="nodejs docker-engine"

    running_command "curl -sI https://github.com/Pterodactyl/Daemon/archive/$DL_VERSION.tar.gz | head -n1 | cut -d$' ' -f2"
    GITHUB_STATUS="$(curl -sI https://github.com/Pterodactyl/Daemon/archive/$DL_VERSION.tar.gz | head -n1 | cut -d$' ' -f2)"
    if [ $GITHUB_STATUS -ne "200" ]; then
        $INSTALL_CMD remove $CLEANUP_PROGRAMS 2>&1
        error_message "Github returned a non-200 response code ($GITHUB_STATUS)"
    fi

    running_command "curl -L \"https://github.com/Pterodactyl/Daemon/archive/$DL_VERSION.tar.gz\" > daemon.tar.gz"
    curl -L "https://github.com/Pterodactyl/Daemon/archive/$DL_VERSION.tar.gz" > daemon.tar.gz

    running_command "tar --strip-components=1 -xzvf daemon.tar.gz"
    tar --strip-components=1 -xzvf daemon.tar.gz 2>&1
    if [ "$?" -ne "0" ]; then
        $INSTALL_CMD remove $CLEANUP_PROGRAMS 2>&1
        cd ~ && rm -rf $INSTALL_DIR 2>&1
        error_message "Unable to install the daemon due to an error while attempting to unpack files."
    fi
elif [ $USE_GIT ]; then
    CLEANUP_PROGRAMS="nodejs docker-engine git"
    running_command "$INSTALL_CMD install git"
    $INSTALL_CMD install git

    running_command "git clone https://github.com/Pterodactyl/Daemon.git ."
    git clone https://github.com/Pterodactyl/Daemon.git .
    if [ -z $USE_UNSTABLE ]; then
        running_command "git checkout tags/$DL_VERSION"
        git checkout tags/$DL_VERSION
    fi
    if [ "$?" -ne "0" ]; then
        $INSTALL_CMD remove $CLEANUP_PROGRAMS 2>&1
        cd ~ && rm -rf $INSTALL_DIR 2>&1
        error_message "Unable to install the daemon due to an error while attempting to clone files to the server."
    fi
else
    error_message "Could not match an install method!"
fi

running_command "npm install --production"
npm install --production
if [ "$?" -ne "0" ]; then
    $INSTALL_CMD remove $CLEANUP_PROGRAMS 2>&1
    cd ~ && rm -rf $INSTALL_DIR 2>&1
    error_message "Unable to install the daemon due to an error that occured while running npm install."
fi

running_command "docker run -d --name ptdl-sftp -p 2022:22 -v $DATA_DIR:/sftp-root -v $INSTALL_DIR/config/credentials:/creds quay.io/pterodactyl/scrappy"
docker run -d --name ptdl-sftp -p 2022:22 -v $DATA_DIR:/sftp-root -v $INSTALL_DIR/config/credentials:/creds quay.io/pterodactyl/scrappy
if [ "$?" -ne "0" ]; then
    $INSTALL_CMD remove $CLEANUP_PROGRAMS 2>&1
    cd ~ && rm -rf $INSTALL_DIR 2>&1
    error_message "Unable to install the daemon due to an error while creating a SFTP container."
fi

echo '{
    "web": {
        "listen": {{ $node->daemonListen }},
        "ssl": {
            "enabled": {{ $node->sceheme === 'https' ? 'true' : 'false' }},
            "certificate": "/etc/letsencrypt/live/{{ $node->fqdn }}/fullchain.pem",
            "key": "/etc/letsencrypt/live/{{ $node->fqdn }}/privkey.pem"
        }
    },
    "docker": {
        "socket": "/var/run/docker.sock"
    },
    "sftp": {
        "path": "{{ $node->daemonBase }}",
        "port": {{ $node->daemonSFTP }},
        "container": "ptdl-sftp"
    },
    "logger": {
        "path": "logs/",
        "src": false,
        "level": "info",
        "period": "1d",
        "count": 3
    },
    "remote": {
        "download": "{{ route('remote.download') }}",
        "installed": "{{ route('remote.install') }}"
    },
    "uploads": {
        "maximumSize": 100000000
    },
    "keys": [
        "{{ $node->daemonSecret }}"
    ]
}' > config/core.json
if [ "$?" -ne "0" ]; then
    $INSTALL_CMD remove $CLEANUP_PROGRAMS
    cd ~ && rm -rf $INSTALL_DIR 2>&1
    error_message "An error occured while attempting to save the JSON file."
fi

success_message "Congratulations, the daemon is now installed."
exit
