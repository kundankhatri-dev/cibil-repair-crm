<?php
// api/followup/config.php
require_once '../config.php';

// Follow-up specific configuration
define('FOLLOWUP_REMINDER_HOURS', 24); // Send reminder 24 hours before
define('FOLLOWUP_MAX_REMINDERS', 3); // Max reminders per follow-up
define('FOLLOWUP_REMINDER_INTERVAL', 24); // Hours between reminders

// Time windows
define('FOLLOWUP_URGENT_HOURS', 4); // Urgent follow-up within 4 hours
define('FOLLOWUP_HIGH_HOURS', 12); // High priority within 12 hours
define('FOLLOWUP_MEDIUM_HOURS', 24); // Medium within 24 hours
define('FOLLOWUP_LOW_HOURS', 48); // Low within 48 hours

// Timezone
date_default_timezone_set('Asia/Kolkata');
?>