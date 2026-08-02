#!/bin/bash
echo "🔍 Security Check - " $(date)

# Check .env permissions
if [ -f ".env" ]; then
    PERMS=$(stat -c "%a" .env)
    if [ "$PERMS" != "600" ] && [ "$PERMS" != "644" ]; then
        echo "⚠️ .env file permissions: $PERMS (should be 600 or 644)"
    fi
fi

# Check for suspicious files
echo "📁 Checking for suspicious PHP files..."
find . -name "*.php" -mtime -1 | head -10

# Check failed logins
echo "🔐 Recent failed logins:"
grep "Failed password" /var/log/auth.log 2>/dev/null | tail -5 || echo "No auth log found"
