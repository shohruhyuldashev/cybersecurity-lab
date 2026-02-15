

# 🛡️ Cybersecurity Lab

Welcome to **Cybersecurity Lab** — a personal collection of vulnerable environments and attack-chain based labs designed for learning and practicing **offensive and defensive security** techniques.

This repository is intended for:
- Hands-on penetration testing practice
- Understanding real-world attack chains
- Learning Docker-based lab design
- Security research and education

> ⚠️ **Disclaimer**  
> All labs in this repository are intentionally vulnerable and must be used **only in controlled environments for educational purposes**.


## 📂 Repository Structure

```

cybersecurity-lab/
├── labs/
│   └── attackchains-lab/
│       ├── docker-compose.yml
│       ├── data-api/
│       ├── idp/
│       ├── web-frontend/
│       ├── minio-seed/
│       │   └── agency-backups/
│       │       └── portal-creds.txt
│       └── README.md
└── README.md

```

Each lab is **self-contained** and documented separately.


## 🧪 Labs

### 🔹 AttackChains Lab
A multi-service vulnerable environment that demonstrates how low-impact bugs can be chained into full system compromise.

**Key concepts:**
- Web vulnerabilities
- Credential exposure
- Service misconfiguration
- Attack chaining
- Containerized infrastructure

📁 Location:  
```

labs/attackchains-lab/

````

📘 Detailed instructions and attack flow are available in the lab-specific README.


## 🚀 Getting Started

> Requirements:
- Docker
- Docker Compose

Clone the repository:
```bash
git clone https://github.com/shohruhyuldashev/cybersecurity-lab.git
cd cybersecurity-lab
````

Start a lab (example):

```bash
cd labs/attackchains-lab
docker-compose up -d
```


## 🎯 Learning Objectives

By working through these labs, you will practice:

* Vulnerability discovery
* Exploitation techniques
* Privilege escalation logic
* Understanding real-world attack paths
* Secure lab design and threat modeling



## 🔒 Security Notice

* No real credentials are used.
* All secrets are **intentionally fake or lab-only**.
* Do **NOT** deploy these labs in production environments.


## 👤 Author

**CyberBro**
Cybersecurity enthusiast focused on:

* Penetration Testing
* Network Security
* Attack Chain Analysis
* Vulnerable Lab Development



## 📜 License

This project is provided for **educational purposes only**.
Use responsibly.


