#!/bin/bash
cd /home/u929623538/domains/cibilrepair.in/public_html

# Get previous commit
PREVIOUS=$(git log --oneline -2 | tail -1 | cut -d' ' -f1)
echo "🔄 Rolling back to: $PREVIOUS"

# Reset to previous commit
git reset --hard $PREVIOUS

# Clear cache
php bin/console deploy

echo "✅ Rollback complete!"
