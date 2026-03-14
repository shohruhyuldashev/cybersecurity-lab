#!/usr/bin/env bash

# Setup script for MOVEit LXD Lab
# Prepares a minimal Ubuntu host with Docker, Docker Compose, Snap/LXD,
# then builds and starts the lab stack.

set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
COMPOSE_FILE="$SCRIPT_DIR/docker-compose.yml"
HOST_FLAG="/root/root.txt"
LXD_SOCKET="/var/snap/lxd/common/lxd/unix.socket"

log() {
    printf '[*] %s\n' "$1"
}

success() {
    printf '[+] %s\n' "$1"
}

warn() {
    printf '[!] %s\n' "$1"
}

fail() {
    printf '[x] %s\n' "$1" >&2
    exit 1
}

on_error() {
    local line="$1"
    fail "Setup failed near line ${line}. Review the output above for the exact command that failed."
}

trap 'on_error $LINENO' ERR

require_root() {
    if [ "${EUID}" -ne 0 ]; then
        fail "Please run as root: sudo ./setup.sh"
    fi
}

require_apt() {
    command -v apt-get >/dev/null 2>&1 || fail "This installer currently supports Debian/Ubuntu hosts with apt-get."
}

apt_update_if_needed() {
    if [ ! -f /var/cache/apt/pkgcache.bin ] || find /var/cache/apt/pkgcache.bin -mmin +180 >/dev/null 2>&1; then
        log "Refreshing package index..."
        apt-get update
    fi
}

install_apt_packages() {
    local missing=()
    local pkg

    for pkg in "$@"; do
        if ! dpkg -s "$pkg" >/dev/null 2>&1; then
            missing+=("$pkg")
        fi
    done

    if [ "${#missing[@]}" -gt 0 ]; then
        apt_update_if_needed
        log "Installing packages: ${missing[*]}"
        DEBIAN_FRONTEND=noninteractive apt-get install -y "${missing[@]}"
    fi
}

ensure_base_packages() {
    install_apt_packages ca-certificates curl gnupg lsb-release apt-transport-https software-properties-common
}

ensure_systemd_service() {
    local service="$1"

    if command -v systemctl >/dev/null 2>&1; then
        systemctl enable --now "$service"
    else
        warn "systemctl is not available; unable to enable ${service} automatically."
    fi
}

wait_for_path() {
    local path="$1"
    local timeout="${2:-30}"
    local elapsed=0

    while [ ! -e "$path" ]; do
        sleep 1
        elapsed=$((elapsed + 1))
        if [ "$elapsed" -ge "$timeout" ]; then
            return 1
        fi
    done
}

ensure_snapd() {
    if command -v snap >/dev/null 2>&1; then
        success "snap is already installed."
        return
    fi

    log "snap not found. Installing snapd..."
    install_apt_packages snapd squashfs-tools
    ensure_systemd_service snapd.socket
    ensure_systemd_service snapd.service

    if [ -L /snap ]; then
        :
    elif [ ! -e /snap ]; then
        ln -s /var/lib/snapd/snap /snap
    fi

    wait_for_path /run/snapd.socket 30 || fail "snapd socket did not become ready."
    success "snapd installed successfully."
}

ensure_docker() {
    if command -v docker >/dev/null 2>&1; then
        success "Docker is already installed."
    else
        log "Installing Docker from Ubuntu repositories..."
        install_apt_packages docker.io
    fi

    ensure_systemd_service docker
}

ensure_docker_compose() {
    if docker compose version >/dev/null 2>&1; then
        success "Docker Compose plugin is available."
        return
    fi

    log "Docker Compose plugin not found. Installing fallback compose packages..."
    install_apt_packages docker-compose-plugin docker-compose

    if docker compose version >/dev/null 2>&1; then
        success "Docker Compose plugin installed."
        return
    fi

    if command -v docker-compose >/dev/null 2>&1; then
        success "Legacy docker-compose binary is available."
        return
    fi

    fail "Docker Compose is still unavailable after installation."
}

compose_cmd() {
    if docker compose version >/dev/null 2>&1; then
        docker compose -f "$COMPOSE_FILE" "$@"
    else
        docker-compose -f "$COMPOSE_FILE" "$@"
    fi
}

ensure_lxd() {
    if command -v lxd >/dev/null 2>&1; then
        success "LXD is already installed."
    else
        ensure_snapd
        log "Installing LXD via snap..."
        snap install lxd
    fi

    if command -v snap >/dev/null 2>&1; then
        snap wait system seed.loaded >/dev/null 2>&1 || true
    fi
}

ensure_lxd_initialized() {
    if [ -S "$LXD_SOCKET" ]; then
        success "LXD appears to be initialized."
        return
    fi

    log "Initializing LXD with default settings..."
    lxd init --auto
    wait_for_path "$LXD_SOCKET" 30 || fail "LXD socket did not appear after initialization."
}

prepare_shared_storage() {
    log "Preparing shared uploads volume..."

    # Volume nomi: docker compose project_name + volume nomi
    # docker-compose.yml da volumes: uploads: deb volumes: moveit-lxd-lab_uploads
    local vol_name
    vol_name="$(basename "$SCRIPT_DIR")_uploads"

    # Volume mavjud emasligini tekshirib, yaratish
    if ! docker volume inspect "$vol_name" >/dev/null 2>&1; then
        docker volume create "$vol_name"
        log "Created Docker volume: $vol_name"
    else
        log "Docker volume already exists: $vol_name"
    fi

    # Alpine container orqali papkaga 777 berish
    # MySQL (uid 999) INTO OUTFILE bilan fayl yoza olishi uchun kerak
    docker run --rm \
        -v "${vol_name}:/var/www/uploads" \
        alpine sh -c "
        mkdir -p /var/www/uploads
        chmod 777 /var/www/uploads
        apk add --no-cache acl
        setfacl -d -m o::r /var/www/uploads
        setfacl -d -m g::r /var/www/uploads
    "

    success "Shared storage prepared with correct permissions."
}

build_and_start_lab() {
    log "Building MOVEit web image..."
    docker build -t moveit-web "$SCRIPT_DIR/moveit-web"

    log "Starting lab stack..."
    compose_cmd up -d --build
}

create_host_flag() {
    log "Creating host flag..."
    printf 'ROOT{1733eb61f77708408425c3208a18a2f6}\n' > "$HOST_FLAG"
    chmod 600 "$HOST_FLAG"
}


configure_lxd_socket_acl() {
    log "Configuring LXD socket permissions for container access..."

    if [ ! -S "$LXD_SOCKET" ]; then
        warn "LXD socket not found at $LXD_SOCKET"
        return
    fi

    # www-data (uid 33) ga rw permission beramiz
    if command -v setfacl >/dev/null 2>&1; then
        setfacl -m u:33:rw "$LXD_SOCKET" || warn "Failed to set ACL on $LXD_SOCKET"
        success "ACL applied: www-data can access LXD socket."
    else
        warn "setfacl not installed, installing acl package..."
        install_apt_packages acl
        setfacl -m u:33:rw "$LXD_SOCKET"
        success "ACL applied after installing acl package."
    fi
}


wait_for_containers() {
    local attempts=0

    log "Waiting for containers and LXD socket mount to settle..."
    while [ "$attempts" -lt 30 ]; do
        if docker ps --format '{{.Names}}' | grep -qx 'moveit-web'; then
            success "moveit-web container is running."
            return
        fi
        attempts=$((attempts + 1))
        sleep 2
    done

    warn "Container did not report as running within the expected window. Check 'docker ps' and compose logs if needed."
}

configure_domain() {
    log "Configuring local domain moveit.vm..."

    if ! grep -q "moveit.vm" /etc/hosts; then
        echo "127.0.0.1 moveit.vm" >> /etc/hosts
        success "Domain moveit.vm added to /etc/hosts"
    else
        log "Domain moveit.vm already present in /etc/hosts"
    fi
}

print_summary() {
    cat <<EOF

Lab setup complete!
Lab URL:          http://localhost:8080
Compose file:     $COMPOSE_FILE
Host flag:        $HOST_FLAG
LXD socket:       $LXD_SOCKET

Default credentials:
  admin   / Summer2024!
  auditor / Compliance2024!

SQLi exploit payload (notes field):
  x'); SELECT 'LAB_WEBSHELL' INTO OUTFILE '/var/www/uploads/shell.txt'; -- 

Webshell URL:
  http://localhost:8080/uploads/shell.txt?cmd=id
EOF
}

main() {
    log "Setting up MOVEit LXD Lab on this host..."
    require_root
    require_apt
    ensure_base_packages
    ensure_docker
    ensure_docker_compose
    ensure_lxd
    ensure_lxd_initialized
    build_and_start_lab
    prepare_shared_storage
    configure_lxd_socket_acl
    create_host_flag
    wait_for_containers
    configure_domain
    print_summary
}

main "$@"