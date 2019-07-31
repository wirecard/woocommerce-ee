#!/bin/bash

WORDPRESS_DIR=wordpress
PLUGIN_DIR=wirecard-woocommerce-extension
VERSION=`jq .[0].release SHOPVERSIONS| tr -d \"`
RELEASE_DIR=${WORDPRESS_DIR}/tags/${VERSION}
BLACKLIST_FILE=.bin/svn-blacklist.txt

composer install --no-dev

mkdir ${WORDPRESS_DIR}

svn checkout https://plugins.svn.wordpress.org/wirecard-woocommerce-extension \
	--username "${WORDPRESS_USER}" --password "${WORDPRESS_PASSWORD}"  -q ${WORDPRESS_DIR}

rsync -r --exclude-from="${BLACKLIST_FILE}" ${PLUGIN_DIR} ${WORDPRESS_DIR}/trunk
mkdir ${RELEASE_DIR}
rsync -r --exclude-from="${BLACKLIST_FILE}" ${PLUGIN_DIR} ${RELEASE_DIR}

cd ${WORDPRESS_DIR}
svn add --parents tags/${VERSION}/*
svn commit -m "Add ${VERSION} release" --username "${WORDPRESS_USER}" --password "${WORDPRESS_PASSWORD}"
echo "Successfully uploaded release to Wordpress"
