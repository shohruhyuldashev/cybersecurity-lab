const express = require('express');
const jwt = require('jsonwebtoken');
const app = express();
const PORT = 3000;
const axios = require('axios'); // Need to add to package.json

const JWT_SECRET = "super_secret_key_12345";
const USERS = {
    "admin": "SuperSecretAdminPass",
    "user": "user123"
};

app.use(express.urlencoded({ extended: true }));

// CSS Styles (Inline for simplicity in Node app, or serve static)
const css = `
<style>
:root { --primary: #003366; --bg: #f4f7f6; --text: #333; }
body { font-family: 'Segoe UI', sans-serif; background: var(--bg); color: var(--text); padding: 2rem; }
.card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 500px; margin: 0 auto; }
h1 { color: var(--primary); text-align: center; }
input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
button { width: 100%; padding: 10px; background: var(--primary); color: white; border: none; border-radius: 4px; cursor: pointer; }
.alert { color: red; text-align: center; }
</style>
`;

app.get('/', (req, res) => {
    const error = req.query.error ? `<p class="alert">${req.query.error}</p>` : '';
    res.send(`
        ${css}
        <div class="card">
            <h1>Central Identity Provider</h1>
            <p style="text-align:center;">Single Sign-On (SSO) Portal</p>
            ${error}
            <form action="/auth" method="POST">
                <input type="text" name="username" placeholder="Username" required/>
                <input type="password" name="password" placeholder="Password" required/>
                <input type="hidden" name="redirect_uri" value="${req.query.redirect_uri || ''}" />
                <button type="submit">Login</button>
            </form>
        </div>
    `);
});

app.post('/auth', (req, res) => {
    const { username, password, redirect_uri } = req.body;

    if (USERS[username] && USERS[username] === password) {
        const token = jwt.sign({ username, role: username === 'admin' ? 'admin' : 'user' }, JWT_SECRET);

        if (redirect_uri) {
            return res.redirect(`${redirect_uri}?token=${token}`);
        } else if (username === 'admin') {
            return res.redirect('/admin_panel'); // Success login for admin
        } else {
            return res.send("Login Successful (No Redirect URI provided)");
        }
    } else {
        res.redirect('/?error=Invalid Credentials');
    }
});

// Admin Panel - Vulnerable to triggering Log4Shell
app.get('/admin_panel', (req, res) => {
    // Basic cookie/session check omitted for simplicity, relying on flow
    res.send(`
        ${css}
        <div class="card" style="max-width:800px;">
            <h1>Admin Diagnostics</h1>
            <h3>Legacy Logger Connectivity Test</h3>
            <p>Test the connection to the legacy logging system.</p>
            <form action="/test_log" method="POST">
                <label>User-Agent Header (for logging):</label>
                <input type="text" name="user_agent" placeholder="Mozilla/5.0..." value="Mozilla/5.0" />
                <button type="submit">Send Test Log</button>
            </form>
        </div>
    `);
});

app.post('/test_log', async (req, res) => {
    const user_agent = req.body.user_agent;
    try {
        // VULNERABILITY: User input passed to X-Api-Version header which is logged by Log4j
        // The vulnerable app logs headers.
        await axios.get('http://legacy-logger:8080', {
            headers: {
                'X-Api-Version': user_agent
            }
        });
        res.send(`${css}<div class="card"><h1>Success</h1><p>Log sent successfully.</p><a href="/admin_panel">Back</a></div>`);
    } catch (e) {
        res.send(`${css}<div class="card"><h1>Error</h1><p>Failed to send log: ${e.message}</p><a href="/admin_panel">Back</a></div>`);
    }
});

app.get('/verify', (req, res) => {
    const token = req.query.token;
    try {
        const decoded = jwt.verify(token, JWT_SECRET);
        res.json({ valid: true, user: decoded });
    } catch (e) {
        res.json({ valid: false });
    }
});

app.listen(PORT, () => {
    console.log(`Central IDP listening on port ${PORT}`);
});
