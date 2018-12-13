#!/bin/bash
# This script will send the notification if 'fail' parameter is passed it will
set -e # Exit with nonzero exit code if anything fails

if [[ $1 == 'fail' ]]; then
    #send slack notification
    bash .bin/send-notify.sh
fi
