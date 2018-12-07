#!/bin/bash
set -e # Exit with nonzero exit code if anything fails

docker-compose build --build-arg WOOCOMMERCE_VERSION=3.5.1 webserver
docker-compose up
# wordpress running on 8080

#install wordpress
docker exec woo_commerce wp core install --allow-root --url=localhost:8080 --admin_password="password" --title=test --admin_user=admin --admin_email=test@test.com

#activate woocommerce
docker exec woo_commerce wp plugin activate woocommerce --allow-root

#activate woocommerce-ee
docker exec woo_commerce wp plugin activate --all --allow-root

