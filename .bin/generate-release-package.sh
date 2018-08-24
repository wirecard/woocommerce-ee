#!/bin/bash

TARGET_DIRECTORY="upload"

composer install --no-dev
rm -rf $TARGET_DIRECTORY
echo "copying files to target directory ${TARGET_DIRECTORY}"
mkdir $TARGET_DIRECTORY
cp -r wirecard-woocommerce-extension ${TARGET_DIRECTORY}/

zip -r wocommerce-wirecard-ee.zip ${TARGET_DIRECTORY} install.xml
