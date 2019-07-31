FROM wordpress:php7.1-apache
#example :
#docker build --build-arg WOOCOMMERCE_VERSION=2.6.14 --build-arg STOREFRONT_VERSION=2.1.8 -t woo .

RUN apt-get update && apt-get install libicu-dev -y \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl \
    && docker-php-ext-enable intl

ARG WOOCOMMERCE_VERSION=0
ARG STOREFRONT_VERSION=0

ENV WOOCOMMERCE_VERSION $WOOCOMMERCE_VERSION
ENV STOREFRONT_VERSION $STOREFRONT_VERSION
ENV GATEWAY $GATEWAY

RUN apt-get install -y --no-install-recommends unzip wget

#Get Woocommerce when --build-arg WOOCOMMERCE_VERSION is not set
RUN if [ "$WOOCOMMERCE_VERSION" = "0" ]; then \
    html=`curl --silent https://wordpress.org/plugins/woocommerce/` \
    && woofile=`echo $html | grep -Eo "https://downloads.wordpress.org/plugin/woocommerce.[0-9]*[.][0-9]*[.][0-9]*[.]zip" | head -n1 ` \
    && WOOCOMMERCE_VERSION=`echo $woofile | sed -n 's/.*\([0-9]\{1,\}[.][0-9]\{1,\}[.][0-9]\{1,\}\).zip/\1/p' ` \
    && wget $woofile -O /tmp/temp.zip \
    && cd /usr/src/wordpress/wp-content/plugins \
    && unzip /tmp/temp.zip \
    && chown -R www-data:www-data /usr/src/wordpress/wp-content/plugins/woocommerce \
    ; fi

#Get Woocommerce use --build-arg, example, --build-arg WOOCOMMERCE_VERSION=2.6.14
RUN if [ "$WOOCOMMERCE_VERSION" != "0" ]; then \
    woofile=https://downloads.wordpress.org/plugin/woocommerce.$WOOCOMMERCE_VERSION.zip \
    && wget $woofile -O /tmp/temp.zip \
    && cd /usr/src/wordpress/wp-content/plugins \
    && unzip /tmp/temp.zip \
    && chown -R www-data:www-data /usr/src/wordpress/wp-content/plugins/woocommerce \
    ; fi

#get woocommerce-wirecard-ee package into the docker image
ADD woocommerce-credit-agricole-ee.zip /tmp/temp.zip
ADD wirecard-woocommerce-extension/tests/_data/ /usr/src/wordpress/_data

RUN cd /usr/src/wordpress/wp-content/plugins \
    && unzip /tmp/temp.zip \
    && chown -R www-data:www-data /usr/src/wordpress/wp-content/plugins/wirecard-woocommerce-extension 

#Housekeep
RUN rm -rf /var/lib/apt/lists/* \
    && rm /tmp/temp.zip

# Download WordPress CLI
RUN curl -L "https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar" > /usr/bin/wp && \
    chmod +x /usr/bin/wp

VOLUME ["/var/www/html", "/usr/src/wordpress"]
