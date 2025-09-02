<?php
session_start();
require "DataBase.php";

// Redirect if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$db = new DataBase();
$db->dbConnect();

// Get user bookings
$username = $_SESSION['username'];
$userQuery = "SELECT id FROM users WHERE username = $1";
$userResult = pg_query_params($db->connect, $userQuery, [$username]);
$user = pg_fetch_assoc($userResult);

$bookings = [];
if ($user) {
    $bookingsQuery = "SELECT b.*, s.service_name, s.category, s.description 
                      FROM bookings b 
                      JOIN services s ON b.service_id = s.id 
                      WHERE b.user_id = $1 
                      ORDER BY b.booking_date DESC";
    
    $bookingsResult = pg_query_params($db->connect, $bookingsQuery, [$user['id']]);
    
    if ($bookingsResult) {
        while ($row = pg_fetch_assoc($bookingsResult)) {
            $bookings[] = $row;
        }
    }
}

// Get status color function
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

function getStatusIcon($status) {
    switch ($status) {
        case 'pending': return '⏳';
        case 'confirmed': return '✅';
        case 'in_progress': return '🔄';
        case 'completed': return '✔️';
        case 'cancelled': return '❌';
        case 'rejected': return '❌';
        default: return '❓';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - HealthCare+</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
            padding: 2rem 1rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.7rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            color: white;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .bookings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .booking-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .booking-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .service-title {
            font-size: 1.3rem;
            font-weight: bold;
        }

        .status-badge {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
            color: white;
        }

        .booking-details {
            line-height: 1.8;
        }

        .booking-details div {
            margin-bottom: 0.5rem;
        }

        .booking-details strong {
            color: #ff6b6b;
            margin-right: 0.5rem;
        }

        .booking-footer {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .booking-date {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .empty-state p {
            opacity: 0.8;
            margin-bottom: 2rem;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #ff6b6b;
        }

        .stat-label {
            margin-top: 0.5rem;
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            .bookings-grid {
                grid-template-columns: 1fr;
            }
            
            .navigation {
                flex-direction: column;
                align-items: stretch;
            }
            
            .nav-links {
                justify-content: center;
            }
            
            .booking-footer {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 My Bookings</h1>
            <p>Manage and track your healthcare service appointments</p>
        </div>

        <div class="navigation">
            <div class="nav-links">
                <a href="index.php" class="btn btn-secondary">🏠 Home</a>
                <a href="dashboard.php" class="btn btn-secondary">📊 Dashboard</a>
            </div>
            <div class="nav-links">
                <a href="index.php#services" class="btn btn-primary">+ New Booking</a>
                <a href="logout.php" class="btn btn-secondary">🚪 Logout</a>
            </div>
        </div>

        <?php if (!empty($bookings)): ?>
            <!-- Statistics -->
            <div class="stats-container">
                <?php
                $stats = array_count_values(array_column($bookings, 'status'));
                $totalBookings = count($bookings);
                ?>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totalBookings; ?></div>
                    <div class="stat-label">Total Bookings</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['pending'] ?? 0; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['confirmed'] ?? 0; ?></div>
                    <div class="stat-label">Confirmed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['completed'] ?? 0; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>

            <!-- Bookings Grid -->
            <div class="bookings-grid">
                <?php foreach ($bookings as $booking): ?>
                    <div class="booking-card">
                        <div class="booking-header">
                            <div class="service-title"><?php echo htmlspecialchars($booking['service_name']); ?></div>
                            <div class="status-badge" style="background-color: <?php echo getStatusColor($booking['status']); ?>;">
                                <?php echo getStatusIcon($booking['status']) . ' ' . ucfirst($booking['status']); ?>
                            </div>
                        </div>

                        <div class="booking-details">
                            <div><strong>📅 Date:</strong> <?php echo date('j M Y', strtotime($booking['preferred_date'])); ?></div>
                            <div><strong>⏰ Time:</strong> <?php echo date('g:i A', strtotime($booking['preferred_time'])); ?></div>
                            <div><strong>💰 Cost:</strong> ₹<?php echo number_format($booking['estimated_cost'], 2); ?></div>
                            <div><strong>📱 Phone:</strong> <?php echo htmlspecialchars($booking['phone']); ?></div>
                            <?php if (!empty($booking['email'])): ?>
                                <div><strong>📧 Email:</strong> <?php echo htmlspecialchars($booking['email']); ?></div>
                            <?php endif; ?>
                            <div><strong>📍 Address:</strong> <?php echo htmlspecialchars(substr($booking['address'], 0, 100)); ?><?php echo strlen($booking['address']) > 100 ? '...' : ''; ?></div>
                            <?php if (!empty($booking['notes'])): ?>
                                <div><strong>📝 Notes:</strong> <?php echo htmlspecialchars($booking['notes']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($booking['assigned_professional'])): ?>
                                <div><strong>👨‍⚕️ Professional:</strong> <?php echo htmlspecialchars($booking['assigned_professional']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="booking-footer">
                            <div class="booking-date">
                                Booked on <?php echo date('j M Y, g:i A', strtotime($booking['booking_date'])); ?>
                            </div>
                            <?php if ($booking['status'] == 'pending'): ?>
                                <button class="btn btn-secondary" onclick="cancelBooking(<?php echo $booking['id']; ?>)">
                                    ❌ Cancel
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <div class="empty-icon">📋</div>
                <h3>No Bookings Yet</h3>
                <p>You haven't made any service bookings yet. Start by booking your first healthcare service!</p>
                <a href="index.php#services" class="btn btn-primary">Book Your First Service</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function cancelBooking(bookingId) {
            if (confirm('Are you sure you want to cancel this booking?')) {
                // Here you would make an AJAX call to cancel the booking
                fetch('booking_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'cancel_booking',
                        booking_id: bookingId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Booking cancelled successfully!');
                        location.reload();
                    } else {
                        alert('Error cancelling booking: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while cancelling the booking.');
                });
            }
        }

        // Auto-refresh page every 5 minutes to update booking status
        setInterval(() => {
            location.reload();
        }, 300000);
    </script>
</body>
</html>