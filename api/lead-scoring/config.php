<?php
// api/lead-scoring/config.php
require_once __DIR__ . '/../config.php';

// Scoring thresholds
define('SCORE_URGENT', 70);
define('SCORE_HIGH', 50);
define('SCORE_MEDIUM', 30);

// Priority mapping
function getPriorityFromScore($score) {
    if ($score >= SCORE_URGENT) return 'urgent';
    if ($score >= SCORE_HIGH) return 'high';
    if ($score >= SCORE_MEDIUM) return 'medium';
    return 'low';
}

function getPriorityLabel($priority) {
    switch ($priority) {
        case 'urgent': return '🔴 Urgent';
        case 'high': return '🟠 High';
        case 'medium': return '🟡 Medium';
        default: return '⚪ Low';
    }
}

function getPriorityColor($priority) {
    switch ($priority) {
        case 'urgent': return '#dc3545';
        case 'high': return '#fd7e14';
        case 'medium': return '#ffc107';
        default: return '#6c757d';
    }
}
?>