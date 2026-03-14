FROM python:3.11-slim

ENV PYTHONDONTWRITEBYTECODE=1
ENV PYTHONUNBUFFERED=1
ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
    sqlite3 \
    && rm -rf /var/lib/apt/lists/*

RUN mkdir -p /app /var/www/uploads /var/log/moveit /opt/devops /opt/backups /home/www-data /tmp \
    && chown -R www-data:www-data /app /var/www/uploads /home/www-data /tmp \
    && chmod 777 /var/www/uploads

RUN apt-get update && apt-get install -y --no-install-recommends \
    php-cli \
    acl \
    curl \
    gosu \
    && rm -rf /var/lib/apt/lists/*

RUN groupadd -g 999 mysql && usermod -aG mysql www-data

RUN printf '%s\n' \
    'Ops note: metadata review target retained for classroom code review.' \
    'Backups remain instructor-managed in this simulated appliance.' \
    > /opt/devops/platform-notes.txt \
    && printf '%s\n' \
    'Immutable audit exports placeholder' \
    'Retention class: standard' \
    > /opt/backups/backup-index.txt \
    && printf '%s\n' \
    '[build] placeholder appliance log' \
    > /var/log/moveit/appliance.log \
    && echo "USER{1c7e08f95bdb0fdbe216ca6afe58fc6b}" > /home/www-data/user.txt

WORKDIR /app

COPY requirements.txt /app/requirements.txt
RUN pip install --no-cache-dir -r requirements.txt

COPY . /app

RUN chown -R www-data:www-data /app

USER www-data

EXPOSE 8080

CMD ["python", "app.py"]
