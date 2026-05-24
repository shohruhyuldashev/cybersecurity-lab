FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    python3-venv \
    gcc \
    curl \
    netcat-openbsd \
    sqlite3 \
    && rm -rf /var/lib/apt/lists/*

# Install Python deps
COPY app/requirements.txt /app/requirements.txt
RUN pip3 install -r /app/requirements.txt --break-system-packages

# Copy app
COPY app/ /app/

# Copy C source and compile
COPY src/admin_tool.c /home/justin/admin_check.c
RUN gcc -o /home/justin/admin_check /home/justin/admin_check.c -static && \
    strip --strip-debug /home/justin/admin_check

# Setup script
COPY setup.sh /setup.sh
RUN chmod +x /setup.sh && /setup.sh

EXPOSE 5000

CMD ["python3", "/app/app.py"]
