#!/bin/bash

# update compatible-shop-releases.txt if there was a release and we are compatible
if [[ ${COMPATIBILITY_CHECK}  == "1" ]]; then
    cp ${WOOCOMMERCE_COMPATIBILITY_FILE} ${WOOCOMMERCE_RELEASES_FILE}
    git config --global user.name "Travis CI"
    git config --global user.email "wirecard@travis-ci.org"
    git add  ${WOOCOMMERCE_RELEASES_FILE}
    git commit -m "[skip ci] Update compatible shop releases"
    git push --quiet https://${GITHUB_TOKEN}@github.com/${TRAVIS_REPO_SLUG} HEAD:master
fi
