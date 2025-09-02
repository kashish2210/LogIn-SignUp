<?php
session_start();
require "../DataBase.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$db = new DataBase();
$db->dbConnect();

// Get dashboard statistics
$stats = [];

// Total bookings
$result = pg_query($db->connect, "SELECT COUNT(*) as total FROM bookings");
$stats['total_bookings'] = pg_fetch_assoc($result)['total'];

// Pending bookings
$result = pg_query($db->connect, "SELECT COUNT(*) as pending FROM bookings WHERE status = 'pending'");
$stats['pending_bookings'] = pg_fetch_assoc($result)['pending'];

// Today's bookings
$result = pg_query($db->connect, "SELECT COUNT(*) as today FROM bookings WHERE preferred_date = CURRENT_DATE");
$stats['today_bookings'] = pg_fetch_assoc($result)['today'];

// Total users
$result = pg_query($db->connect, "SELECT COUNT(*) as users FROM users");
$stats['total_users'] = pg_fetch_assoc($result)['users'];

// Revenue this month
$result = pg_query($db->connect, "SELECT COALESCE(SUM(estimated_cost), 0) as revenue FROM bookings WHERE status = 'completed' AND EXTRACT(MONTH FROM booking_date) = EXTRACT(MONTH FROM CURRENT_DATE)");
$stats['monthly_revenue'] = pg_fetch_assoc($result)['revenue'];

// Recent bookings
$recentBookings = [];
$query = "SELECT b.*, s.service_name, u.fullname as customer_name 
          FROM bookings b 
          LEFT JOIN services s ON b.service_id = s.id 
          LEFT JOIN users u ON b.user_id = u.id 
          ORDER BY b.booking_date DESC 
          LIMIT 10";
$result = pg_query($db->connect, $query);

if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $recentBookings[] = $row;
    }
}

// Status distribution for chart
$statusQuery = "SELECT status, COUNT(*) as count FROM bookings GROUP BY status";
$statusResult = pg_query($db->connect, $statusQuery);
$statusData = [];
while ($row = pg_fetch_assoc($statusResult)) {
    $statusData[$row['status']] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HealthCare+</title>
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

        .admin-info {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .admin-badge {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
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

        .page-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-title h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .page-title p {
            opacity: 0.8;
            font-size: 1.1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .recent-bookings {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .section-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: #e74c3c;
        }

        .booking-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border-left: 4px solid #e74c3c;
            transition: all 0.3s ease;
        }

        .booking-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .booking-service {
            font-weight: bold;
            color: white;
        }

        .booking-status {
            padding: 0.2rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .status-pending { background: #f39c12; color: white; }
        .status-confirmed { background: #3498db; color: white; }
        .status-completed { background: #27ae60; color: white; }
        .status-cancelled { background: #95a5a6; color: white; }

        .booking-details {
            font-size: 0.9rem;
            opacity: 0.8;
            line-height: 1.4;
        }

        .quick-actions {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .action-btn {
            display: block;
            width: 100%;
            padding: 1rem;
            margin-bottom: 1rem;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            text-align: center;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .action-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .action-btn.primary {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
        }

        .action-btn.primary:hover {
            box-shadow: 0 8px 25px rgba(231, 76, 60, 0.3);
        }

        .logout-btn {
            color: #e74c3c;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border: 1px solid #e74c3c;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: #e74c3c;
            color: white;
        }

        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                align-items: stretch;
            }
            
            .nav-links {
                justify-content: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="header-content">
            <div class="logo">🏥 HealthCare+ Admin</div>
            <div class="admin-info">
                <div class="admin-badge">👤 <?php echo htmlspecialchars($_SESSION['admin_username']); ?></div>
                <nav class="nav-links">
                    <a href="dashboard.php" class="nav-link active">📊 Dashboard</a>
                    <a href="manage_bookings.php" class="nav-link">📋 Bookings</a>
                    <a href="manage_services.php" class="nav-link">🛠️ Services</a>
                    <a href="manage_users.php" class="nav-link">👥 Users</a>
                    <a href="reports.php" class="nav-link">📈 Reports</a>
                </nav>
                <a href="logout.php" class="logout-btn">🚪 Logout</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="page-title">
            <h1>📊 Admin Dashboard</h1>
            <p>Manage your healthcare service platform</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-icon">📋</span>
                <div class="stat-number"><?php echo $stats['total_bookings']; ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>

            <div class="stat-card">
                <span class="stat-icon">⏳</span>
                <div class="stat-number"><?php echo $stats['pending_bookings']; ?></div>
                <div class="stat-label">Pending Bookings</div>
            </div>

            <div class="stat-card">
                <span class="stat-icon">📅</span>
                <div class="stat-number"><?php echo $stats['today_bookings']; ?></div>
                <div class="stat-label">Today's Bookings</div>
            </div>

            <div class="stat-card">
                <span class="stat-icon">👥</span>
                <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                <div class="stat-label">Total Users</div>
            </div>

            <div class="stat-card">
                <span class="stat-icon">💰</span>
                <div class="stat-number">₹<?php echo number_format($stats['monthly_revenue']); ?></div>
                <div class="stat-label">Monthly Revenue</div>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Recent Bookings -->
            <div class="recent-bookings">
                <h2 class="section-title">🕒 Recent Bookings</h2>
                <?php if (!empty($recentBookings)): ?>
                    <?php foreach ($recentBookings as $booking): ?>
                        <div class="booking-item">
                            <div class="booking-header">
                                <div class="booking-service"><?php echo htmlspecialchars($booking['service_name']); ?></div>
                                <div class="booking-status status-<?php echo $booking['status']; ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </div>
                            </div>
                            <div class="booking-details">
                                <strong>Customer:</strong> <?php echo htmlspecialchars($booking['customer_name'] ?: $booking['full_name']); ?><br>
                                <strong>Date:</strong> <?php echo date('j M Y', strtotime($booking['preferred_date'])); ?> at <?php echo date('g:i A', strtotime($booking['preferred_time'])); ?><br>
                                <strong>Cost:</strong> ₹<?php echo number_format($booking['estimated_cost']); ?><br>
                                <strong>Phone:</strong> <?php echo htmlspecialchars($booking['phone']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="opacity: 0.7; text-align: center; padding: 2rem;">No bookings found</p>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h2 class="section-title">⚡ Quick Actions</h2>
                <a href="manage_bookings.php" class="action-btn primary">📋 Manage All Bookings</a>
                <a href="manage_bookings.php?status=pending" class="action-btn">⏳ Review Pending Bookings</a>
                <a href="manage_services.php" class="action-btn">🛠️ Manage Services</a>
                <a href="manage_users.php" class="action-btn">👥 View All Users</a>
                <a href="reports.php" class="action-btn">📈 Generate Reports</a>
                <a href="../index.php" class="action-btn" target="_blank">🌐 View Website</a>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh dashboard every 30 seconds
        setInterval(() => {
            // Only refresh if user is still on the page
            if (document.hasFocus()) {
                location.reload();
            }
        }, 30000);

        // Real-time clock
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            const dateString = now.toLocaleDateString();
            
            // You can add a clock element if needed
            console.log(`${dateString} ${timeString}`);
        }

        setInterval(updateClock, 1000);
    </script>
</body>
</html>