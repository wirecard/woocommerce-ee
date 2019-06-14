#!/bin/bash

WORDPRESS_DIR=wordpress
PLUGIN_DIR=wirecard-woocommerce-extension
VERSION=`jq .[0].release SHOPVERSIONS| tr -d \"`
RELEASE_DIR=${WORDPRESS_DIR}/${PLUGIN_DIR}/tags/${VERSION}

composer install --no-dev

mkdir ${WORDPRESS_DIR}

svn checkout https://plugins.svn.wordpress.org/wirecard-woocommerce-extension \
	--username "${WORDPRESS_USER}" --password "${WORDPRESS_PASSWORD}"  -q ${WORDPRESS_DIR}
cp -r ${PLUGIN_DIR}/{assets,classes,languages,vendor,readme.txt,woocommerce-wirecard-payment-gateway.php} \
	${WORDPRESS_DIR}/${PLUGIN_DIR}/trunk

mkdir ${RELEASE_DIR}
cp -r ${PLUGIN_DIR}/{assets,classes,languages, vendor,readme.txt,woocommerce-wirecard-payment-gateway.php} ${RELEASE_DIR}

cd ${WORDPRESS_DIR}
svn add {PLUGIN_DIR}/tags/${VERSION}/*
svn add {PLUGIN_DIR}/trunk/*
#svn commit -m "Add ${VERSION} release" --username "${WORDPRESS_USER}" --password "${WORDPRESS_PASSWORD}"
echo "Successfully uploaded release to Wordpress"
