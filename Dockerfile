FROM wordpress:php7.1-apache
#example :
#docker build --build-arg WOOCOMMERCE_VERSION=2.6.14 --build-arg STOREFRONT_VERSION=2.1.8 -t woo .

ARG WOOCOMMERCE_VERSION=0
ARG STOREFRONT_VERSION=0

ENV WOOCOMMERCE_VERSION $WOOCOMMERCE_VERSION
ENV STOREFRONT_VERSION $STOREFRONT_VERSION

RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip wget

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
ADD woocommerce-wirecard-ee.zip /tmp/temp.zip

RUN cd /usr/src/wordpress/wp-content/plugins \
    && unzip /tmp/temp.zip \
    && chown -R www-data:www-data /usr/src/wordpress/wp-content/plugins/wirecard-woocommerce-extension \
    && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php -r "if (hash_file('sha384', 'composer-setup.php') === '93b54496392c062774670ac18b134c3b3a95e5a5e5c8f1a9f115f203b75bf9a129d5daa8ba6a13e2cc8a1da0806388a8') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');" \
    && php composer.phar install

#Housekeep
RUN rm -rf /var/lib/apt/lists/* \
    && rm /tmp/temp.zip

# Download WordPress CLI
RUN curl -L "https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar" > /usr/bin/wp && \
    chmod +x /usr/bin/wp

VOLUME ["/var/www/html", "/usr/src/wordpress"]