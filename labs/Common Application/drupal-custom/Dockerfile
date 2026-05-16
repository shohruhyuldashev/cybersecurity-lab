FROM drupal:8.5.0-apache

# Use archive repos for EOL Debian Stretch
RUN echo "deb http://archive.debian.org/debian stretch main" > /etc/apt/sources.list && \
    echo "deb http://archive.debian.org/debian-security stretch/updates main" >> /etc/apt/sources.list

# Fix man page issues (remove exclusions and create dirs)
RUN rm -f /etc/dpkg/dpkg.cfg.d/docker-clean && \
    mkdir -p /usr/share/man/man1 /usr/share/man/man7

RUN apt-get update && \
    apt-get install -y --allow-unauthenticated postgresql-client curl && \
    rm -rf /var/lib/apt/lists/*

# Download Drush 8
RUN curl -L https://github.com/drush-ops/drush/releases/download/8.4.8/drush.phar -o /usr/local/bin/drush && \
    chmod +x /usr/local/bin/drush

COPY drupal-entrypoint.sh /drupal-entrypoint.sh
RUN chmod +x /drupal-entrypoint.sh

ENTRYPOINT ["/drupal-entrypoint.sh"]
