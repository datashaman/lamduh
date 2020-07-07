FROM lambci/lambda-base-2:build AS build

ARG BISON_VERSION=3.6
ARG PHP_VERSION=7.4.7
ARG PREFIX=/opt

ENV PATH=/var/lang/bin:$PATH \
    LD_LIBRARY_PATH=/var/lang/lib:$LD_LIBRARY_PATH \
    AWS_EXECUTION_ENV=AWS_Lambda_PHP_$PHP_VERSION \
    PKG_CONFIG_PATH=/var/lang/lib/pkgconfig:/usr/lib64/pkgconfig:/usr/share/pkgconfig

RUN yum install -y \
    libcurl-devel \
    libxml2-devel \
    openssl-devel \
    re2c \
    sqlite-devel

RUN curl -sL http://mirror.ufs.ac.za/gnu/bison/bison-$BISON_VERSION.tar.gz | tar -xvz \
    && cd bison-$BISON_VERSION \
    && ./configure \
    && make install

RUN curl -sL https://github.com/php/php-src/archive/php-$PHP_VERSION.tar.gz | tar -xvz \
    && cd php-src-php-$PHP_VERSION \
    && ./buildconf --force \
    && ./configure --prefix=$PREFIX --with-openssl --with-curl --with-zlib --without-pear \
    && make install

COPY bootstrap $PREFIX

ENV PATH=$PREFIX/bin:$PATH

RUN cd $PREFIX \
    && chmod +x bootstrap \
    && curl -sS https://getcomposer.org/installer | php \
    && php composer.phar require --optimize-autoloader guzzlehttp/guzzle
