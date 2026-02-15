FROM python:3.9-slim
WORKDIR /app
COPY requirements.txt .
RUN apt-get update && apt-get install -y iputils-ping curl && rm -rf /var/lib/apt/lists/*
RUN pip install -r requirements.txt
COPY . .
CMD ["python", "app.py"]
