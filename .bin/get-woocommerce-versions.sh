#!/bin/bash

curl -H "Authorization: token ${GITHUB_TOKEN}" https://api.github.com/repos/woocommerce/woocommerce/releases | jq -r '.[] | .tag_name' | egrep -v [a-zA-Z] | head -3 > tmp.txt

sort -nr tmp.txt > ${WOOCOMMERCE_RELEASES_FILE}


if [[ $(git diff HEAD ${WOOCOMMERCE_RELEASES_FILE}) != '' ]]; then
	git config --global user.name "Travis CI"
	git config --global user.email "wirecard@travis-ci.org"
	git add  ${WOOCOMMERCE_RELEASES_FILE}
	git commit -m "${SHOP_SYSTEM_UPDATE_COMMIT}"
	git push --quiet https://${GITHUB_TOKEN}@github.com/${TRAVIS_REPO_SLUG} HEAD:master
fi
