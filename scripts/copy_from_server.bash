#!/usr/bin/env bash

set -euo pipefail

echo "WARNING: This script will copy files from the server to your local machine, potentially overwriting existing files. Make sure you have committed any local changes before proceeding."
read -r -p "Are you sure you want to continue? [y/N]: " response

case "$response" in
    [yY][eE][sS]|[yY])
        echo "Proceeding..."
        scp -i .ssh/abet -r osburn@72.167.148.35:/home/osburn/public_html/abet.asucapstonetools.com ..
        scp -i .ssh/abet -r osburn@72.167.148.35:/home/osburn/abet_private ..
        ;;
    *)
        echo "Aborted. Not copying from server."
        exit 1
        ;;
esac

