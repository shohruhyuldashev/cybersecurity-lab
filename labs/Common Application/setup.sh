#!/bin/bash

echo "[*] Updating package list and installing Docker..."
sudo apt-get update
sudo apt-get install -y docker.io docker-compose

echo "[*] Starting the CTF Infrastructure..."
sudo docker-compose up -d

echo ""
echo "[*] Infrastructure started successfully!"
echo "[*] IMPORTANT: Please add the following line to your /etc/hosts file to enable subdomain routing:"
echo ""
echo "127.0.0.1 haad.local wordpress.haad.local joomla.haad.local drupal.haad.local tomcat.haad.local jenkins.haad.local splunk.haad.local cgi.haad.local gitlab.haad.local"
echo ""
echo "[*] Check CTF_Architecture_and_Challenges.md for the challenge list and flag instructions."
