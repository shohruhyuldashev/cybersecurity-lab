FROM python:3.11-slim
WORKDIR /app
COPY requirements.txt ./
RUN pip install --no-cache-dir -r requirements.txt

# Cron o'rnatish
RUN apt-get update && apt-get install -y cron curl && rm -rf /var/lib/apt/lists/*

COPY . .
ENV FLASK_ENV=development
ENV FLASK_RUN_HOST=0.0.0.0

CMD service cron start && python app.py