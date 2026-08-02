#!/bin/bash
echo "🔍 Security Check - " $(date)
echo "📁 Checking for suspicious PHP files..."
find . -name "*.php" -mtime -1 | head -10
echo "🔐 Recent failed logins:"
grep "Failed password" /var/log/auth.log 2>/dev/null | tail -5 || echo "No auth log found"
