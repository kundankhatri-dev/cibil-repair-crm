<?php
// /api/docs/index.php
header('Content-Type: application/json');

$apiDocs = [
    'openapi' => '3.0.0',
    'info' => [
        'title' => 'CIBIL Repair CRM API',
        'description' => 'Complete API documentation for CIBIL Repair CRM',
        'version' => '1.0.0',
        'contact' => [
            'name' => 'API Support',
            'email' => 'support@cibilrepair.in'
        ]
    ],
    'servers' => [
        [
            'url' => 'https://cibilrepair.in/api/',
            'description' => 'Production Server'
        ]
    ],
    'paths' => [
        // ── ADMIN APIS ──
        '/admin/test' => [
            'get' => [
                'summary' => 'Test Admin API',
                'description' => 'Checks if admin API is working',
                'responses' => [
                    '200' => [
                        'description' => 'Successful response',
                        'content' => [
                            'application/json' => [
                                'example' => [
                                    'success' => true,
                                    'message' => 'Admin API is working!'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        '/admin/get_applications' => [
            'get' => [
                'summary' => 'Get Partner Applications',
                'description' => 'Retrieves all partner applications',
                'parameters' => [
                    [
                        'name' => 'limit',
                        'in' => 'query',
                        'schema' => ['type' => 'integer', 'default' => 100]
                    ],
                    [
                        'name' => 'offset',
                        'in' => 'query',
                        'schema' => ['type' => 'integer', 'default' => 0]
                    ],
                    [
                        'name' => 'status',
                        'in' => 'query',
                        'schema' => ['type' => 'string', 'enum' => ['pending', 'approved', 'rejected']]
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Successful response',
                        'content' => [
                            'application/json' => [
                                'example' => [
                                    'success' => true,
                                    'data' => [],
                                    'total' => 3
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        '/admin/get_partners' => [
            'get' => [
                'summary' => 'Get All Partners',
                'description' => 'Retrieves all partners',
                'parameters' => [
                    [
                        'name' => 'limit',
                        'in' => 'query',
                        'schema' => ['type' => 'integer', 'default' => 100]
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Successful response',
                        'content' => [
                            'application/json' => [
                                'example' => [
                                    'success' => true,
                                    'data' => ['partners' => []]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        '/admin/get_reviews' => [
            'get' => [
                'summary' => 'Get All Reviews',
                'description' => 'Retrieves all customer reviews',
                'parameters' => [
                    [
                        'name' => 'limit',
                        'in' => 'query',
                        'schema' => ['type' => 'integer', 'default' => 50]
                    ],
                    [
                        'name' => 'min_rating',
                        'in' => 'query',
                        'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 5]
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Successful response',
                        'content' => [
                            'application/json' => [
                                'example' => [
                                    'success' => true,
                                    'data' => [],
                                    'total' => 11,
                                    'stats' => ['average_rating' => 5.0]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        '/admin/get_services' => [
            'get' => [
                'summary' => 'Get All Services',
                'description' => 'Retrieves all services',
                'parameters' => [
                    [
                        'name' => 'limit',
                        'in' => 'query',
                        'schema' => ['type' => 'integer', 'default' => 50]
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Successful response',
                        'content' => [
                            'application/json' => [
                                'example' => [
                                    'success' => true,
                                    'data' => [],
                                    'total' => 26
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        '/admin/get_followups' => [
            'get' => [
                'summary' => 'Get All Follow-ups',
                'description' => 'Retrieves all follow-ups',
                'responses' => [
                    '200' => [
                        'description' => 'Successful response',
                        'content' => [
                            'application/json' => [
                                'example' => [
                                    'success' => true,
                                    'followups' => [],
                                    'total' => 4
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        '/admin/get_commission' => [
            'get' => [
                'summary' => 'Get Commission Records',
                'description' => 'Retrieves all commission records',
                'responses' => [
                    '200' => [
                        'description' => 'Successful response',
                        'content' => [
                            'application/json' => [
                                'example' => [
                                    'success' => true,
                                    'data' => [],
                                    'total' => 1
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        '/admin/get_payouts' => [
            'get' => [
                'summary' => 'Get Payout Records',
                'description' => 'Retrieves all payout records',
                'responses' => [
                    '200' => [
                        'description' => 'Successful response',
                        'content' => [
                            'application/json' => [
                                'example' => [
                                    'success' => true,
                                    'data' => [],
                                    'total' => 0
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ],
    'components' => [
        'securitySchemes' => [
            'sessionAuth' => [
                'type' => 'apiKey',
                'in' => 'cookie',
                'name' => 'PHPSESSID'
            ]
        ],
        'schemas' => [
            'PartnerApplication' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'email' => ['type' => 'string'],
                    'phone' => ['type' => 'string'],
                    'status' => ['type' => 'string', 'enum' => ['pending', 'approved', 'rejected']],
                    'created_at' => ['type' => 'string', 'format' => 'datetime']
                ]
            ],
            'Partner' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'email' => ['type' => 'string'],
                    'commission_rate' => ['type' => 'number'],
                    'status' => ['type' => 'string', 'enum' => ['active', 'inactive']]
                ]
            ],
            'Review' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'rating' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 5],
                    'review_text' => ['type' => 'string']
                ]
            ],
            'Service' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'price' => ['type' => 'number'],
                    'status' => ['type' => 'string']
                ]
            ]
        ]
    ],
    'security' => [
        ['sessionAuth' => []]
    ]
];

echo json_encode($apiDocs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>