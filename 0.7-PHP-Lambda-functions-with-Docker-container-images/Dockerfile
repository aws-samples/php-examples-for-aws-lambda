#Lambda base image Amazon linux
FROM public.ecr.aws/lambda/provided as builder 
# Set desired PHP Version
ARG php_version="8.3.1"
ARG php_path="/var/lang/"
ARG lambda_runtime_path="/var/runtime"
ARG lambda_task_path="/var/task"

RUN dnf clean all && \
    dnf install -y autoconf \
                bison \
                bzip2-devel \
                gcc \
                gcc-c++ \
                git \
                gzip \
                libcurl-devel \
                libxml2-devel \
                make \
                openssl-devel \
                tar \
                unzip \
                zip \
                re2c \
                sqlite-devel \
                oniguruma-devel

# Download the PHP source, compile, and install both PHP and Composer
RUN curl -sL https://github.com/php/php-src/archive/php-${php_version}.tar.gz | tar -xvz && \
    cd php-src-php-${php_version} && \
    ./buildconf --force && \
    ./configure --prefix=${php_path} --with-openssl --with-curl --with-zlib --without-pear --enable-bcmath --with-bz2 --enable-mbstring --with-mysqli && \
    make -j 5 && \
    make install && \
    ${php_path}/bin/php -v && \
    curl -sS https://getcomposer.org/installer | ${php_path}/bin/php -- --install-dir=${php_path}/bin/ --filename=composer

# Prepare runtime files
COPY runtime/bootstrap ${lambda_runtime_path}
RUN chmod 0755 ${lambda_runtime_path}/bootstrap

# Install Guzzle, prepare vendor files
RUN cd /opt && \
    ${php_path}/bin/php ${php_path}/bin/composer require guzzlehttp/guzzle


COPY src/ ${lambda_task_path}

CMD [ "index.handler" ]