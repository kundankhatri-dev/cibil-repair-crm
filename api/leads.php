<?php
// ============================================================
// CIBIL REPAIR CRM - Leads Unified API
// Endpoint: /api/leads.php
// Method: GET, POST, PUT, DELETE
// ============================================================

require_once __DIR__ . '/init.php';

// Check authentication
requireAuth();

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Route to appropriate handler
switch ($method) {
    case 'GET':
        handleGetLeads();
        break;
    case 'POST':
        handlePostLead();
        break;
    case 'PUT':
        handlePutLead();
        break;
    case 'DELETE':
        handleDeleteLead();
        break;
    default:
        apiResponse(false, 'Method not allowed. Use GET, POST, PUT, or DELETE.', null, 405);
        break;
}

// ============================================================
// HANDLER FUNCTIONS
// ============================================================

function handleGetLeads() {
    try {
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $status = isset($_GET['status']) ? trim($_GET['status']) : '';
        $priority = isset($_GET['priority']) ? trim($_GET['priority']) : '';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

        if ($id > 0) {
            $lead = dbFetchOne("SELECT * FROM leads WHERE id = ?", 'i', $id);
            if ($lead) {
                apiResponse(true, 'Lead found', $lead);
            } else {
                apiResponse(false, 'Lead not found', null, 404);
            }
            return;
        }

        $where = [];
        $params = [];
        $types = '';

        if (!empty($search)) {
            $where[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'sss';
        }

        if (!empty($status) && $status !== 'all') {
            $where[] = "status = ?";
            $params[] = $status;
            $types .= 's';
        }

        if (!empty($priority) && $priority !== 'all') {
            $where[] = "priority = ?";
            $params[] = $priority;
            $types .= 's';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $countResult = dbFetchOne("SELECT COUNT(*) as total FROM leads $whereClause");
        $total = $countResult ? intval($countResult['total']) : 0;

        $leads = dbFetchAll(
            "SELECT * FROM leads $whereClause ORDER BY id DESC LIMIT ? OFFSET ?",
            $types . 'ii',
            ...array_merge($params, [$limit, $offset])
        );

        // Get status counts
        $statusCounts = ['new' => 0, 'contacted' => 0, 'converted' => 0, 'lost' => 0, 'total' => $total];
        foreach (['new', 'contacted', 'converted', 'lost'] as $s) {
            $result = dbFetchOne("SELECT COUNT(*) as count FROM leads WHERE status = ?", 's', $s);
            $statusCounts[$s] = $result ? intval($result['count']) : 0;
        }

        apiResponse(true, 'Leads retrieved successfully', [
            'leads' => $leads,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'priority' => $priority
            ],
            'status_counts' => $statusCounts,
            'generated_at' => date('Y-m-d H:i:s')
        ]);

    } catch (Exception $e) {
        apiResponse(false, 'Error: ' . $e->getMessage(), null, 500);
    }
}

function handlePostLead() {
    // Validate CSRF
    if (!validateCSRF()) {
        apiResponse(false, 'Invalid CSRF token', null, 403);
        return;
    }

    $input = getJsonInput();

    $name = isset($input['name']) ? sanitize($input['name']) : '';
    $phone = isset($input['phone']) ? sanitize($input['phone']) : '';
    $email = isset($input['email']) ? sanitize($input['email']) : '';
    $message = isset($input['message']) ? sanitize($input['message']) : '';
    $service = isset($input['service']) ? sanitize($input['service']) : 'CIBIL Repair';
    $priority = isset($input['priority']) ? sanitize($input['priority']) : 'medium';
    $source = isset($input['source']) ? sanitize($input['source']) : 'Website';
    $amount = isset($input['amount']) ? floatval($input['amount']) : 0;
    $status = isset($input['status']) ? sanitize($input['status']) : 'new';

    if (empty($name) || empty($phone)) {
        apiResponse(false, 'Name and phone are required');
        return;
    }

    if (!validatePhone($phone)) {
        apiResponse(false, 'Invalid phone number. Must be 10 digits');
        return;
    }

    if (!empty($email) && !validateEmail($email)) {
        apiResponse(false, 'Invalid email format');
        return;
    }

    // Check duplicate
    $existing = dbFetchOne("SELECT id FROM leads WHERE phone = ? AND status NOT IN ('converted', 'lost')", 's', $phone);
    if ($existing) {
        apiResponse(false, 'Lead with this phone already exists');
        return;
    }

    $sql = "INSERT INTO leads (name, phone, email, message, service, priority, source, amount, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $affected = dbExecute($sql, 'sssssssss', $name, $phone, $email, $message, $service, $priority, $source, $amount, $status);

    if ($affected > 0) {
        $id = dbLastId();
        $lead = dbFetchOne("SELECT * FROM leads WHERE id = ?", 'i', $id);
        logActivity('Created lead', "Lead ID: $id, Name: $name");
        apiResponse(true, 'Lead created successfully', $lead);
    } else {
        apiResponse(false, 'Failed to create lead', null, 500);
    }
}

function handlePutLead() {
    if (!validateCSRF()) {
        apiResponse(false, 'Invalid CSRF token', null, 403);
        return;
    }

    $input = getJsonInput();
    $id = isset($input['id']) ? intval($input['id']) : 0;

    if (!$id) {
        apiResponse(false, 'Lead ID is required', null, 400);
        return;
    }

    $lead = dbFetchOne("SELECT * FROM leads WHERE id = ?", 'i', $id);
    if (!$lead) {
        apiResponse(false, 'Lead not found', null, 404);
        return;
    }

    $updates = [];
    $params = [];
    $types = '';

    $fields = ['name', 'email', 'phone', 'message', 'service', 'status', 'priority', 'source', 'amount'];
    foreach ($fields as $field) {
        if (isset($input[$field])) {
            $updates[] = "$field = ?";
            $value = sanitize($input[$field]);
            if ($field === 'amount') {
                $value = floatval($value);
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $params[] = $value;
        }
    }

    if (empty($updates)) {
        apiResponse(false, 'No fields to update');
        return;
    }

    $params[] = $id;
    $types .= 'i';

    $sql = "UPDATE leads SET " . implode(', ', $updates) . " WHERE id = ?";
    $affected = dbExecute($sql, $types, ...$params);

    if ($affected >= 0) {
        $lead = dbFetchOne("SELECT * FROM leads WHERE id = ?", 'i', $id);
        logActivity('Updated lead', "Lead ID: $id");
        apiResponse(true, 'Lead updated successfully', $lead);
    } else {
        apiResponse(false, 'Failed to update lead', null, 500);
    }
}

function handleDeleteLead() {
    if (!validateCSRF()) {
        apiResponse(false, 'Invalid CSRF token', null, 403);
        return;
    }

    $input = getJsonInput();
    $id = isset($input['id']) ? intval($input['id']) : 0;

    if (!$id) {
        apiResponse(false, 'Lead ID is required', null, 400);
        return;
    }

    $lead = dbFetchOne("SELECT * FROM leads WHERE id = ?", 'i', $id);
    if (!$lead) {
        apiResponse(false, 'Lead not found', null, 404);
        return;
    }

    if ($lead['status'] === 'converted') {
        apiResponse(false, 'Cannot delete converted leads', null, 409);
        return;
    }

    $affected = dbExecute("DELETE FROM leads WHERE id = ?", 'i', $id);

    if ($affected > 0) {
        logActivity('Deleted lead', "Lead ID: $id, Name: " . $lead['name']);
        apiResponse(true, 'Lead deleted successfully');
    } else {
        apiResponse(false, 'Failed to delete lead', null, 500);
    }
}
?>