FROM lambci/lambda-base-2:build AS build

ARG BISON_VERSION=3.6
ARG PHP_VERSION=7.4.7
ARG PREFIX=/var/task/php-bin-php-$PHP_VERSION

RUN yum install -y \
    autoconf \
    gcc \
    gcc-c++ \
    libcurl-devel \
    libxml2-devel \
    openssl-devel \
    re2c \
    sqlite-devel

RUN curl -sL http://mirror.ufs.ac.za/gnu/bison/bison-$BISON_VERSION.tar.gz | tar -xvz \
    && cd bison-$BISON_VERSION \
    && ./configure --prefix=/usr \
    && make \
    && make install \
    && cd .. \
    && curl -sL https://github.com/php/php-src/archive/php-$PHP_VERSION.tar.gz | tar -xvz \
    && cd php-src-php-$PHP_VERSION \
    && ./buildconf --force \
    && ./configure --prefix=$PREFIX --with-openssl --with-curl --with-zlib --without-pear \
    && make install

COPY bootstrap $PREFIX

RUN cd $PREFIX \
    && chmod +x bootstrap \
    && zip -r /var/task/runtime.zip bin bootstrap -x bin/php-cgi bin/phpdbg \
    && curl -sS https://getcomposer.org/installer | $PREFIX/bin/php \
    && $PREFIX/bin/php composer.phar require --optimize-autoloader guzzlehttp/guzzle \
    && zip -r /var/task/vendor.zip vendor/

FROM busybox

COPY --from=build /var/task/runtime.zip .
COPY --from=build /var/task/vendor.zip .

RUN mkdir artifacts
