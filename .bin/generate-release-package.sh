#!/bin/bash
# This script will generate release package and keep composer.json if 'test' parameter is passed

TARGET_DIRECTORY="wirecard-woocommerce-extension"

composer install --no-dev
if [[ $1 == 'test' ]]; then
    zip -r woocommerce-wirecard-ee.zip ${TARGET_DIRECTORY} composer.json -x "*tests*" -x "*Test*" -x "*codeception*"
else
    zip -r woocommerce-wirecard-ee.zip ${TARGET_DIRECTORY} -x "*tests*" -x "*Test*" -x "*codeception*"
fi

