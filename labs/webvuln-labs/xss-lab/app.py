from flask import Flask, request, redirect, url_for, render_template
import os

app = Flask(__name__)

comments = []
loaded_images = []

FLAGS = {
    "lab1": "FLAG{reflected_xss_master}",
    "lab2": "FLAG{stored_xss_persistent}",
    "lab3": "FLAG{dom_xss_client_pwn}",
    "lab4": "FLAG{attribute_injection_1337}",
    "lab5": "FLAG{blind_xss_hunter}"
}


@app.route('/')
def index():
    return render_template("index.html")


@app.route('/lab1')
def lab1():
    q = request.args.get('q', '')
    return render_template("lab1_reflected.html", q=q, flag=FLAGS["lab1"])


@app.route('/lab2', methods=['GET', 'POST'])
def lab2():
    if request.method == 'POST':
        c = request.form.get('comment', '')
        comments.append(c)
        return redirect(url_for('lab2'))

    return render_template("lab2_stored.html", comments=comments, flag=FLAGS["lab2"])


@app.route('/lab3')
def lab3():
    return render_template("lab3_dom.html", flag=FLAGS["lab3"])


@app.route('/lab4')
def lab4():
    v = request.args.get('v', '')
    return render_template("lab4_attribute.html", v=v, flag=FLAGS["lab4"])


@app.route('/lab5', methods=['GET', 'POST'])
def lab5():
    if request.method == 'POST':
        url = request.form.get('img', '')
        loaded_images.append(url)
        return redirect(url_for('lab5'))

    return render_template("lab5_image_blind.html", imgs=loaded_images, flag=FLAGS["lab5"])


if __name__ == '__main__':
    port = int(os.environ.get('PORT', '5000'))
    app.run(host='0.0.0.0', port=port, debug=True)