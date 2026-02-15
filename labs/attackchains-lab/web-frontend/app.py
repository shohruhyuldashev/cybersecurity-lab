import os
import subprocess
from flask import Flask, request, render_template, redirect, session, url_for, flash
import requests

app = Flask(__name__)
app.secret_key = 'weak_secret_key'
app.config['UPLOAD_FOLDER'] = 'static/uploads'
os.makedirs(app.config['UPLOAD_FOLDER'], exist_ok=True)

# CREDENTIALS (Synced with MinIO leak)
VALID_USERNAME = "employee"
VALID_PASSWORD = "SecureGov2024!"

@app.route('/')
def home():
    if not session.get('logged_in'):
        return redirect(url_for('login'))
    return render_template('index.html', user=session.get('user', 'Employee'))

@app.route('/login', methods=['GET', 'POST'])
def login():
    error = None
    if request.method == 'POST':
        username = request.form.get('username')
        password = request.form.get('password')
        if username == VALID_USERNAME and password == VALID_PASSWORD:
            session['logged_in'] = True
            session['user'] = username
            return redirect(url_for('home'))
        else:
            error = "Invalid credentials. Please contact IT Security."
    return render_template('login.html', error=error)

@app.route('/logout')
def logout():
    session.clear()
    return redirect(url_for('login'))

@app.route('/tools/ping', methods=['GET', 'POST'])
def ping_service():
    if not session.get('logged_in'):
        return redirect(url_for('login'))
    
    output = ""
    if request.method == 'POST':
        target = request.form.get('target', '')
        
        # VULNERABILITY: Middle Difficulty Filter
        # "Security" feature: Block obvious separators and spaces
        blacklist = [';', '&', ' ', '|', '`', '$(']
        
        # Bypass hint: use newlines (%0a) or tabs, or other tricks.
        # But wait, we need to allow *some* injection for the lab to work.
        # Medium difficulty: Block space ' ' and semicolon ';'.
        # Allow '&', '|' but block spaces forcing use of ${IFS} or tabs.
        
        # Let's go with blocking spaces and semicolons to force creativity.
        # And let's say we block "ls" keyword just to be annoying.
        
        if any(char in target for char in [';', ' ', '&&', '||']):
            output = "SECURITY ALERT: Malicious characters detected."
        else:
            try:
                # Still vulnerable, but harder to exploit
                output = subprocess.check_output(f"ping -c 1 {target}", shell=True, stderr=subprocess.STDOUT).decode()
            except subprocess.CalledProcessError as e:
                output = e.output.decode()
            except Exception as e:
                output = str(e)


            
    return render_template('ping.html', output=output)

@app.route('/upload', methods=['GET', 'POST'])
def upload_file():
    if not session.get('logged_in'):
        return redirect(url_for('login'))

    message = ""
    if request.method == 'POST':
        if 'file' not in request.files:
            return 'No file part'
        file = request.files['file']
        if file.filename == '':
            return 'No selected file'
        
        # VULNERABILITY: Unrestricted File Upload
        filepath = os.path.join(app.config['UPLOAD_FOLDER'], file.filename)
        file.save(filepath)
        message = f"File uploaded to {filepath}. Accessible at /static/uploads/{file.filename}"

    return render_template('upload.html', message=message)

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=8080, debug=True)
