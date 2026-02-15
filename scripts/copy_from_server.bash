#!/usr/bin/env bash

set -euo pipefail

echo "WARNING: This script will copy files from the server to your local machine, potentially overwriting existing files. Make sure you have committed any local changes before proceeding."
read -r -p "Are you sure you want to continue? [y/N]: " response

case "$response" in
    [yY][eE][sS]|[yY])
        echo "Proceeding..."

        HOSTNAME=35.148.167.72.host.secureserver.net

        # COPY DEPLOYED FILES FROM SERVER TO LOCAL MACHINE
        scp -r osburn@${HOSTNAME}:/home/osburn/public_html/abet.asucapstonetools.com/* ../src/public/server_clone

        scp -r osburn@${HOSTNAME}:/home/osburn/abet_private/* ../src/abet_private/server_clone
        
        # COPY DATABASE CONFIG FILES
        # scp -r osburn@${HOSTNAME}:/etc/apache2/config.d/* ../docker/app/apache2/server_clone
        
        
        ;;
    *)
        echo "Aborted. Not copying from server."
        exit 1
        ;;
esac

