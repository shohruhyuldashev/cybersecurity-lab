FROM wordpress:5.4.1-php7.2-apache

# Copy wp-cli from official wp-cli image (no internet download needed)
COPY --from=wordpress:cli-php7.2 /usr/local/bin/wp /usr/local/bin/wp

COPY wp-entrypoint.sh /wp-entrypoint.sh
RUN chmod +x /wp-entrypoint.sh

ENTRYPOINT ["/wp-entrypoint.sh"]
CMD ["apache2-foreground"]
