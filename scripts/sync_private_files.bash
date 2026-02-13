#!/usr/bin/env bash

set -euo pipefail

echo "WARNING: this will overwrite your local apache config files with the ones from the server. It's okay if this is your first time running the project, but if you've made any local changes to the apache config files, make sure to back them up before proceeding."
read -r -p "Are you sure you want to continue? [y/N]: " response

case "$response" in
    [yY][eE][sS]|[yY])
        echo "Proceeding..."

        # COPY DATABASE CONFIG FILES
        scp -r osburn@72.167.148.35:/etc/apache2/config.d ../docker/apache2
        
        
        ;;
    *)
        echo "Aborted. Not copying from server."
        exit 1
        ;;
esac

