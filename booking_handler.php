<?php
session_start();
require "DataBase.php";

header('Content-Type: application/json');

class BookingHandler extends DataBase {
    
    public function createBooking($data) {
        if (!$this->dbConnect()) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }
        
        // Validate required fields
        $required_fields = ['fullName', 'phone', 'address', 'service', 'date', 'time'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Field $field is required"];
            }
        }
        
        // Validate phone number (basic validation)
        if (!preg_match('/^[\+]?[0-9\-\(\)\s]+$/', $data['phone'])) {
            return ['success' => false, 'message' => 'Invalid phone number format'];
        }
        
        // Validate date (should be today or future)
        if (strtotime($data['date']) < strtotime('today')) {
            return ['success' => false, 'message' => 'Booking date cannot be in the past'];
        }
        
        // Get service details
        $service = $this->getServiceByName($data['service']);
        if (!$service) {
            return ['success' => false, 'message' => 'Invalid service selected'];
        }
        
        // Prepare data
        $fullName = $this->prepareData($data['fullName']);
        $phone = $this->prepareData($data['phone']);
        $email = !empty($data['email']) ? $this->prepareData($data['email']) : null;
        $address = $this->prepareData($data['address']);
        $preferredDate = $this->prepareData($data['date']);
        $preferredTime = $this->prepareData($data['time']);
        $notes = !empty($data['notes']) ? $this->prepareData($data['notes']) : null;
        $estimatedCost = $service['price'];
        
        // Get user_id if logged in
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        try {
            // Insert booking
            $query = "INSERT INTO bookings (user_id, service_id, full_name, phone, email, address, 
                      preferred_date, preferred_time, notes, estimated_cost, status) 
                      VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, 'pending') RETURNING id";
            
            $result = pg_query_params($this->connect, $query, [
                $userId, $service['id'], $fullName, $phone, $email, 
                $address, $preferredDate, $preferredTime, $notes, $estimatedCost
            ]);
            
            if ($result && $row = pg_fetch_assoc($result)) {
                $bookingId = $row['id'];
                
                // Send confirmation (you can implement email/SMS here)
                return [
                    'success' => true, 
                    'message' => 'Booking created successfully! We will contact you within 2 hours to confirm.',
                    'booking_id' => $bookingId,
                    'estimated_cost' => $estimatedCost
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to create booking. Please try again.'];
            }
            
        } catch (Exception $e) {
            error_log("Booking creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while processing your booking'];
        }
    }
    
    private function getServiceByName($serviceName) {
        $query = "SELECT id, service_name, price FROM services WHERE service_name = $1 AND is_active = true";
        $result = pg_query_params($this->connect, $query, [$serviceName]);
        
        if ($result && $row = pg_fetch_assoc($result)) {
            return $row;
        }
        return false;
    }
    
    public function getUserBookings($userId) {
        $query = "SELECT b.*, s.service_name, s.category 
                  FROM bookings b 
                  JOIN services s ON b.service_id = s.id 
                  WHERE b.user_id = $1 
                  ORDER BY b.booking_date DESC";
        
        $result = pg_query_params($this->connect, $query, [$userId]);
        $bookings = [];
        
        if ($result) {
            while ($row = pg_fetch_assoc($result)) {
                $bookings[] = $row;
            }
        }
        
        return $bookings;
    }
    
    public function getAllServices() {
        $query = "SELECT * FROM services WHERE is_active = true ORDER BY category, service_name";
        $result = pg_query($this->connect, $query);
        $services = [];
        
        if ($result) {
            while ($row = pg_fetch_assoc($result)) {
                $services[] = $row;
            }
        }
        
        return $services;
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $handler = new BookingHandler();
    
    // Get JSON data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        // Fallback to POST data
        $input = $_POST;
    }
    
    if (isset($input['action'])) {
        switch ($input['action']) {
            case 'create_booking':
                echo json_encode($handler->createBooking($input));
                break;
                
            case 'get_user_bookings':
                if (isset($_SESSION['user_id'])) {
                    $bookings = $handler->getUserBookings($_SESSION['user_id']);
                    echo json_encode(['success' => true, 'bookings' => $bookings]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'User not logged in']);
                }
                break;
                
            case 'get_services':
                $services = $handler->getAllServices();
                echo json_encode(['success' => true, 'services' => $services]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } else {
        // Direct booking submission (non-AJAX)
        $result = $handler->createBooking($_POST);
        echo json_encode($result);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>