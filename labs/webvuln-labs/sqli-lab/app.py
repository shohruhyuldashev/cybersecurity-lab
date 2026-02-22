from flask import Flask, request, render_template, redirect, url_for
import pymysql
import os

app = Flask(__name__)

# =============================
# MySQL Connection Helper
# =============================

def get_db(database="webapp_main"):
    return pymysql.connect(
        host=os.environ.get("DB_HOST", "mysql"),
        user=os.environ.get("DB_USER", "root"),
        password=os.environ.get("DB_PASS", "rootpass"),
        database=database,
        cursorclass=pymysql.cursors.DictCursor,
        autocommit=True
    )

# =============================
# DB Initialization (run once)
# =============================

def init_databases():
    conn = get_db(database=None)
    cur = conn.cursor()

    # Main DB
    cur.execute("CREATE DATABASE IF NOT EXISTS webapp_main")
    cur.execute("USE webapp_main")

    cur.execute("""
        CREATE TABLE IF NOT EXISTS users(
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(100),
            password VARCHAR(100)
        )
    """)

    cur.execute("""
        CREATE TABLE IF NOT EXISTS products(
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100),
            description TEXT
        )
    """)

    cur.execute("""
        CREATE TABLE IF NOT EXISTS comments(
            id INT PRIMARY KEY AUTO_INCREMENT,
            product_id INT,
            text TEXT
        )
    """)

    cur.execute("SELECT COUNT(*) as c FROM users")
    if cur.fetchone()["c"] == 0:
        cur.execute("INSERT INTO users(username,password) VALUES('admin','adminpass')")
        cur.execute("INSERT INTO users(username,password) VALUES('guest','guestpass')")

        cur.execute("""
            INSERT INTO products(name,description)
            VALUES
            ('Widget','Small widget'),
            ('Gadget','Useful gadget'),
            ('Thingamajig','Mystery item')
        """)

        cur.execute("INSERT INTO comments(product_id,text) VALUES(1,'Nice product!')")

    # Lab databases
    labs = {
        "lab1_db": "FLAG{basic_sqli_master}",
        "lab2_db": "FLAG{login_bypass_1337}",
        "lab3_db": "FLAG{union_extraction_pro}",
        "lab4_db": "FLAG{boolean_blind_ninja}",
        "lab5_db": "FLAG{stored_injection_chain}"
    }

    for dbname, flag in labs.items():
        cur.execute(f"CREATE DATABASE IF NOT EXISTS {dbname}")
        cur.execute(f"USE {dbname}")
        cur.execute("CREATE TABLE IF NOT EXISTS flag(flag VARCHAR(255))")
        cur.execute("SELECT COUNT(*) as c FROM flag")
        if cur.fetchone()["c"] == 0:
            cur.execute("INSERT INTO flag(flag) VALUES(%s)", (flag,))

    conn.close()


# Run initialization once container starts
try:
    init_databases()
except:
    pass


# =============================
# ROUTES
# =============================

@app.route('/')
def index():
    return render_template("index.html")


# Lab1 – Numeric SQLi
@app.route('/lab1')
def lab1():
    pid = request.args.get('id', '1')
    conn = get_db("webapp_main")
    cur = conn.cursor()

    query = f"SELECT * FROM products WHERE id = {pid}"

    try:
        cur.execute(query)
        rows = cur.fetchall()
    except Exception as e:
        rows = []
        query = f"ERROR: {e}"

    return render_template("lab_simple.html", query=query, rows=rows)


# Lab2 – Login bypass
@app.route('/lab2', methods=['GET','POST'])
def lab2():
    message = ''
    query = ''
    conn = get_db("webapp_main")
    cur = conn.cursor()

    if request.method == 'POST':
        u = request.form.get('username','')
        p = request.form.get('password','')

        query = f"SELECT * FROM users WHERE username='{u}' AND password='{p}'"

        try:
            cur.execute(query)
            result = cur.fetchall()
            if result:
                message = "Login success!"
            else:
                message = "Login failed"
        except Exception as e:
            message = str(e)

    return render_template("lab_login.html", query=query, message=message)


# Lab3 – UNION SQLi
@app.route('/lab3')
def lab3():
    name = request.args.get('name','')
    conn = get_db("webapp_main")
    cur = conn.cursor()

    query = f"SELECT id,name,description FROM products WHERE name LIKE '%{name}%';"

    try:
        cur.execute(query)
        rows = cur.fetchall()
    except Exception as e:
        rows = []
        query = f"ERROR: {e}"

    return render_template("lab_search.html", query=query, rows=rows)


# Lab4 – Boolean SQLi
@app.route('/lab4')
def lab4():
    filt = request.args.get('f','')
    conn = get_db("webapp_main")
    cur = conn.cursor()

    query = f"SELECT id,name FROM products WHERE description LIKE '%{filt}%';"

    try:
        cur.execute(query)
        rows = cur.fetchall()
    except Exception as e:
        rows = []
        query = f"ERROR: {e}"

    return render_template("lab_bool.html", query=query, rows=rows)


# Lab5 – Stored SQLi
@app.route('/lab5', methods=['GET','POST'])
def lab5():
    msg = ''
    conn = get_db("webapp_main")
    cur = conn.cursor()

    if request.method == 'POST':
        pid = request.form.get('product_id','1')
        text = request.form.get('text','')

        ins = f"INSERT INTO comments(product_id,text) VALUES({pid},'{text}')"

        try:
            cur.execute(ins)
            msg = "Comment added!"
        except Exception as e:
            msg = str(e)

    cur.execute("""
        SELECT c.id,c.product_id,c.text,p.name
        FROM comments c
        LEFT JOIN products p ON p.id=c.product_id
    """)
    comments = cur.fetchall()

    return render_template("lab_store.html", comments=comments, message=msg)


if __name__ == '__main__':
    port = int(os.environ.get('PORT', '5002'))
    app.run(host='0.0.0.0', port=port)