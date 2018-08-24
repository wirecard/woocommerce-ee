#!/bin/bash

TARGET_DIRECTORY="wirecard-woocommerce-extension"

composer install --no-dev

zip -r woocommerce-ee.zip ${TARGET_DIRECTORY}
