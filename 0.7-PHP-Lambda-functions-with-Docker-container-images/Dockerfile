#Lambda base image Amazon linux
FROM public.ecr.aws/lambda/provided as builder 
# Set desired PHP Version
ARG php_version="7.3.6"
RUN yum clean all && \
    yum install -y autoconf \
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
                zip

# Download the PHP source, compile, and install both PHP and Composer
RUN curl -sL https://github.com/php/php-src/archive/php-${php_version}.tar.gz | tar -xvz && \
    cd php-src-php-${php_version} && \
    ./buildconf --force && \
    ./configure --prefix=/opt/php-7-bin/ --with-openssl --with-curl --with-zlib --without-pear --enable-bcmath --with-bz2 --enable-mbstring --with-mysqli && \
    make -j 5 && \
    make install && \
    /opt/php-7-bin/bin/php -v && \
    curl -sS https://getcomposer.org/installer | /opt/php-7-bin/bin/php -- --install-dir=/opt/php-7-bin/bin/ --filename=composer

# Prepare runtime files
# RUN mkdir -p /lambda-php-runtime/bin && \
    # cp /opt/php-7-bin/bin/php /lambda-php-runtime/bin/php
COPY runtime/bootstrap /lambda-php-runtime/
RUN chmod 0755 /lambda-php-runtime/bootstrap

# Install Guzzle, prepare vendor files
RUN mkdir /lambda-php-vendor && \
    cd /lambda-php-vendor && \
    /opt/php-7-bin/bin/php /opt/php-7-bin/bin/composer require guzzlehttp/guzzle


###### Create runtime image ######
FROM public.ecr.aws/lambda/provided as runtime
# Layer 1: PHP Binaries
COPY --from=builder /opt/php-7-bin /var/lang
# Layer 2: Runtime Interface Client
COPY --from=builder /lambda-php-runtime /var/runtime
# Layer 3: Vendor
COPY --from=builder /lambda-php-vendor/vendor /opt/vendor

COPY src/ /var/task/

CMD [ "index" ]
