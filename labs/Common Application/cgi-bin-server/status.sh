#!/bin/bash
echo "Content-type: text/html"
echo ""
echo "<html><body><h2>System Status</h2>"
echo "<pre>"
uptime
echo "</pre></body></html>"
