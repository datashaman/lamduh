FROM lambci/lambda-base-2:build

ARG BISON_VERSION=3.6
ARG PHP_VERSION=7.4.7
ARG PYTHONUNBUFFERED=1

ENV AWS_EXECUTION_ENV=AWS_Lambda_PHP_${PHP_VERSION}

RUN yum update -y \
    && yum install -y \
        autoconf \
        gcc \
        gcc-c++ \
        libcurl-devel \
        libxml2-devel \
        openssl-devel \
        re2c \
        sqlite-devel \
    && yum clean all

RUN curl -sL http://mirror.ufs.ac.za/gnu/bison/bison-$BISON_VERSION.tar.gz | tar -xvz \
    && cd bison-$BISON_VERSION \
    && ./configure --prefix=/usr \
    && make \
    && make install

RUN mkdir -p /var/task/php-$PHP_VERSION \
    && curl -sL https://github.com/php/php-src/archive/php-$PHP_VERSION.tar.gz | tar -xvz \
    && cd php-src-php-$PHP_VERSION \
    && ./buildconf --force \
    && ./configure --prefix=/usr --with-openssl --with-curl --with-zlib --without-pear \
    && make install

COPY bootstrap .
RUN chmod +x bootstrap

RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer \
    && composer require guzzlehttp/guzzle

ENTRYPOINT ["/var/task/bootstrap"]
