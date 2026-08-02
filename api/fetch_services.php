<?php
header('Content-Type: application/json');

// Simple test without database
echo json_encode([
    'success' => true,
    'data' => [
        ['id' => 1, 'name' => 'CIBIL Score Repair', 'category' => 'credit_repair', 'description' => 'Test service 1', 'price' => 999.00, 'status' => 'active', 'is_featured' => 1, 'is_popular' => 1, 'icon' => '📈'],
        ['id' => 2, 'name' => 'Dispute Resolution', 'category' => 'dispute', 'description' => 'Test service 2', 'price' => 1499.00, 'status' => 'active', 'is_featured' => 1, 'is_popular' => 0, 'icon' => '⚖️'],
        ['id' => 3, 'name' => 'Credit Consultation', 'category' => 'consulting', 'description' => 'Test service 3', 'price' => 499.00, 'status' => 'active', 'is_featured' => 0, 'is_popular' => 1, 'icon' => '💡']
    ],
    'total' => 3
]);