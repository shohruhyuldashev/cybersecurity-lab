from flask import Flask, request, render_template, send_from_directory
import os

UPLOAD_FOLDER = os.path.join(os.path.dirname(__file__), 'uploads')
os.makedirs(UPLOAD_FOLDER, exist_ok=True)

app = Flask(__name__)
app.config['UPLOAD_FOLDER'] = UPLOAD_FOLDER
app.config['MAX_CONTENT_LENGTH'] = 16 * 1024 * 1024


# ─── FLAGS ────────────────────────────────────────────────────────────────────
def init_flags():
    flags = {
        'uploads/.flag1.txt': 'FLAG{unr3str1ct3d_upl04d_pwn3d}',
        'flag2.txt':          'FLAG{ext3ns10n_byp4ss_w0rks}',
        '/opt/flag3.txt':     'FLAG{d0ubl3_ext3ns10n_byp4ss}',
        '/var/flag4.txt':     'FLAG{m1m3_sp00f1ng_d0n3}',
    }
    for path, flag in flags.items():
        os.makedirs(os.path.dirname(os.path.abspath(path)), exist_ok=True)
        if not os.path.exists(path):
            with open(path, 'w') as f:
                f.write(flag)

    os.makedirs('/root/secret', exist_ok=True)
    if not os.path.exists('/root/secret/flag5.txt'):
        with open('/root/secret/flag5.txt', 'w') as f:
            f.write('FLAG{p4th_tr4v3rs4l_cr0n_rce}')

# init_flags DEFINE qilingandan keyin chaqiriladi
with app.app_context():
    init_flags()


# ─── ROUTES ───────────────────────────────────────────────────────────────────
@app.route('/')
def index():
    return render_template('index.html')

@app.route('/lab1', methods=['GET', 'POST'])
def lab1():
    msg = ''
    saved = ''
    if request.method == 'POST':
        f = request.files.get('file')
        if f and f.filename:
            dest = os.path.join(app.config['UPLOAD_FOLDER'], f.filename)
            f.save(dest)
            saved = f.filename
            msg = f'File saved: {f.filename}'
        else:
            msg = 'No file selected.'
    return render_template('lab1.html', message=msg, saved=saved)

@app.route('/lab2', methods=['GET', 'POST'])
def lab2():
    msg = ''
    saved = ''
    if request.method == 'POST':
        f = request.files.get('file')
        if f and f.filename:
            allowed = {'png', 'jpg', 'jpeg', 'gif'}
            ext = f.filename.rsplit('.', 1)[-1].lower()
            if ext in allowed:
                dest = os.path.join(app.config['UPLOAD_FOLDER'], f.filename)
                f.save(dest)
                saved = f.filename
                msg = f'Image uploaded: {f.filename}'
            else:
                msg = f'Error: Extension ".{ext}" not allowed. Only images!'
        else:
            msg = 'No file selected.'
    return render_template('lab2.html', message=msg, saved=saved)

@app.route('/lab3', methods=['GET', 'POST'])
def lab3():
    msg = ''
    saved = ''
    if request.method == 'POST':
        f = request.files.get('file')
        if f and f.filename:
            parts = f.filename.split('.')
            last_ext = parts[-1].lower()
            blocked = {'php', 'py', 'sh', 'exe', 'bat', 'pl', 'rb'}
            if last_ext in blocked:
                msg = f'Error: ".{last_ext}" files are blocked!'
            else:
                dest = os.path.join(app.config['UPLOAD_FOLDER'], f.filename)
                f.save(dest)
                saved = f.filename
                msg = f'File accepted: {f.filename}'
        else:
            msg = 'No file selected.'
    return render_template('lab3.html', message=msg, saved=saved)

@app.route('/lab4', methods=['GET', 'POST'])
def lab4():
    msg = ''
    saved = ''
    if request.method == 'POST':
        f = request.files.get('file')
        if f and f.filename:
            allowed_mime = {'image/png', 'image/jpeg', 'image/gif'}
            ct = f.content_type
            if ct in allowed_mime:
                dest = os.path.join(app.config['UPLOAD_FOLDER'], f.filename)
                f.save(dest)
                saved = f.filename
                msg = f'Accepted (MIME: {ct}): {f.filename}'
            else:
                msg = f'Error: MIME "{ct}" not allowed!'
        else:
            msg = 'No file selected.'
    return render_template('lab4.html', message=msg, saved=saved)

@app.route('/lab5', methods=['GET', 'POST'])
def lab5():
    msg = ''
    saved = ''
    if request.method == 'POST':
        f = request.files.get('file')
        if f and f.filename:
            if not f.filename.lower().endswith('.txt'):
                msg = 'Xato: Faqat .txt fayllarga ruxsat!'
                return render_template('lab5.html', message=msg, saved=saved)
            dest = os.path.join(app.config['UPLOAD_FOLDER'], f.filename)
            try:
                os.makedirs(os.path.dirname(dest), exist_ok=True)
                f.save(dest)
                saved = f.filename
                msg = f'Saved to: {dest}'
            except Exception as e:
                msg = f'Error: {str(e)}'
        else:
            msg = 'No file selected.'
    return render_template('lab5.html', message=msg, saved=saved)

@app.route('/uploads/<path:filename>')
def uploaded_file(filename):
    return send_from_directory(app.config['UPLOAD_FOLDER'], filename)


if __name__ == '__main__':
    port = int(os.environ.get('PORT', '5004'))
    app.run(host='0.0.0.0', port=port, debug=True)