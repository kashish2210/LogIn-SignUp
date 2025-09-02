<?php
session_start();
require "DataBase.php";

// Check if user is logged in
$isLoggedIn = isset($_SESSION['username']);
$username = $isLoggedIn ? $_SESSION['username'] : '';

// Get services from database
$db = new DataBase();
$db->dbConnect();

$services = [];
$servicesQuery = "SELECT * FROM services WHERE is_active = true ORDER BY category, service_name";
$servicesResult = pg_query($db->connect, $servicesQuery);

if ($servicesResult) {
    while ($row = pg_fetch_assoc($servicesResult)) {
        $services[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthCare+ | Home Healthcare Services</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        /* Navigation */
        .navbar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-item a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .nav-item a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .nav-login {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .user-info {
            color: white;
            font-weight: 500;
        }

        .btn {
            padding: 0.7rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            font-size: 0.9rem;
        }

        .btn-outline {
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            background: transparent;
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .btn-primary {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(238, 90, 36, 0.3);
        }

        /* Hero Section */
        .hero {
            margin-top: 80px;
            padding: 4rem 2rem;
            text-align: center;
            color: white;
        }

        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(45deg, #fff, #f8f9ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        /* Services Section */
        .services {
            padding: 4rem 2rem;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            margin: 2rem 0;
        }

        .services-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            color: white;
            margin-bottom: 3rem;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .service-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            padding: 2rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            color: white;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .service-card:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .service-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }

        .service-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .service-card p {
            opacity: 0.9;
            margin-bottom: 1.5rem;
        }

        .service-price {
            font-size: 1.2rem;
            font-weight: bold;
            color: #ff6b6b;
            margin-bottom: 1rem;
        }

        .service-duration {
            font-size: 0.9rem;
            opacity: 0.7;
            margin-bottom: 1rem;
        }

        /* Features Section */
        .features {
            padding: 4rem 2rem;
            background: rgba(255, 255, 255, 0.03);
        }

        .features-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .feature-item {
            text-align: center;
            color: white;
            padding: 1.5rem;
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
        }

        /* Booking Form Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 5% auto;
            padding: 0;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            position: relative;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            background: rgba(255, 255, 255, 0.1);
            padding: 1.5rem;
            border-radius: 20px 20px 0 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-header h2 {
            color: white;
            text-align: center;
            margin: 0;
        }

        .close {
            color: white;
            float: right;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
            position: absolute;
            right: 1rem;
            top: 1rem;
        }

        .close:hover {
            opacity: 0.7;
        }

        .modal-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            color: white;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: none;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.2);
        }

        .form-group select option {
            background: #667eea;
            color: white;
        }

        /* Footer */
        .footer {
            background: rgba(0, 0, 0, 0.3);
            color: white;
            text-align: center;
            padding: 2rem;
            backdrop-filter: blur(20px);
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-section h3 {
            margin-bottom: 1rem;
            color: #ff6b6b;
        }

        .footer-section a {
            color: white;
            text-decoration: none;
            opacity: 0.8;
            display: block;
            margin-bottom: 0.5rem;
        }

        .footer-section a:hover {
            opacity: 1;
            color: #ff6b6b;
        }

        /* Success/Error Messages */
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .alert-success {
            background: rgba(39, 174, 96, 0.2);
            color: #2ecc71;
            border: 1px solid rgba(39, 174, 96, 0.3);
        }

        .alert-error {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.3);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }

            .hero h1 {
                font-size: 2.5rem;
            }

            .services-grid {
                grid-template-columns: 1fr;
            }

            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
        }

        /* Animation Classes */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.8s ease forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .service-card.animate {
            animation: slideInUp 0.8s ease forwards;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="#" class="logo">🏥 HealthCare+</a>
            <ul class="nav-menu">
                <li class="nav-item"><a href="#home">Home</a></li>
                <li class="nav-item"><a href="#services">Services</a></li>
                <li class="nav-item"><a href="#about">About</a></li>
                <li class="nav-item"><a href="#contact">Contact</a></li>
            </ul>
            <div class="nav-login">
                <?php if ($isLoggedIn): ?>
                    <span class="user-info">👋 Welcome, <?php echo htmlspecialchars($username); ?>!</span>
                    <a href="my_bookings.php" class="btn btn-outline">📋 My Bookings</a>
                    <a href="dashboard.php" class="btn btn-outline">📊 Dashboard</a>
                    <a href="logout.php" class="btn btn-primary">🚪 Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">Login</a>
                    <a href="signup.php" class="btn btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-container">
            <h1 class="fade-in">Professional Healthcare at Your Doorstep</h1>
            <p class="fade-in">Expert medical care, therapy, and wellness services delivered to your home with compassion and professionalism.</p>
            <a href="#services" class="btn btn-primary">Book a Service</a>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="services">
        <div class="services-container">
            <h2 class="section-title">Our Services</h2>
            <div class="services-grid">
                <?php if (!empty($services)): ?>
                    <?php foreach ($services as $service): ?>
                        <div class="service-card" onclick="openBookingModal('<?php echo htmlspecialchars($service['service_name']); ?>', <?php echo $service['price']; ?>, <?php echo $service['id']; ?>)">
                            <span class="service-icon">
                                <?php
                                // Icon mapping based on service name
                                $icons = [
                                    'Home Nursing' => '👩‍⚕️',
                                    'Physiotherapy' => '🏃‍♂️',
                                    'Elder Care' => '👴',
                                    'Lab Tests at Home' => '🧪',
                                    'Mental Health Support' => '🧠',
                                    'Vaccination Services' => '💉'
                                ];
                                echo $icons[$service['service_name']] ?? '🏥';
                                ?>
                            </span>
                            <h3><?php echo htmlspecialchars($service['service_name']); ?></h3>
                            <p><?php echo htmlspecialchars($service['description']); ?></p>
                            <div class="service-price">₹<?php echo number_format($service['price'], 0); ?> / <?php echo $service['category'] === 'Elder Care' ? 'day' : ($service['category'] === 'Diagnostic' ? 'test' : 'session'); ?></div>
                            <?php if ($service['duration_minutes']): ?>
                                <div class="service-duration">Duration: <?php echo $service['duration_minutes']; ?> minutes</div>
                            <?php endif; ?>
                            <a href="#" class="btn btn-primary">Book Now</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Fallback if no services in database -->
                    <div class="service-card" onclick="openBookingModal('Home Nursing', 1500, 1)">
                        <span class="service-icon">👩‍⚕️</span>
                        <h3>Home Nursing</h3>
                        <p>Professional nurses providing medical care, medication management, and health monitoring at your home.</p>
                        <div class="service-price">₹1,500 / visit</div>
                        <a href="#" class="btn btn-primary">Book Now</a>
                    </div>

                    <div class="service-card" onclick="openBookingModal('Physiotherapy', 1200, 2)">
                        <span class="service-icon">🏃‍♂️</span>
                        <h3>Physiotherapy</h3>
                        <p>Expert physiotherapists for injury recovery, pain management, and mobility improvement.</p>
                        <div class="service-price">₹1,200 / session</div>
                        <a href="#" class="btn btn-primary">Book Now</a>
                    </div>

                    <div class="service-card" onclick="openBookingModal('Elder Care', 2000, 3)">
                        <span class="service-icon">👴</span>
                        <h3>Elder Care</h3>
                        <p>Comprehensive care for elderly including daily assistance, health monitoring, and companionship.</p>
                        <div class="service-price">₹2,000 / day</div>
                        <a href="#" class="btn btn-primary">Book Now</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="features-container">
            <h2 class="section-title">Why Choose Us?</h2>
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">⚡</div>
                    <h3>Quick Response</h3>
                    <p>Same-day service availability with 2-hour response time for urgent care.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">🏆</div>
                    <h3>Certified Professionals</h3>
                    <p>All our healthcare providers are licensed, experienced, and background-verified.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">💝</div>
                    <h3>Affordable Care</h3>
                    <p>Quality healthcare services at transparent, competitive pricing with no hidden costs.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">🛡️</div>
                    <h3>Safety First</h3>
                    <p>Strict hygiene protocols, safety equipment, and insurance coverage for peace of mind.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Booking Modal -->
    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close">&times;</span>
                <h2 id="modalTitle">Book Service</h2>
            </div>
            <div class="modal-body">
                <div id="alertContainer"></div>
                <form id="bookingForm">
                    <input type="hidden" id="serviceId" name="service_id">
                    
                    <div class="form-group">
                        <label for="fullName">Full Name *</label>
                        <input type="text" id="fullName" name="fullName" placeholder="Enter your full name" required 
                               value="<?php echo $isLoggedIn ? htmlspecialchars($username) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address *</label>
                        <textarea id="address" name="address" rows="3" placeholder="Enter your complete address" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="service">Service *</label>
                        <select id="service" name="service" required>
                            <option value="">Select a service</option>
                            <?php if (!empty($services)): ?>
                                <?php foreach ($services as $service): ?>
                                    <option value="<?php echo htmlspecialchars($service['service_name']); ?>">
                                        <?php echo htmlspecialchars($service['service_name']); ?> - ₹<?php echo number_format($service['price']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="date">Preferred Date *</label>
                        <input type="date" id="date" name="date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="time">Preferred Time *</label>
                        <input type="time" id="time" name="time" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Additional Notes</label>
                        <textarea id="notes" name="notes" rows="3" placeholder="Any special requirements or notes"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <strong style="color: white;">Estimated Cost: ₹<span id="estimatedCost">0</span></strong>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;" id="submitBtn">
                        Confirm Booking
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-grid">
                <div class="footer-section">
                    <h3>HealthCare+</h3>
                    <p>Professional healthcare services delivered to your doorstep with care and compassion.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <a href="#home">Home</a>
                    <a href="#services">Services</a>
                    <a href="#about">About Us</a>
                    <a href="#contact">Contact</a>
                    <?php if ($isLoggedIn): ?>
                        <a href="my_bookings.php">My Bookings</a>
                    <?php endif; ?>
                </div>
                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <p>📞 +91 98765 43210</p>
                    <p>📧 info@healthcareplus.com</p>
                    <p>📍 Ahmedabad, Gujarat</p>
                </div>
                <div class="footer-section">
                    <h3>Emergency</h3>
                    <p style="color: #ff6b6b; font-size: 1.2rem;">🚨 24/7 Emergency: +91 98765 00000</p>
                </div>
            </div>
            <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem; margin-top: 2rem;">
                <p>&copy; 2025 HealthCare+. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Service prices mapping
        const servicePrices = {
            <?php if (!empty($services)): ?>
                <?php foreach ($services as $service): ?>
                    '<?php echo addslashes($service['service_name']); ?>': <?php echo $service['price']; ?>,
                <?php endforeach; ?>
            <?php endif; ?>
        };

        // Get modal elements
        const modal = document.getElementById('bookingModal');
        const span = document.getElementsByClassName('close')[0];
        const modalTitle = document.getElementById('modalTitle');
        const serviceSelect = document.getElementById('service');
        const estimatedCostSpan = document.getElementById('estimatedCost');
        const serviceIdInput = document.getElementById('serviceId');

        // Open modal function
        function openBookingModal(serviceName = '', price = 0, serviceId = 0) {
            modal.style.display = 'block';
            modalTitle.textContent = `Book ${serviceName}`;
            serviceSelect.value = serviceName;
            serviceIdInput.value = serviceId;
            estimatedCostSpan.textContent = price;
            document.body.style.overflow = 'hidden';
        }

        // Close modal when X is clicked
        span.onclick = function() {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            clearAlert();
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
                clearAlert();
            }
        }

        // Update price when service changes
        serviceSelect.addEventListener('change', function() {
            const selectedService = this.value;
            const price = servicePrices[selectedService] || 0;
            estimatedCostSpan.textContent = price;
        });

        // Set minimum date to today
        document.getElementById('date').min = new Date().toISOString().split('T')[0];

        // Alert functions
        function showAlert(message, type) {
            const alertContainer = document.getElementById('alertContainer');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            alertContainer.innerHTML = '';
            alertContainer.appendChild(alert);
        }

        function clearAlert() {
            document.getElementById('alertContainer').innerHTML = '';
        }

        // Handle form submission
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.textContent;
            
            // Show loading state
            submitBtn.textContent = 'Processing...';
            submitBtn.disabled = true;
            clearAlert();
            
            // Get form data
            const formData = new FormData(this);
            formData.append('action', 'create_booking');
            formData.append('estimatedCost', estimatedCostSpan.textContent);
            
            // Send to booking handler
            fetch('booking_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('🎉 ' + data.message, 'success');
                    this.reset();
                    estimatedCostSpan.textContent = '0';
                    
                    // Close modal after 3 seconds
                    setTimeout(() => {
                        modal.style.display = 'none';
                        document.body.style.overflow = 'auto';
                        clearAlert();
                        
                        // Redirect to bookings page if logged in
                        <?php if ($isLoggedIn): ?>
                            window.location.href = 'my_bookings.php';
                        <?php endif; ?>
                    }, 3000);
                } else {
                    showAlert('❌ ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('❌ An error occurred while processing your booking. Please try again.', 'error');
            })
            .finally(() => {
                // Reset button
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        });

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate');
                }
            });
        }, observerOptions);

        // Observe service cards for animation
        document.querySelectorAll('.service-card').forEach(card => {
            observer.observe(card);
        });

        // Add some interactive effects
        document.querySelectorAll('.service-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-15px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    </script>
</body>
</html>