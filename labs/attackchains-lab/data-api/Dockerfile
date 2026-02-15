FROM python:3.9-slim
WORKDIR /app
COPY requirements.txt .
RUN pip install -r requirements.txt
COPY . .
# Create a dummy database with 'admin' user having the password for the IDP
RUN python -c "import sqlite3; conn = sqlite3.connect('db.sqlite3'); conn.execute('CREATE TABLE auth_user (id INTEGER PRIMARY KEY, username TEXT, password TEXT, email TEXT)'); conn.execute(\"INSERT INTO auth_user VALUES (1, 'admin', 'SuperSecretAdminPass', 'admin@gov.local')\"); conn.execute(\"INSERT INTO auth_user VALUES (2, 'citizen_user', 'password123', 'user@gov.local')\"); conn.commit(); conn.close()"
CMD ["python", "manage.py", "runserver", "0.0.0.0:8000"]
