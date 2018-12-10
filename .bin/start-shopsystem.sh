#!/bin/bash
set -e # Exit with nonzero exit code if anything fails

docker-compose build --build-arg WOOCOMMERCE_VERSION=3.5.1 webserver
docker-compose up > /dev/null &
# wordpress running on 9090

sleep 30

#install wordpress
docker exec woo_commerce wp core install --allow-root --url="${NGROK_URL}" --admin_password="password" --title=test --admin_user=admin --admin_email=test@test.com

#activate woocommerce
docker exec woo_commerce wp plugin activate woocommerce --allow-root

#activate woocommerce-ee
docker exec woo_commerce wp plugin activate wirecard-woocommerce-extension --allow-root

#install wordpress-importer
docker exec woo_commerce wp plugin install wordpress-importer --activate --allow-root

#import sample product
docker exec woo_commerce wp import /var/www/html/wp-content/plugins/woocommerce/sample-data/sample_products.xml --allow-root --authors=create

#activate storefront theme
docker exec woo_commerce wp theme install storefront --activate --allow-root

#install shop pages
docker exec woo_commerce wp wc tool run install_pages --user=admin --allow-root

