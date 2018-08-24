#!/bin/bash

TARGET_DIRECTORY="wirecard-woocommerce-extension"

composer install --no-dev

zip -r wocommerce-ee.zip ${TARGET_DIRECTORY}
