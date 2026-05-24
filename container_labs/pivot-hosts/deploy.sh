#!/bin/bash

# ANSI Colors
RED='\033[31m'
YELLOW='\033[33m'
GREEN='\033[32m'
CYAN='\033[36m'
BOLD='\033[1m'
RESET='\033[0m'

clear
printf "${BOLD}${CYAN}"
printf "   ____      _               ____            \n"
printf "  / ___|   _| |__   ___ _ __| __ ) _ __ ___  \n"
printf " | |  | | | | '_ \\ / _ \\ '__|  _ \\| '__/ _ \\ \n"
printf " | |__| |_| | |_) |  __/ |  | |_) | | | (_) |\n"
printf "  \\____\\__, |_.__/ \\___|_|  |____/|_|  \\___/ \n"
printf "        |___/          CTF Lab Deploy v2.0     \n"
printf "${RESET}\n"

# ─── Usage ────────────────────────────────────────────────────────────────────
if [ $# -ne 3 ]; then
    echo -e "${RED}Usage:${RESET} $0 <vm1.tar> <vm2.tar> <vm3.tar>"
    echo ""
    echo -e "  Example: ${CYAN}./deploy.sh ctf-vm1.tar ctf-vm2.tar ctf-vm3.tar${RESET}"
    exit 1
fi

VM1_TAR="$1"; VM2_TAR="$2"; VM3_TAR="$3"

# ─── Check files ──────────────────────────────────────────────────────────────
for f in "$VM1_TAR" "$VM2_TAR" "$VM3_TAR"; do
    if [ ! -f "$f" ]; then
        echo -e "${RED}[!] File not found: $f${RESET}"
        exit 1
    fi
done

# ─── Docker check / install ───────────────────────────────────────────────────
if ! command -v docker &> /dev/null; then
    echo -e "${YELLOW}[*] Docker not found. Installing...${RESET}"
    sudo apt-get update -qq && sudo apt-get install -y docker.io > /dev/null
    sudo systemctl enable --now docker
    echo -e "${GREEN}[+] Docker installed.${RESET}"
fi

# ─── Detect host network interface & subnet ───────────────────────────────────
HOST_IF=$(ip route show default | awk '/default/ {print $5}' | head -1)
HOST_GW=$(ip route show default | awk '/default/ {print $3}' | head -1)
HOST_SUBNET=$(ip -o -f inet addr show "$HOST_IF" 2>/dev/null | awk '{print $4}' | head -1)
HOST_IP=$(ip -o -f inet addr show "$HOST_IF" 2>/dev/null | awk '{print $4}' | cut -d/ -f1 | head -1)

if [ -z "$HOST_IF" ] || [ -z "$HOST_SUBNET" ]; then
    echo -e "${RED}[!] Could not detect network interface. Falling back to port-mapping mode.${RESET}"
    USE_MACVLAN=false
else
    echo -e "${CYAN}[i] Host interface : ${HOST_IF}${RESET}"
    echo -e "${CYAN}[i] Host subnet    : ${HOST_SUBNET}${RESET}"
    echo -e "${CYAN}[i] Host IP        : ${HOST_IP}${RESET}"
    USE_MACVLAN=true
fi

# ─── Cleanup ──────────────────────────────────────────────────────────────────
echo -e "${YELLOW}[*] Cleaning up previous lab (if any)...${RESET}"
docker rm -f ctf-vm1 ctf-vm2 ctf-vm3 > /dev/null 2>&1 || true
docker network rm ctf-external ctf-net2 ctf-net3 > /dev/null 2>&1 || true

# ─── Load images ──────────────────────────────────────────────────────────────
echo -e "${YELLOW}[*] Loading VM1 image...${RESET}"
docker load -i "$VM1_TAR" > /dev/null 2>&1 || { echo -e "${RED}[!] Failed to load VM1${RESET}"; exit 1; }

echo -e "${YELLOW}[*] Loading VM2 image...${RESET}"
docker load -i "$VM2_TAR" > /dev/null 2>&1 || { echo -e "${RED}[!] Failed to load VM2${RESET}"; exit 1; }

echo -e "${YELLOW}[*] Loading VM3 image...${RESET}"
docker load -i "$VM3_TAR" > /dev/null 2>&1 || { echo -e "${RED}[!] Failed to load VM3${RESET}"; exit 1; }

echo -e "${GREEN}[+] All images loaded.${RESET}"

# ─── Create networks ──────────────────────────────────────────────────────────
echo -e "${YELLOW}[*] Creating networks...${RESET}"

if [ "$USE_MACVLAN" = true ]; then
    # VM1 gets real IP on physical network visible to all LAN devices
    if docker network create \
        -d macvlan \
        --subnet="$HOST_SUBNET" \
        --gateway="$HOST_GW" \
        -o parent="$HOST_IF" \
        ctf-external > /dev/null 2>&1; then
        echo -e "${GREEN}    ✓ ctf-external (macvlan on ${HOST_IF}) — VM1 gets LAN IP${RESET}"
        echo -e "${YELLOW}    ⚠  Host cannot ping VM1 directly (macvlan isolation)${RESET}"
    else
        echo -e "${YELLOW}    ⚠  macvlan failed, using bridge + port-map fallback${RESET}"
        docker network create --subnet=10.10.10.0/24 ctf-external > /dev/null
        USE_MACVLAN=false
    fi
else
    docker network create --subnet=10.10.10.0/24 ctf-external > /dev/null
    echo -e "${GREEN}    ✓ ctf-external (bridge, port 80 mapped)${RESET}"
fi

docker network create --subnet=172.16.0.0/24    --internal ctf-net2 > /dev/null
docker network create --subnet=192.168.100.0/24 --internal ctf-net3 > /dev/null
echo -e "${GREEN}    ✓ ctf-net2 (172.16.0.0/24 — internal, no external access)${RESET}"
echo -e "${GREEN}    ✓ ctf-net3 (192.168.100.0/24 — internal, no external access)${RESET}"

# ─── Start VM1 ────────────────────────────────────────────────────────────────
echo -e "${YELLOW}[*] Starting VM1 (Entry Point)...${RESET}"
if [ "$USE_MACVLAN" = true ]; then
    # No --ip → Docker picks an available IP from subnet (DHCP-like from host range)
    docker run -d \
        --name ctf-vm1 \
        --network ctf-external \
        --restart unless-stopped \
        ctf-vm1:latest > /dev/null
else
    docker run -d \
        --name ctf-vm1 \
        --network ctf-external \
        --ip 10.10.10.10 \
        -p 80:80 \
        --restart unless-stopped \
        ctf-vm1:latest > /dev/null
fi
# Connect VM1 to internal net2 as pivot point
docker network connect --ip 172.16.0.10 ctf-net2 ctf-vm1 > /dev/null

# ─── Start VM2 ────────────────────────────────────────────────────────────────
echo -e "${YELLOW}[*] Starting VM2 (Internal API)...${RESET}"
docker run -d \
    --name ctf-vm2 \
    --network ctf-net2 \
    --ip 172.16.0.20 \
    --restart unless-stopped \
    ctf-vm2:latest > /dev/null
docker network connect --ip 192.168.100.20 ctf-net3 ctf-vm2 > /dev/null

# ─── Start VM3 ────────────────────────────────────────────────────────────────
echo -e "${YELLOW}[*] Starting VM3 (Admin Panel)...${RESET}"
docker run -d \
    --name ctf-vm3 \
    --network ctf-net3 \
    --ip 192.168.100.30 \
    --restart unless-stopped \
    ctf-vm3:latest > /dev/null

# ─── Get VM1's actual IP ──────────────────────────────────────────────────────
echo -e "${YELLOW}[*] Waiting for services to initialize...${RESET}"
for i in $(seq 15 -1 1); do
    printf "\r  ${CYAN}%2d seconds remaining...${RESET}" "$i"
    sleep 1
done
echo ""

if [ "$USE_MACVLAN" = true ]; then
    VM1_IP=$(docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' ctf-vm1 2>/dev/null | head -1)
    VM1_URL="http://${VM1_IP}/"
else
    VM1_IP="$HOST_IP"
    VM1_URL="http://${HOST_IP}/"
fi

# ─── Health checks ────────────────────────────────────────────────────────────
VM1_CODE=$(docker exec ctf-vm1 curl -s -o /dev/null -w "%{http_code}" --max-time 5 http://localhost/ 2>/dev/null)
VM2_CODE=$(docker exec ctf-vm2 curl -s -o /dev/null -w "%{http_code}" --max-time 5 http://localhost:5000/ 2>/dev/null)
VM3_CODE=$(docker exec ctf-vm3 curl -s -o /dev/null -w "%{http_code}" --max-time 5 http://localhost/ 2>/dev/null)

mark() { [ "$1" = "200" ] && echo -e "${GREEN}✓${RESET}" || echo -e "${RED}✗${RESET}"; }

# ─── Summary ──────────────────────────────────────────────────────────────────
echo ""
echo -e "${BOLD}${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${RESET}"
echo -e "${BOLD}   CTF Lab Ready!${RESET}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${RESET}"
echo ""
echo -e "  ${BOLD}Attack Entry Point:${RESET}"
echo -e "  ${GREEN}➤  ${VM1_URL}${RESET}"
if [ "$USE_MACVLAN" = true ]; then
    echo -e "  ${CYAN}(VM1 appears as a real host on your network)${RESET}"
fi
echo ""
echo -e "  ${BOLD}Network Topology:${RESET}"
echo -e "  ${CYAN}[Attacker]${RESET}"
echo -e "       ↓"
echo -e "  ${YELLOW}[VM1: ${VM1_IP:-10.10.10.10}]${RESET}  ← Entry point (port 80)"
echo -e "       ↓  172.16.0.10"
echo -e "  ${YELLOW}[VM2: 172.16.0.20]${RESET}  ← Internal only (pivot required)"
echo -e "       ↓  192.168.100.20"
echo -e "  ${YELLOW}[VM3: 192.168.100.30]${RESET} ← Deep internal (double pivot)"
echo ""
echo -e "  ${BOLD}Service Status:${RESET}"
echo -e "  VM1 DocuShare   $(mark $VM1_CODE)  ${VM1_IP:-?}:80"
echo -e "  VM2 InternalAPI $(mark $VM2_CODE)  172.16.0.20:5000"
echo -e "  VM3 AdminPanel  $(mark $VM3_CODE)  192.168.100.30:80"
echo ""
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${RESET}"
echo -e "  ${YELLOW}Press Ctrl+C to stop and remove the entire lab${RESET}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${RESET}"
echo ""

# ─── Ctrl+C → cleanup ─────────────────────────────────────────────────────────
trap cleanup INT
cleanup() {
    echo ""
    echo -e "${YELLOW}[*] Shutting down CTF lab...${RESET}"
    docker rm -f ctf-vm1 ctf-vm2 ctf-vm3 > /dev/null 2>&1
    docker network rm ctf-external ctf-net2 ctf-net3 > /dev/null 2>&1
    echo -e "${GREEN}[+] Lab removed. Goodbye.${RESET}"
    exit 0
}

while true; do sleep 5; done
