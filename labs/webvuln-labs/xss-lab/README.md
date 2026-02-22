# XSS Labs (5 exercises)

This small lab demonstrates 5 simple XSS scenarios (for learning only).

How to run (on Linux):

1. Build and start with Docker Compose (uses host networking so services are reachable on host interfaces):

```bash
cd xss-lab
docker compose up --build
```

2. Open http://localhost:5000 in your browser.

Files:
- [xss-lab/app.py](xss-lab/app.py) - Flask app with routes for each lab
- [xss-lab/Dockerfile](xss-lab/Dockerfile)
- [xss-lab/docker-compose.yml](xss-lab/docker-compose.yml)
- [xss-lab/templates](xss-lab/templates) - HTML templates for each lab

Warning: This application intentionally contains XSS vulnerabilities for education. Run only in an isolated lab environment.
