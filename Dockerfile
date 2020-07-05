FROM lambci/lambda-base-2:build

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

RUN curl -sL https://github.com/php/php-src/archive/php-$PHP_VERSION.tar.gz | tar -xvz \
    && cd php-src-php-$PHP_VERSION \
    && ./buildconf --force \
    && ./configure --prefix=/var/task --with-openssl --with-curl --with-zlib --without-pear \
    && make install

COPY bootstrap .
RUN chmod +x bootstrap \
    && zip -r runtime.zip bin bootstrap

RUN PATH=/var/task/bin:$PATH \
    && curl -sS https://getcomposer.org/installer | php \
    && php composer.phar require --optimize-autoloader guzzlehttp/guzzle \
    && zip -r vendor.zip vendor/
