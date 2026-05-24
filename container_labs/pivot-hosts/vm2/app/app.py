#!/usr/bin/env python3
"""
InternalAPI v1.4 - Internal API Service
"""
import os, sqlite3, subprocess, json, hashlib, secrets, pwd
from flask import Flask, request, jsonify, session, render_template, redirect, url_for

app = Flask(__name__)
app.secret_key = secrets.token_hex(32)
DB = '/var/db/api.db'

def get_db():
    db = sqlite3.connect(DB)
    db.row_factory = sqlite3.Row
    return db

def init_db():
    db = get_db()
    db.executescript("""
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        email TEXT,
        role TEXT DEFAULT 'user',
        api_token TEXT,
        session_token TEXT
    );
    CREATE TABLE IF NOT EXISTS audit_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        action TEXT,
        ts DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    """)
    admin_tok = "admin-sess-" + secrets.token_hex(8)
    user_tok  = "user-sess-"  + secrets.token_hex(8)
    db.execute("""INSERT OR IGNORE INTO users (id,username,password,email,role,api_token,session_token)
        VALUES (1,'admin',?,'admin@internal.corp','admin',?,?)""",
        (hashlib.sha256(b'Adm1n$ecure2024!').hexdigest(),
         secrets.token_hex(16), admin_tok))
    db.execute("""INSERT OR IGNORE INTO users (id,username,password,email,role,api_token,session_token)
        VALUES (2,'alice',?,'alice@internal.corp','user',?,?)""",
        (hashlib.sha256(b'Alice@pass123').hexdigest(),
         secrets.token_hex(16), user_tok))
    db.execute("""INSERT OR IGNORE INTO users (id,username,password,email,role,api_token,session_token)
        VALUES (3,'bob',?,'bob@internal.corp','user',?,?)""",
        (hashlib.sha256(b'BobPass!99').hexdigest(),
         secrets.token_hex(16), "user-sess-" + secrets.token_hex(8)))
    db.commit()
    db.close()

def current_user():
    uid = session.get('user_id')
    if not uid: return None
    db = get_db()
    u = db.execute('SELECT * FROM users WHERE id=?', (uid,)).fetchone()
    db.close()
    return u

def require_auth(f):
    from functools import wraps
    @wraps(f)
    def decorated(*args, **kwargs):
        if not current_user():
            return jsonify({'error': 'Unauthorized', 'code': 401}), 401
        return f(*args, **kwargs)
    return decorated

def require_admin(f):
    from functools import wraps
    @wraps(f)
    def decorated(*args, **kwargs):
        u = current_user()
        if not u:
            return jsonify({'error': 'Unauthorized', 'code': 401}), 401
        if u['role'] != 'admin':
            return jsonify({'error': 'Forbidden', 'code': 403}), 403
        return f(*args, **kwargs)
    return decorated

# ─── Static files ─────────────────────────────────────────────────────────────
@app.route('/robots.txt')
def robots():
    content = (
        "User-agent: *\n"
        "Disallow: /api/admin\n"
        "Disallow: /dashboard\n"
        "\n"
        "# Note to self: temp staff account below, remove before prod\n"
        "# alice : Alice@pass123\n"
    )
    return content, 200, {'Content-Type': 'text/plain'}

# ─── Frontend ─────────────────────────────────────────────────────────────────
@app.route('/')
def index():
    return render_template('index.html', user=current_user())

@app.route('/dashboard')
def dashboard():
    u = current_user()
    if not u: return redirect('/login')
    return render_template('dashboard.html', user=dict(u))

@app.route('/login', methods=['GET','POST'])
def login():
    if request.method == 'POST':
        data = request.get_json() or request.form
        username = data.get('username','')
        password = data.get('password','')
        ph = hashlib.sha256(password.encode()).hexdigest()
        db = get_db()
        u = db.execute('SELECT * FROM users WHERE username=? AND password=?',(username,ph)).fetchone()
        if u:
            session['user_id'] = u['id']
            session['username'] = u['username']
            session['role']     = u['role']
            resp = jsonify({'success': True, 'user_id': u['id'],
                            'username': u['username'], 'role': u['role'],
                            'session_token': u['session_token']})
            resp.set_cookie('session_token', u['session_token'], httponly=False)
            db.close()
            return resp
        db.close()
        return jsonify({'error': 'Invalid credentials'}), 401
    return render_template('login.html')

@app.route('/logout')
def logout():
    session.clear()
    return redirect('/')

# ─── API: Info ────────────────────────────────────────────────────────────────
@app.route('/api/info', methods=['GET'])
def api_info():
    return jsonify({
        'service': 'InternalAPI',
        'version': '1.4.0',
        'status': 'running'
    })

# ─── API: Users ───────────────────────────────────────────────────────────────
@app.route('/api/users', methods=['GET'])
@require_auth
def list_users():
    db = get_db()
    users = db.execute('SELECT id,username,email,role FROM users').fetchall()
    db.close()
    return jsonify({'users': [dict(u) for u in users]})

@app.route('/api/user/<int:user_id>', methods=['GET'])
@require_auth
def get_user(user_id):
    db = get_db()
    u = db.execute('SELECT id,username,email,role,session_token,api_token FROM users WHERE id=?',
                   (user_id,)).fetchone()
    db.close()
    if not u:
        return jsonify({'error': 'User not found'}), 404
    return jsonify({'user': dict(u)})

@app.route('/api/user/<int:user_id>/profile', methods=['GET'])
@require_auth
def get_profile(user_id):
    db = get_db()
    u = db.execute('SELECT id,username,email,role FROM users WHERE id=?',(user_id,)).fetchone()
    db.close()
    if not u:
        return jsonify({'error': 'Not found'}), 404
    return jsonify(dict(u))

# ─── API: Session ─────────────────────────────────────────────────────────────
@app.route('/api/session', methods=['GET'])
def session_info():
    """Discovery endpoint — reveals available sub-endpoints."""
    return jsonify({
        'api': 'session',
        'endpoints': {
            'upgrade': {
                'method': 'POST',
                'path': '/api/session/upgrade'
            }
        }
    })

@app.route('/api/session/upgrade', methods=['POST'])
def upgrade_session():
    token = request.cookies.get('session_token') or (request.get_json() or {}).get('session_token','')
    if not token:
        return jsonify({'error': 'No token provided'}), 400
    db = get_db()
    u = db.execute('SELECT * FROM users WHERE session_token=?',(token,)).fetchone()
    if not u:
        db.close()
        return jsonify({'error': 'Invalid token'}), 401
    session['user_id'] = u['id']
    session['username'] = u['username']
    session['role']     = u['role']
    db.close()
    return jsonify({'success': True, 'logged_in_as': u['username'], 'role': u['role']})

# ─── API: Admin ───────────────────────────────────────────────────────────────
@app.route('/api/admin', methods=['GET'])
@require_admin
def admin_panel():
    db = get_db()
    users = db.execute('SELECT id,username,email,role,session_token FROM users').fetchall()
    logs  = db.execute('SELECT * FROM audit_log ORDER BY ts DESC LIMIT 20').fetchall()
    db.close()
    return jsonify({
        'admin_panel': True,
        'total_users': len(users),
        'users': [dict(u) for u in users],
        'audit_log': [dict(l) for l in logs],
        'endpoints': {
            'download': {
                'method': 'POST',
                'path': '/api/admin/download',
                'body': {'package': '<package-name>'}
            }
        }
    })

@app.route('/api/admin/download', methods=['POST'])
@require_admin
def admin_download():
    data = request.get_json() or {}
    package = data.get('package', '')
    if not package:
        return jsonify({'error': 'package parameter required'}), 400
    try:
        # Drop privileges to www-data for subprocess execution
        www = pwd.getpwnam('www-data')
        def drop_privs():
            os.setgid(www.pw_gid)
            os.setuid(www.pw_uid)

        cmd = f'pip install {package}'
        result = subprocess.run(
            cmd, shell=True,
            capture_output=True, text=True, timeout=30,
            preexec_fn=drop_privs
        )
        return jsonify({
            'stdout': result.stdout,
            'stderr': result.stderr,
            'returncode': result.returncode
        })
    except subprocess.TimeoutExpired:
        return jsonify({'error': 'Command timed out'}), 408
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/api/admin/users', methods=['GET'])
@require_admin
def admin_users():
    db = get_db()
    users = db.execute('SELECT * FROM users').fetchall()
    db.close()
    return jsonify({'users': [dict(u) for u in users]})

if __name__ == '__main__':
    init_db()
    app.run(host='0.0.0.0', port=5000, debug=False)
