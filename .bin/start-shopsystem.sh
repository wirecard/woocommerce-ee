#!/bin/bash
set -e # Exit with nonzero exit code if anything fails
WOOCOMMERCE_CONTAINER_NAME=woo_commerce

docker-compose build --build-arg WOOCOMMERCE_VERSION=3.5.1 webserver
docker-compose up > /dev/null &
# wordpress running on 9090

while ! $(curl --output /dev/null --silent --head --fail "${NGROK_URL}/wp-admin/install.php"); do
    echo "Waiting for docker container to initialize"
    sleep 5
done

#install wordpress
docker exec ${WOOCOMMERCE_CONTAINER_NAME} wp core install --allow-root --url="${NGROK_URL}" --admin_password="password" --title=test --admin_user=admin --admin_email=test@test.com

#activate woocommerce
docker exec ${WOOCOMMERCE_CONTAINER_NAME} wp plugin activate woocommerce --allow-root

#activate woocommerce-ee
docker exec ${WOOCOMMERCE_CONTAINER_NAME} wp plugin activate wirecard-woocommerce-extension --allow-root

#install wordpress-importer
docker exec ${WOOCOMMERCE_CONTAINER_NAME} wp plugin install wordpress-importer --activate --allow-root

#import sample product
docker exec ${WOOCOMMERCE_CONTAINER_NAME} wp import /var/www/html/wp-content/plugins/woocommerce/sample-data/sample_products.xml --allow-root --authors=create

#activate storefront theme
docker exec ${WOOCOMMERCE_CONTAINER_NAME} wp theme install storefront --activate --allow-root

#install shop pages
docker exec ${WOOCOMMERCE_CONTAINER_NAME} wp wc tool run install_pages --user=admin --allow-root

