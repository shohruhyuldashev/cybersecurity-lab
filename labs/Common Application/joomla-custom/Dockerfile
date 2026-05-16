FROM joomla:3.9.0-php7.1-apache

RUN echo "deb http://archive.debian.org/debian stretch main" > /etc/apt/sources.list && \
    echo "deb http://archive.debian.org/debian-security stretch/updates main" >> /etc/apt/sources.list && \
    apt-get update && apt-get install -y --allow-unauthenticated default-mysql-client && rm -rf /var/lib/apt/lists/*

COPY configuration.php /joomla-custom-config.php
COPY joomla-entrypoint.sh /joomla-entrypoint.sh
RUN chmod +x /joomla-entrypoint.sh

ENTRYPOINT ["/joomla-entrypoint.sh"]
