from flask import Flask, request, render_template
import os
import urllib.parse

app = Flask(__name__)

# ─── FLAGS ────────────────────────────────────────────────────────────────────
def init_flags():
    flags = {
        '/etc/flag1.txt':  'FLAG{b4s1c_lf1_p4th_trav3rs4l}',
        '/var/flag2.txt':  'FLAG{d0ubl3_url_3nc0d3_byp4ss}',
        '/opt/flag3.txt':  'FLAG{c00k13_lf1_byp4ss}',
        '/logs/flag4.txt': 'FLAG{l0g_p01s0n1ng_rce}',
        '/etc/flag5':      'FLAG{p4th_trunc4t10n_pwn3d}',  # kengaytmasiz!
    }
    for path, flag in flags.items():
        os.makedirs(os.path.dirname(path), exist_ok=True)
        if not os.path.exists(path):
            with open(path, 'w') as f:
                f.write(flag)

    os.makedirs('/logs', exist_ok=True)
    if not os.path.exists('/logs/access.log'):
        with open('/logs/access.log', 'w') as f:
            f.write('[INFO] Log server started.\n')

    # Lab5 uchun web root
    os.makedirs('/var/www/html', exist_ok=True)
    if not os.path.exists('/var/www/html/index.html'):
        with open('/var/www/html/index.html', 'w') as f:
            f.write('<h1>Welcome to the web server!</h1>')
    if not os.path.exists('/var/www/html/about.html'):
        with open('/var/www/html/about.html', 'w') as f:
            f.write('<h1>About page</h1>')

with app.app_context():
    init_flags()


@app.route('/')
def index():
    return render_template('index.html')


# ─── LAB 1: ....// bypass ─────────────────────────────────────────────────────
@app.route('/lab1')
def lab1():
    page = request.args.get('page', 'home')
    content = ''
    error = ''
    page_clean = page.replace('../', '')
    try:
        with open(f'templates/pages/{page_clean}.txt', 'r') as f:
            content = f.read()
    except Exception as e:
        error = str(e)
    return render_template('lab1.html', page=page, page_clean=page_clean, content=content, error=error)


# ─── LAB 2: Double URL Encode bypass ──────────────────────────────────────────
@app.route('/lab2')
def lab2():
    page = request.args.get('page', 'home')
    content = ''
    error = ''
    decoded_once = urllib.parse.unquote(page)
    if '../' in decoded_once:
        error = 'Xavfli belgilar aniqlandi: ../'
        return render_template('lab2.html', page=page, decoded=decoded_once, content=content, error=error)
    filepath = f'templates/pages/{decoded_once}.txt'
    try:
        with open(filepath, 'r') as f:
            content = f.read()
    except Exception as e:
        error = str(e)
    return render_template('lab2.html', page=page, decoded=decoded_once, content=content, error=error, filepath=filepath)


# ─── LAB 3: Cookie-based LFI ──────────────────────────────────────────────────
@app.route('/lab3')
def lab3():
    content = ''
    error = ''
    lang = request.cookies.get('lang', 'en')
    blocked = ['<', '>', '|', ';', '&']
    if any(c in lang for c in blocked):
        error = 'Xavfli belgilar aniqlandi!'
        return render_template('lab3.html', lang=lang, content=content, error=error)
    filepath = f'templates/pages/lang_{lang}.txt'
    try:
        with open(filepath, 'r') as f:
            content = f.read()
    except Exception as e:
        error = str(e)
    return render_template('lab3.html', lang=lang, filepath=filepath, content=content, error=error)


# ─── LAB 4: Log Poisoning ─────────────────────────────────────────────────────
@app.route('/lab4')
def lab4():
    page = request.args.get('page', 'home')
    content = ''
    error = ''
    user_agent = request.headers.get('User-Agent', '')
    ip = request.remote_addr
    log_entry = f'[{os.popen("date").read().strip()}] {ip} GET /lab4?page={page} | UA: {user_agent}\n'
    with open('/logs/access.log', 'a') as f:
        f.write(log_entry)
    try:
        with open(page, 'r') as f:
            content = f.read()
    except Exception as e:
        error = str(e)
    return render_template('lab4.html', page=page, content=content, error=error)


# ─── LAB 5: Path Truncation Bypass (HARD) ────────────────────────────────────
@app.route('/lab5')
def lab5():
    import os
    import base64

    page = request.args.get('page', 'index')
    content = ''
    error = ''

    BASE_DIR = '/var/www/html/'   # 14 belgi
    MAX_LEN  = 50                 # truncation chegarasi

    # 1️⃣ Faqat ../ strip (intentional zaiflik)
    page_clean = page.replace('../', '')

    # 2️⃣ BASE_DIR bilan birlashtiramiz
    combined = BASE_DIR + page_clean

    # 3️⃣ Path truncation logikasi
    if len(combined) + len('.html') > MAX_LEN:
        full_path = combined[:MAX_LEN]   # .html kesiladi
        truncated = True
    else:
        full_path = combined + '.html'
        truncated = False

    # 4️⃣ Agar truncate natijasida oxiri "/" bo‘lsa — olib tashlaymiz
    #    Aks holda file bo‘lsa Not a directory chiqadi
    if full_path.endswith('/'):
        full_path = full_path.rstrip('/')

    # 5️⃣ Path normalization (OS resolve simulate)
    full_path = os.path.normpath(full_path)

    # 6️⃣ Faylni o‘qishga urinamiz
    try:
        with open(full_path, 'rb') as f:
            raw = f.read()

        try:
            content = raw.decode('utf-8', errors='replace')
        except Exception:
            content = '[binary] base64:\n' + base64.b64encode(raw).decode()

    except Exception as e:
        error = str(e)

    return render_template(
        'lab5.html',
        page=page,
        page_clean=page_clean,
        combined=combined,
        full_path=full_path,
        truncated=truncated,
        max_len=MAX_LEN,
        base_dir=BASE_DIR,
        content=content,
        error=error
    )

if __name__ == '__main__':
    port = int(os.environ.get('PORT', '5005'))
    app.run(host='0.0.0.0', port=port, debug=True)