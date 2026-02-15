#!/usr/bin/env bash

set -euo pipefail

echo "WARNING: This script will copy files from your local machine to the server, potentially overwriting existing files. Make sure you know what you are doing before proceeding."
read -r -p "Are you sure you want to continue? [y/N]: " response

case "$response" in
    [yY][eE][sS]|[yY])
        echo "Proceeding..."
        HOSTNAME=35.148.167.72.host.secureserver.net
        scp -r ../src/public osburn@${HOSTNAME}:/home/osburn/public_html/abet.asucapstonetools.com
        scp -r ../src/abet_private osburn@${HOSTNAME}:/home/osburn/abet_private
        ;;
    *)
        echo "Aborted. Not copying from server."
        exit 1
        ;;
esac

