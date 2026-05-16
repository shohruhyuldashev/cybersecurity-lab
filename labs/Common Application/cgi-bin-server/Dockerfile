FROM ubuntu:25.04

RUN apt-get update && apt-get install -y apache2 && apt-get clean

# Enable CGI
RUN a2enmod cgi

COPY status.sh /usr/lib/cgi-bin/status.sh
RUN chmod +x /usr/lib/cgi-bin/status.sh

COPY flag.txt /flag.txt

CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]
