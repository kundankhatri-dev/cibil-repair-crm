<?php
// api/whatsapp/config.php
require_once '../config.php';

// WhatsApp Business API Configuration (from .env)
define('WHATSAPP_TOKEN', getenv('WHATSAPP_TOKEN') ?: '');
define('WHATSAPP_PHONE_NUMBER_ID', getenv('WHATSAPP_PHONE_NUMBER_ID') ?: '');
define('WHATSAPP_BUSINESS_ACCOUNT_ID', getenv('WHATSAPP_BUSINESS_ACCOUNT_ID') ?: '');
define('WHATSAPP_API_VERSION', 'v18.0');
define('WHATSAPP_API_URL', 'https://graph.facebook.com/' . WHATSAPP_API_VERSION . '/' . WHATSAPP_PHONE_NUMBER_ID . '/messages');

// Default WhatsApp number for fallback (your existing number)
define('DEFAULT_WHATSAPP_NUMBER', '919905482503');

// Rate limiting
define('WHATSAPP_RATE_LIMIT', 50);
define('WHATSAPP_RATE_WINDOW', 60);
?>