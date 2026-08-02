<?php
// report-generator.php - Advanced Report Generator

class ReportGenerator {
    private $pdo;
    
    public function __construct() {
        $this->pdo = new PDO("mysql:host=localhost;dbname=u929623538_cibil", "u929623538_cibilrepair", "Kundanlaxmi@1995");
    }
    
    public function generateReport($type, $filters = [], $format = 'json') {
        $data = [];
        
        switch ($type) {
            case 'sales':
                $data = $this->getSalesReport($filters);
                break;
            case 'leads':
                $data = $this->getLeadsReport($filters);
                break;
            case 'customers':
                $data = $this->getCustomersReport($filters);
                break;
            case 'partners':
                $data = $this->getPartnersReport($filters);
                break;
            case 'credit_analysis':
                $data = $this->getCreditAnalysisReport($filters);
                break;
            case 'disputes':
                $data = $this->getDisputesReport($filters);
                break;
            default:
                return ['error' => 'Invalid report type'];
        }
        
        // Save report
        $report_id = $this->saveReport($type, $data, $format);
        
        return [
            'report_id' => $report_id,
            'data' => $data,
            'format' => $format,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    private function getSalesReport($filters) {
        $query = "SELECT s.*, c.name as customer_name 
                  FROM sales s 
                  LEFT JOIN customers c ON s.customer_id = c.id 
                  WHERE 1=1";
        $params = [];
        
        if (!empty($filters['date_from'])) {
            $query .= " AND s.sale_date >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $query .= " AND s.sale_date <= ?";
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['service'])) {
            $query .= " AND s.service = ?";
            $params[] = $filters['service'];
        }
        if (!empty($filters['status'])) {
            $query .= " AND s.status = ?";
            $params[] = $filters['status'];
        }
        
        $query .= " ORDER BY s.sale_date DESC LIMIT 1000";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        
        // Calculate totals
        $total = array_sum(array_column($data, 'amount'));
        $count = count($data);
        
        return [
            'type' => 'sales',
            'title' => 'Sales Report',
            'filters' => $filters,
            'total_records' => $count,
            'total_amount' => $total,
            'data' => $data
        ];
    }
    
    private function getLeadsReport($filters) {
        $query = "SELECT * FROM leads WHERE 1=1";
        $params = [];
        
        if (!empty($filters['date_from'])) {
            $query .= " AND created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $query .= " AND created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['status'])) {
            $query .= " AND status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['source'])) {
            $query .= " AND source = ?";
            $params[] = $filters['source'];
        }
        
        $query .= " ORDER BY created_at DESC LIMIT 1000";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        
        return [
            'type' => 'leads',
            'title' => 'Leads Report',
            'filters' => $filters,
            'total_records' => count($data),
            'data' => $data
        ];
    }
    
    private function getCustomersReport($filters) {
        $query = "SELECT * FROM customers WHERE 1=1";
        $params = [];
        
        if (!empty($filters['date_from'])) {
            $query .= " AND created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $query .= " AND created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['status'])) {
            $query .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        $query .= " ORDER BY created_at DESC LIMIT 1000";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        
        return [
            'type' => 'customers',
            'title' => 'Customers Report',
            'filters' => $filters,
            'total_records' => count($data),
            'data' => $data
        ];
    }
    
    private function getPartnersReport($filters) {
        $query = "SELECT * FROM partners WHERE 1=1";
        $params = [];
        
        if (!empty($filters['status'])) {
            $query .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        $query .= " ORDER BY total_conversions DESC LIMIT 1000";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        
        return [
            'type' => 'partners',
            'title' => 'Partners Report',
            'filters' => $filters,
            'total_records' => count($data),
            'data' => $data
        ];
    }
    
    private function getCreditAnalysisReport($filters) {
        $query = "SELECT r.*, c.name as client_name 
                  FROM credit_analysis_reports r 
                  LEFT JOIN customers c ON r.client_id = c.id 
                  WHERE 1=1";
        $params = [];
        
        if (!empty($filters['date_from'])) {
            $query .= " AND r.created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $query .= " AND r.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['status'])) {
            $query .= " AND r.status = ?";
            $params[] = $filters['status'];
        }
        
        $query .= " ORDER BY r.created_at DESC LIMIT 1000";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        
        return [
            'type' => 'credit_analysis',
            'title' => 'Credit Analysis Report',
            'filters' => $filters,
            'total_records' => count($data),
            'data' => $data
        ];
    }
    
    private function getDisputesReport($filters) {
        $query = "SELECT * FROM dispute_documents WHERE 1=1";
        $params = [];
        
        if (!empty($filters['date_from'])) {
            $query .= " AND created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $query .= " AND created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['status'])) {
            $query .= " AND status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['issue_type'])) {
            $query .= " AND issue_type = ?";
            $params[] = $filters['issue_type'];
        }
        
        $query .= " ORDER BY created_at DESC LIMIT 1000";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        
        return [
            'type' => 'disputes',
            'title' => 'Disputes Report',
            'filters' => $filters,
            'total_records' => count($data),
            'data' => $data
        ];
    }
    
    private function saveReport($type, $data, $format) {
        $stmt = $this->pdo->prepare("
            INSERT INTO generated_reports (report_name, report_data, file_type) 
            VALUES (?, ?, ?)
        ");
        $name = $data['title'] . ' - ' . date('Y-m-d H:i:s');
        $stmt->execute([$name, json_encode($data), $format]);
        return $this->pdo->lastInsertId();
    }
    
    public function getReport($report_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM generated_reports WHERE id = ?");
        $stmt->execute([$report_id]);
        return $stmt->fetch();
    }
    
    public function getReportList($limit = 20) {
        $stmt = $this->pdo->prepare("SELECT * FROM generated_reports ORDER BY generated_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    public function downloadReport($report_id, $format) {
        $report = $this->getReport($report_id);
        if (!$report) return ['error' => 'Report not found'];
        
        $data = json_decode($report['report_data'], true);
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="report_' . $report_id . '.json"');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
}

// Web interface
$generator = new ReportGenerator();

if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'generate':
            $type = $_GET['type'] ?? 'sales';
            $filters = json_decode(file_get_contents('php://input'), true) ?? [];
            $result = $generator->generateReport($type, $filters, 'json');
            echo json_encode($result);
            break;
            
        case 'list':
            $limit = $_GET['limit'] ?? 20;
            echo json_encode($generator->getReportList($limit));
            break;
            
        case 'get':
            $id = $_GET['id'] ?? 0;
            echo json_encode($generator->getReport($id));
            break;
            
        case 'download':
            $id = $_GET['id'] ?? 0;
            $generator->downloadReport($id, 'json');
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
    exit;
}
?>
