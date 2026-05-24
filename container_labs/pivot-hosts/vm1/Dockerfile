FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive
ENV APACHE_RUN_USER=www-data
ENV APACHE_RUN_GROUP=www-data

RUN apt-get update && apt-get install -y \
    apache2 \
    php \
    php-sqlite3 \
    php-xml \
    php-dev \
    expect \
    sqlite3 \
    cron \
    wget \
    curl \
    build-essential \
    netcat-openbsd \
    && rm -rf /var/lib/apt/lists/*

# Configure PHP for LFI / wrapper vulnerabilities
RUN PHP_VER=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;") && \
    echo "allow_url_include = On" >> /etc/php/$PHP_VER/apache2/php.ini && \
    echo "allow_url_fopen = On"   >> /etc/php/$PHP_VER/apache2/php.ini && \
    echo "allow_url_include = On" >> /etc/php/$PHP_VER/cli/php.ini && \
    echo "allow_url_fopen = On"   >> /etc/php/$PHP_VER/cli/php.ini

# Enable Apache modules
RUN a2enmod rewrite && a2enmod headers

# Copy Apache config
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Copy web application
COPY app/ /var/www/html/

# Copy setup script and backup script
COPY setup.sh /setup.sh
RUN chmod +x /setup.sh
COPY cron/backup.sh /usr/local/bin/backup.sh

# Run setup
RUN /setup.sh

# Entrypoint
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80
CMD ["/entrypoint.sh"]
