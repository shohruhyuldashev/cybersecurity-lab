FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    apache2 \
    php \
    php-mysqli \
    php-xml \
    mysql-server \
    curl \
    netcat-openbsd \
    python3 \
    libcap2-bin \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite
RUN a2enmod rewrite

COPY apache3.conf /etc/apache2/sites-available/000-default.conf

# Copy application
COPY app/ /var/www/html/

# Copy MySQL init script and setup
COPY mysql/init.sql /docker-entrypoint-initdb.d/init.sql
COPY setup.sh /setup.sh
RUN chmod +x /setup.sh

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

CMD ["/entrypoint.sh"]
