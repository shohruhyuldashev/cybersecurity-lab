#!/bin/bash
# ============================================================
# CTF Lab - Build & Export Script
# Builds all 3 machine images and exports them as .tar files
# Usage: ./build_and_export.sh
# Load on another machine: docker load -i ctf-vm1.tar
# ============================================================

set -e
EXPORT_DIR="./exports"
mkdir -p "$EXPORT_DIR"

echo "╔══════════════════════════════════════════════╗"
echo "║       CTF Lab - Build & Export Tool         ║"
echo "╚══════════════════════════════════════════════╝"
echo ""

# ─── Build Images ────────────────────────────────────────────
echo "[*] Building VM1 (DocuShare - PHP LFI + cronjob privesc)..."
docker build -t ctf-vm1:latest ./vm1
echo "[+] VM1 built successfully!"
echo ""

echo "[*] Building VM2 (InternalAPI - IDOR + RCE + binary reverse)..."
docker build -t ctf-vm2:latest ./vm2
echo "[+] VM2 built successfully!"
echo ""

echo "[*] Building VM3 (AdminPanel - SQLi + python cap_setuid)..."
docker build -t ctf-vm3:latest ./vm3
echo "[+] VM3 built successfully!"
echo ""

# ─── Export as TAR ───────────────────────────────────────────
echo "[*] Exporting VM1 to $EXPORT_DIR/ctf-vm1.tar ..."
docker save ctf-vm1:latest -o "$EXPORT_DIR/ctf-vm1.tar"
echo "[+] VM1 exported: $(du -sh $EXPORT_DIR/ctf-vm1.tar | cut -f1)"

echo "[*] Exporting VM2 to $EXPORT_DIR/ctf-vm2.tar ..."
docker save ctf-vm2:latest -o "$EXPORT_DIR/ctf-vm2.tar"
echo "[+] VM2 exported: $(du -sh $EXPORT_DIR/ctf-vm2.tar | cut -f1)"

echo "[*] Exporting VM3 to $EXPORT_DIR/ctf-vm3.tar ..."
docker save ctf-vm3:latest -o "$EXPORT_DIR/ctf-vm3.tar"
echo "[+] VM3 exported: $(du -sh $EXPORT_DIR/ctf-vm3.tar | cut -f1)"

# ─── Bundle everything ───────────────────────────────────────
echo ""
echo "[*] Creating single bundle: $EXPORT_DIR/ctf-lab-all.tar.gz ..."
tar -czf "$EXPORT_DIR/ctf-lab-all.tar.gz" \
    "$EXPORT_DIR/ctf-vm1.tar" \
    "$EXPORT_DIR/ctf-vm2.tar" \
    "$EXPORT_DIR/ctf-vm3.tar" \
    docker-compose.yml
echo "[+] Bundle: $(du -sh $EXPORT_DIR/ctf-lab-all.tar.gz | cut -f1)"

echo ""
echo "╔══════════════════════════════════════════════╗"
echo "║              Export Complete!                ║"
echo "╠══════════════════════════════════════════════╣"
echo "║  Files in $EXPORT_DIR/:                         "
ls -lh "$EXPORT_DIR/"
echo "╚══════════════════════════════════════════════╝"
echo ""
echo "To load on another machine:"
echo "  docker load -i ctf-vm1.tar"
echo "  docker load -i ctf-vm2.tar"
echo "  docker load -i ctf-vm3.tar"
echo "  docker-compose up -d"
