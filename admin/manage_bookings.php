<?php
session_start();
require "../DataBase.php";

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$db = new DataBase();
$db->dbConnect();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $response = ['success' => false, 'message' => ''];
    
    switch ($_POST['action']) {
        case 'update_status':
            if (isset($_POST['booking_id']) && isset($_POST['status'])) {
                $bookingId = intval($_POST['booking_id']);
                $status = $db->prepareData($_POST['status']);
                $professional = isset($_POST['professional']) ? $db->prepareData($_POST['professional']) : null;
                
                $query = "UPDATE bookings SET status = $1, assigned_professional = $2, updated_at = CURRENT_TIMESTAMP WHERE id = $3";
                $result = pg_query_params($db->connect, $query, [$status, $professional, $bookingId]);
                
                if ($result) {
                    $response = ['success' => true, 'message' => 'Booking status updated successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to update booking status'];
                }
            }
            break;
    }
    
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
}

// Get filter parameters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$dateFilter = isset($_GET['date']) ? $_GET['date'] : '';
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$whereConditions = [];
$params = [];
$paramCount = 0;

if (!empty($statusFilter)) {
    $paramCount++;
    $whereConditions[] = "b.status = $$paramCount";
    $params[] = $statusFilter;
}

if (!empty($dateFilter)) {
    $paramCount++;
    $whereConditions[] = "b.preferred_date = $$paramCount";
    $params[] = $dateFilter;
}

if (!empty($searchTerm)) {
    $paramCount++;
    $whereConditions[] = "(b.full_name ILIKE $$$paramCount OR b.phone ILIKE $$$paramCount OR s.service_name ILIKE $$$paramCount)";
    $params[] = "%$searchTerm%";
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get bookings
$query = "SELECT b.*, s.service_name, s.category, u.fullname as customer_name, u.email as customer_email
          FROM bookings b 
          LEFT JOIN services s ON b.service_id = s.id 
          LEFT JOIN users u ON b.user_id = u.id 
          $whereClause
          ORDER BY b.booking_date DESC";

$result = pg_query_params($db->connect, str_replace('$$', '$', $query), $params);
$bookings = [];

if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $bookings[] = $row;
    }
}

// Get professionals for assignment
$profQuery = "SELECT * FROM professionals WHERE is_available = true ORDER BY name";
$profResult = pg_query($db->connect, $profQuery);
$professionals = [];
if ($profResult) {
    while ($row = pg_fetch_assoc($profResult)) {
        $professionals[] = $row;
    }
}

function getStatusColor($status) {
    switch ($status) {
        case 'pending': return '#f39c12';
        case 'confirmed': return '#3498db';
        case 'in_progress': return '#9b59b6';
        case 'completed': return '#27ae60';
        case 'cancelled': return '#95a5a6';
        case 'rejected': return '#e74c3c';
        default: return '#34495e';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - HealthCare+ Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            min-height: 100vh;
            color: white;
        }

        .admin-header {
            background: rgba(0, 0, 0, 0.3);
            padding: 1rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #e74c3c;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .nav-link.active {
            background: rgba(231, 76, 60, 0.3);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .filters-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .filter-group select,
        .filter-group input {
            padding: 0.7rem;
            border: none;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 0.9rem;
        }

        .filter-group select option {
            background: #2c3e50;
            color: white;
        }

        .filter-btn {
            padding: 0.7rem 1.5rem;
            border: none;
            border-radius: 8px;
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(231, 76, 60, 0.3);
        }

        .bookings-table {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .table-header {
            background: rgba(231, 76, 60, 0.2);
            padding: 1rem;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr 1fr 150px;
            gap: 1rem;
            font-weight: bold;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .booking-row {
            padding: 1rem;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr 1fr 150px;
            gap: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
            align-items: center;
        }

        .booking-row:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .booking-row:last-child {
            border-bottom: none;
        }

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
            color: white;
            text-align: center;
        }

        .booking-details {
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .booking-details strong {
            color: #e74c3c;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .action-btn {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 6px;
            color: white;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-approve { background: #27ae60; }
        .btn-reject { background: #e74c3c; }
        .btn-complete { background: #9b59b6; }
        .btn-assign { background: #3498db; }

        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            margin: 10% auto;
            padding: 2rem;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-header h3 {
            color: #e74c3c;
        }

        .close {
            color: white;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #e74c3c;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: none;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
        }

        .form-group select option {
            background: #2c3e50;
            color: white;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .btn-save {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-cancel {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            padding: 0.8rem 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            opacity: 0.7;
        }

        .empty-state h3 {
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #e74c3c;
        }

        .stat-label {
            font-size: 0.8rem;
            opacity: 0.8;
            margin-top: 0.2rem;
        }

        @media (max-width: 1024px) {
            .table-header,
            .booking-row {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
            
            .booking-row {
                padding: 1.5rem;
                border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            }
        }

        @media (max-width: 768px) {
            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                margin: 5% auto;
                width: 95%;
            }
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="header-content">
            <div class="logo">🏥 HealthCare+ Admin</div>
            <nav class="nav-links">
                <a href="dashboard.php" class="nav-link">📊 Dashboard</a>
                <a href="manage_bookings.php" class="nav-link active">📋 Bookings</a>
                <a href="manage_services.php" class="nav-link">🛠️ Services</a>
                <a href="manage_users.php" class="nav-link">👥 Users</a>
                <a href="logout.php" class="nav-link">🚪 Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="page-header">
            <h1>📋 Manage Bookings</h1>
            <p>Review and manage all service bookings</p>
        </div>

        <!-- Statistics -->
        <div class="stats-row">
            <?php
            $statusCounts = array_count_values(array_column($bookings, 'status'));
            ?>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($bookings); ?></div>
                <div class="stat-label">Total Shown</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $statusCounts['pending'] ?? 0; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $statusCounts['confirmed'] ?? 0; ?></div>
                <div class="stat-label">Confirmed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $statusCounts['completed'] ?? 0; ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" class="filters-grid">
                <div class="filter-group">
                    <label for="status">Status Filter</label>
                    <select name="status" id="status">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="in_progress" <?php echo $statusFilter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="date">Date Filter</label>
                    <input type="date" name="date" id="date" value="<?php echo htmlspecialchars($dateFilter); ?>">
                </div>
                
                <div class="filter-group">
                    <label for="search">Search</label>
                    <input type="text" name="search" id="search" placeholder="Name, phone, service..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
                
                <div class="filter-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="filter-btn">🔍 Apply Filters</button>
                </div>
            </form>
        </div>

        <!-- Bookings Table -->
        <div class="bookings-table">
            <div class="table-header">
                <div>Customer & Service</div>
                <div>Date & Time</div>
                <div>Contact</div>
                <div>Status</div>
                <div>Amount</div>
                <div>Actions</div>
            </div>

            <?php if (!empty($bookings)): ?>
                <?php foreach ($bookings as $booking): ?>
                    <div class="booking-row" id="booking-<?php echo $booking['id']; ?>">
                        <div class="booking-details">
                            <strong><?php echo htmlspecialchars($booking['customer_name'] ?: $booking['full_name']); ?></strong><br>
                            <span style="opacity: 0.8;"><?php echo htmlspecialchars($booking['service_name']); ?></span><br>
                            <small style="opacity: 0.6;">ID: #<?php echo $booking['id']; ?></small>
                        </div>
                        
                        <div class="booking-details">
                            <strong><?php echo date('j M Y', strtotime($booking['preferred_date'])); ?></strong><br>
                            <span style="opacity: 0.8;"><?php echo date('g:i A', strtotime($booking['preferred_time'])); ?></span><br>
                            <small style="opacity: 0.6;">Booked: <?php echo date('j M', strtotime($booking['booking_date'])); ?></small>
                        </div>
                        
                        <div class="booking-details">
                            <strong><?php echo htmlspecialchars($booking['phone']); ?></strong><br>
                            <?php if (!empty($booking['email'])): ?>
                                <span style="opacity: 0.8;"><?php echo htmlspecialchars($booking['email']); ?></span><br>
                            <?php endif; ?>
                            <?php if (!empty($booking['assigned_professional'])): ?>
                                <small style="color: #3498db;">👨‍⚕️ <?php echo htmlspecialchars($booking['assigned_professional']); ?></small>
                            <?php endif; ?>
                        </div>
                        
                        <div>
                            <div class="status-badge" style="background-color: <?php echo getStatusColor($booking['status']); ?>;">
                                <?php echo ucfirst($booking['status']); ?>
                            </div>
                        </div>
                        
                        <div class="booking-details">
                            <strong>₹<?php echo number_format($booking['estimated_cost']); ?></strong><br>
                            <?php if (!empty($booking['actual_cost'])): ?>
                                <small style="opacity: 0.8;">Actual: ₹<?php echo number_format($booking['actual_cost']); ?></small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="action-buttons">
                            <?php if ($booking['status'] === 'pending'): ?>
                                <button class="action-btn btn-approve" onclick="updateStatus(<?php echo $booking['id']; ?>, 'confirmed')">✅ Approve</button>
                                <button class="action-btn btn-reject" onclick="updateStatus(<?php echo $booking['id']; ?>, 'rejected')">❌ Reject</button>
                                <button class="action-btn btn-assign" onclick="openAssignModal(<?php echo $booking['id']; ?>, '<?php echo htmlspecialchars($booking['service_name']); ?>')">👨‍⚕️ Assign</button>
                            <?php elseif ($booking['status'] === 'confirmed'): ?>
                                <button class="action-btn btn-complete" onclick="updateStatus(<?php echo $booking['id']; ?>, 'in_progress')">🔄 Start</button>
                                <button class="action-btn btn-assign" onclick="openAssignModal(<?php echo $booking['id']; ?>, '<?php echo htmlspecialchars($booking['service_name']); ?>')">👨‍⚕️ Assign</button>
                            <?php elseif ($booking['status'] === 'in_progress'): ?>
                                <button class="action-btn btn-complete" onclick="updateStatus(<?php echo $booking['id']; ?>, 'completed')">✅ Complete</button>
                            <?php endif; ?>
                            <button class="action-btn" onclick="viewDetails(<?php echo $booking['id']; ?>)" style="background: #34495e;">👁️ View</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>📋 No Bookings Found</h3>
                    <p>No bookings match your current filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Assignment Modal -->
    <div id="assignModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>👨‍⚕️ Assign Professional</h3>
                <span class="close">&times;</span>
            </div>
            <form id="assignForm">
                <input type="hidden" id="assignBookingId" name="booking_id">
                
                <div class="form-group">
                    <label for="assignProfessional">Select Professional:</label>
                    <select id="assignProfessional" name="professional" required>
                        <option value="">Choose a professional...</option>
                        <?php foreach ($professionals as $prof): ?>
                            <option value="<?php echo htmlspecialchars($prof['name']); ?>">
                                <?php echo htmlspecialchars($prof['name']); ?> - <?php echo htmlspecialchars($prof['specialization']); ?>
                                (⭐ <?php echo $prof['rating']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="assignStatus">Update Status:</label>
                    <select id="assignStatus" name="status">
                        <option value="confirmed">Confirmed</option>
                        <option value="in_progress">In Progress</option>
                    </select>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeAssignModal()">Cancel</button>
                    <button type="submit" class="btn-save">Assign Professional</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        const modal = document.getElementById('assignModal');
        const span = document.getElementsByClassName('close')[0];

        function openAssignModal(bookingId, serviceName) {
            document.getElementById('assignBookingId').value = bookingId;
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeAssignModal() {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        span.onclick = closeAssignModal;

        window.onclick = function(event) {
            if (event.target == modal) {
                closeAssignModal();
            }
        }

        // Handle assignment form
        document.getElementById('assignForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update_status');
            formData.append('ajax', '1');
            
            fetch('manage_bookings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Professional assigned successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while assigning professional.');
            });
        });

        // Quick status update
        function updateStatus(bookingId, newStatus) {
            if (confirm(`Are you sure you want to change this booking status to "${newStatus}"?`)) {
                const formData = new FormData();
                formData.append('action', 'update_status');
                formData.append('booking_id', bookingId);
                formData.append('status', newStatus);
                formData.append('ajax', '1');
                
                fetch('manage_bookings.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Booking status updated successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating status.');
                });
            }
        }

        function viewDetails(bookingId) {
            // You can implement a detailed view modal here
            alert('Detailed view for booking #' + bookingId + ' will be implemented');
        }

        // Auto-refresh every 2 minutes
        setInterval(() => {
            if (document.hasFocus() && modal.style.display === 'none') {
                location.reload();
            }
        }, 120000);
    </script>
</body>
</html>