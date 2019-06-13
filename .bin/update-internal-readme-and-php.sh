#!/bin/bash
TARGET_DIRECTORY="wirecard-woocommerce-extension"

git config --global user.name "Travis CI"
git config --global user.email "wirecard@travis-ci.org"

echo "Updating internal readme.txt file"
composer update-versions-internal-readme

echo "Updating internal headers in .php file"
composer update-versions-internal-php

git add ${TARGET_DIRECTORY}/readme.txt ${TARGET_DIRECTORY}/woocommerce-wirecard-payment-gateway.php 
git commit -m "[skip ci] Update readme.txt and woocommerce-wirecard-payment-gateway.php with latest versions"
git push https://$GITHUB_TOKEN@github.com/$TRAVIS_REPO_SLUG HEAD:master
echo "Successfully updated readme.txt and woocommerce-wirecard-payment-gateway.php with latest versions"
