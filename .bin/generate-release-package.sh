#!/bin/bash

TARGET_DIRECTORY="wirecard-woocommerce-extension"

composer install --no-dev

zip -r woocommerce-wirecard-ee.zip ${TARGET_DIRECTORY} composer.json
