from flask import Flask, request, render_template, render_template_string
import subprocess
import os
import pickle
import base64

app = Flask(__name__)


@app.route('/')
def index():
    return render_template('index.html')


# Lab 1 — Basic command injection (ping utility)
@app.route('/lab1', methods=['GET', 'POST'])
def lab1():
    output = ''
    if request.method == 'POST':
        host = request.form.get('host', '')
        # Intentionally vulnerable: unsanitized shell command
        try:
            result = subprocess.run(
                f'ping -c 2 {host}',
                shell=True,
                capture_output=True,
                text=True,
                timeout=10
            )
            output = result.stdout + result.stderr
        except subprocess.TimeoutExpired:
            output = 'Command timed out.'
        except Exception as e:
            output = str(e)
    return render_template('lab1_cmdi.html', output=output)


# Lab 2 — Eval injection (calculator)
@app.route('/lab2', methods=['GET', 'POST'])
def lab2():
    result = ''
    if request.method == 'POST':
        expr = request.form.get('expr', '')
        # Intentionally vulnerable: eval() on user input
        try:
            result = str(eval(expr))
        except Exception as e:
            result = f'Error: {e}'
    return render_template('lab2_eval.html', result=result)


# Lab 3 — Server-Side Template Injection (SSTI)
@app.route('/lab3', methods=['GET', 'POST'])
def lab3():
    output = ''
    if request.method == 'POST':
        name = request.form.get('name', '')
        # Intentionally vulnerable: user input rendered as template
        try:
            template = f'Hello, {name}!'
            output = render_template_string(template)
        except Exception as e:
            output = f'Error: {e}'
    return render_template('lab3_ssti.html', output=output)


# Lab 4 — Insecure deserialization (pickle)
@app.route('/lab4', methods=['GET', 'POST'])
def lab4():
    output = ''
    if request.method == 'POST':
        data = request.form.get('data', '')
        # Intentionally vulnerable: pickle.loads on user-supplied data
        try:
            decoded = base64.b64decode(data)
            obj = pickle.loads(decoded)
            output = f'Deserialized: {obj}'
        except Exception as e:
            output = f'Error: {e}'
    return render_template('lab4_pickle.html', output=output)


# Lab 5 — File include / read (path traversal + exec)
@app.route('/lab5', methods=['GET', 'POST'])
def lab5():
    content = ''
    if request.method == 'POST':
        filename = request.form.get('file', '')
        # Intentionally vulnerable: no path sanitization
        try:
            filepath = os.path.join('/tmp/uploads', filename)
            with open(filepath, 'r') as f:
                content = f.read()
        except Exception as e:
            content = f'Error: {e}'
    return render_template('lab5_fileinclude.html', content=content)


if __name__ == '__main__':
    os.makedirs('/tmp/uploads', exist_ok=True)

    # Create sample file for lab5
    with open('/tmp/uploads/readme.txt', 'w') as f:
        f.write('This is a sample file. Try reading other system files...')

    # ===== CREATE CTF FLAGS =====

    # Flag 1 - Command Injection
    os.makedirs('/opt', exist_ok=True)
    with open('/opt/flag1.txt', 'w') as f:
        f.write('FLAG{cmd_injection_master}')

    # Flag 2 - Eval Injection
    os.makedirs('/var', exist_ok=True)
    with open('/var/flag2.txt', 'w') as f:
        f.write('FLAG{eval_pwned_python}')

    # Flag 3 - SSTI
    os.makedirs('/usr/local/bin', exist_ok=True)
    with open('/usr/local/bin/flag3.txt', 'w') as f:
        f.write('FLAG{ssti_template_escape}')

    # Flag 4 - Insecure Deserialization
    os.makedirs('/home', exist_ok=True)
    with open('/home/flag4.txt', 'w') as f:
        f.write('FLAG{pickle_rce_achieved}')

    # Flag 5 - Path Traversal
    os.makedirs('/root/secret', exist_ok=True)
    with open('/root/secret/flag5.txt', 'w') as f:
        f.write('FLAG{path_traversal_root_access}')

    port = int(os.environ.get('PORT', '5003'))
    app.run(host='0.0.0.0', port=port, debug=True)
