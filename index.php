<?php require_once 'includes/header.php'; ?>

<!-- Hero Section with Improved Styling -->
<section class="hero-section" style="background-image: url('https://images.unsplash.com/photo-1615461066841-6116e61870c5?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80');">
    <div class="container">
        <div class="hero-content text-center">
            <div class="row">
                <div class="col-md-8 offset-md-2">
                    <h1 class="display-4 mb-4 font-weight-bold text-white animate__animated animate__fadeInDown">Donate Blood, Save Lives</h1>
                    <p class="lead mb-5 text-white animate__animated animate__fadeInUp">Your donation can give someone another chance at life.</p>
                    <div class="hero-buttons animate__animated animate__fadeInUp">
                        <a href="pages/looking_for_blood.php" class="btn btn-light btn-lg mr-3 mb-3 mb-md-0">
                            <i class="fas fa-search mr-2"></i> I Need Blood
                        </a>
                        <a href="pages/donate_blood.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-hand-holding-heart mr-2"></i> Donate Blood
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Blood Stock Stats -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-md-6 offset-md-3 text-center mb-5">
                <h2 class="text-danger">Current Blood Stock</h2>
                <p class="lead text-secondary">Check real-time blood availability in our bank</p>
            </div>
        </div>
        
        <div class="row">
            <?php
            // Get blood stock data
            $stock_query = "SELECT * FROM blood_stock ORDER BY blood_group";
            $stock_result = mysqli_query($conn, $stock_query);
            
            while ($stock = mysqli_fetch_assoc($stock_result)):
                // Determine the status and color
                if ($stock['quantity_ml'] > 1000) {
                    $status = 'Sufficient';
                    $bg_class = 'bg-success';
                } elseif ($stock['quantity_ml'] > 500) {
                    $status = 'Moderate';
                    $bg_class = 'bg-warning';
                } elseif ($stock['quantity_ml'] > 0) {
                    $status = 'Low';
                    $bg_class = 'bg-danger';
                } else {
                    $status = 'Not Available';
                    $bg_class = 'bg-dark';
                }
            ?>
                <div class="col-md-3 col-6 mb-4">
                    <div class="card text-center h-100 border-0 shadow-sm">
                        <div class="card-header <?php echo $bg_class; ?> text-white">
                            <h4 class="mb-0"><?php echo $stock['blood_group']; ?></h4>
                        </div>
                        <div class="card-body d-flex flex-column justify-content-between">
                            <div>
                                <h3 class="text-danger"><?php echo $stock['quantity_ml']; ?> <small>ml</small></h3>
                                <p class="text-secondary mb-0"><?php echo $status; ?></p>
                            </div>
                            <div>
                                <small class="text-secondary">Last updated: <?php echo date('M j, Y', strtotime($stock['last_updated'])); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <div class="text-center mt-4">
            <a href="pages/blood_stock.php" class="btn btn-danger">
                <i class="fas fa-tint mr-2"></i> View Detailed Blood Stock
            </a>
        </div>
    </div>
</section>

<!-- Our Services Section -->
<div class="container my-5">
    <div class="text-center mb-5">
        <h2 class="display-4 text-danger">Our Services</h2>
        <p class="lead text-secondary">We're committed to making blood donation and receiving process simple and accessible.</p>
    </div>
    
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card service-card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                        <i class="fas fa-search fa-2x card-icon"></i>
                    </div>
                    <h3 class="card-title">Blood Availability Search</h3>
                    <p class="card-text text-secondary">Check blood availability across blood groups and find what you need quickly.</p>
                    <a href="pages/blood_stock.php" class="btn btn-outline-danger mt-auto">Search Now</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card service-card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                        <i class="fas fa-hospital fa-2x card-icon"></i>
                    </div>
                    <h3 class="card-title">Blood Bank Directory</h3>
                    <p class="card-text text-secondary">View our extensive list of available donors and their information.</p>
                    <a href="pages/donor_list.php" class="btn btn-outline-danger mt-auto">View Directory</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card service-card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                        <i class="fas fa-calendar-alt fa-2x card-icon"></i>
                    </div>
                    <h3 class="card-title">Blood Donation Camps</h3>
                    <p class="card-text text-secondary">Find upcoming blood donation camps in your area and participate.</p>
                    <a href="#" class="btn btn-outline-danger mt-auto">Find Camps</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card service-card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                        <i class="fas fa-user-plus fa-2x card-icon"></i>
                    </div>
                    <h3 class="card-title">Donor Login</h3>
                    <p class="card-text text-secondary">Already registered as a donor? Login to your account and manage donations.</p>
                    <a href="pages/login.php" class="btn btn-outline-danger mt-auto">Login Now</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card service-card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                        <i class="fas fa-clipboard-list fa-2x card-icon"></i>
                    </div>
                    <h3 class="card-title">Register Voluntary Blood Camp</h3>
                    <p class="card-text text-secondary">Register to organize a voluntary blood donation camp in your area.</p>
                    <a href="pages/register.php" class="btn btn-outline-danger mt-auto">Register Now</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Blood Donation Facts Section -->
<div class="bg-light py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-4 text-danger">Blood Donation Facts</h2>
            <p class="lead text-secondary">Learn why blood donation is so important.</p>
        </div>
        
        <div class="row">
            <div class="col-md-3 col-6 text-center mb-4">
                <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                    <i class="fas fa-tint fa-2x"></i>
                </div>
                <h3 class="h4">1 Donation</h3>
                <p class="text-secondary">Can save up to 3 lives</p>
            </div>
            
            <div class="col-md-3 col-6 text-center mb-4">
                <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                    <i class="fas fa-clock fa-2x"></i>
                </div>
                <h3 class="h4">Every 2 Seconds</h3>
                <p class="text-secondary">Someone needs blood</p>
            </div>
            
            <div class="col-md-3 col-6 text-center mb-4">
                <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                    <i class="fas fa-procedures fa-2x"></i>
                </div>
                <h3 class="h4">1 in 7 People</h3>
                <p class="text-secondary">Entering hospital need blood</p>
            </div>
            
            <div class="col-md-3 col-6 text-center mb-4">
                <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                    <i class="fas fa-heartbeat fa-2x"></i>
                </div>
                <h3 class="h4">43,000 Pints</h3>
                <p class="text-secondary">Of blood are needed daily</p>
            </div>
        </div>
    </div>
</div>

<!-- Awareness Section -->
<div class="container my-5">
    <div class="row">
        <div class="col-md-6 mb-4">
            <h2 class="text-danger">Who Can Donate Blood?</h2>
            <p class="text-dark">Most people can donate blood if they:</p>
            <ul class="text-secondary">
                <li>Are in good health</li>
                <li>Are at least 17 years old</li>
                <li>Weigh at least 110 pounds</li>
                <li>Have not donated blood in the last 56 days</li>
            </ul>
            <p class="text-dark">However, some conditions may mean that you can't donate blood. These include if you:</p>
            <ul class="text-secondary">
                <li>Have been diagnosed with certain illnesses</li>
                <li>Take certain medications</li>
                <li>Have traveled to certain countries</li>
            </ul>
            <a href="#" class="btn btn-danger">Learn More</a>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h3 class="mb-0">Blood Compatibility Chart</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead class="bg-light">
                            <tr>
                                <th>Blood Type</th>
                                <th>Can Donate To</th>
                                <th>Can Receive From</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>A+</td>
                                <td>A+, AB+</td>
                                <td>A+, A-, O+, O-</td>
                            </tr>
                            <tr>
                                <td>A-</td>
                                <td>A+, A-, AB+, AB-</td>
                                <td>A-, O-</td>
                            </tr>
                            <tr>
                                <td>B+</td>
                                <td>B+, AB+</td>
                                <td>B+, B-, O+, O-</td>
                            </tr>
                            <tr>
                                <td>B-</td>
                                <td>B+, B-, AB+, AB-</td>
                                <td>B-, O-</td>
                            </tr>
                            <tr>
                                <td>AB+</td>
                                <td>AB+</td>
                                <td>All Blood Types</td>
                            </tr>
                            <tr>
                                <td>AB-</td>
                                <td>AB+, AB-</td>
                                <td>A-, B-, AB-, O-</td>
                            </tr>
                            <tr>
                                <td>O+</td>
                                <td>A+, B+, AB+, O+</td>
                                <td>O+, O-</td>
                            </tr>
                            <tr>
                                <td>O-</td>
                                <td>All Blood Types</td>
                                <td>O-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Testimonials -->
<div class="bg-light py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-4 text-danger">Testimonials</h2>
            <p class="lead text-secondary">Stories from donors and recipients who have been part of our journey.</p>
        </div>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="mb-3 text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="card-text text-secondary">"I've been donating blood for 5 years now, and the experience at this blood bank has always been excellent. The staff is professional and caring."</p>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center mr-3" style="width: 50px; height: 50px;">
                                <span>NNC</span>
                            </div>
                            <div>
                                <h5 class="mb-0">Najeefa Ma'am</h5>
                                <small class="text-secondary">Regular Donor</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="mb-3 text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="card-text text-secondary">"After my accident, I needed multiple blood transfusions. I'm alive today because people I don't know donated their blood. Now, I donate regularly too."</p>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center mr-3" style="width: 50px; height: 50px;">
                                <span>SP</span>
                            </div>
                            <div>
                                <h5 class="mb-0">Sanjana Prionty</h5>
                                <small class="text-secondary">Blood Recipient & Donor</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="mb-3 text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="card-text text-secondary">"The online system made it so easy to find blood for my emergency surgery. Within hours, we had donors contacting us. Forever grateful!"</p>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center mr-3" style="width: 50px; height: 50px;">
                                <span>ZR</span>
                            </div>
                            <div>
                                <h5 class="mb-0">Zead Raihan</h5>
                                <small class="text-secondary">Family of Recipient</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Emergency Contact Section -->
<section class="py-5 bg-danger text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2><i class="fas fa-ambulance mr-3"></i> Need Blood Urgently?</h2>
                <p class="lead mb-0">Call our 24/7 emergency helpline for immediate assistance</p>
            </div>
            <div class="col-md-4 text-md-right mt-3 mt-md-0">
                <a href="tel:+8801234567890" class="btn btn-lg btn-outline-light">
                    <i class="fas fa-phone-alt mr-2"></i> +880 1234-567890
                </a>
            </div>
        </div>
    </div>
</section>

<?php
// Get recent emergency alerts
$emergency_query = "SELECT p.content, p.post_date, u.full_name 
                   FROM posts p 
                   JOIN users u ON p.user_id = u.user_id 
                   WHERE p.content LIKE '%EMERGENCY BLOOD ALERT%' 
                   ORDER BY p.post_date DESC 
                   LIMIT 1";
$emergency_result = mysqli_query($conn, $emergency_query);

if (mysqli_num_rows($emergency_result) > 0):
    $emergency = mysqli_fetch_assoc($emergency_result);
    $post_date = new DateTime($emergency['post_date']);
    $now = new DateTime();
    $diff = $post_date->diff($now);
    
    // Only show emergencies posted within the last 24 hours
    if ($diff->days < 1):
?>
<!-- Emergency Alert Banner -->
<div class="emergency-alert" style="position: fixed; bottom: 0; left: 0; right: 0; background-color: rgba(220, 53, 69, 0.95); color: white; padding: 15px; z-index: 1000;">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-exclamation-circle fa-lg mr-2"></i>
                <strong>EMERGENCY BLOOD ALERT:</strong> 
                <?php echo htmlspecialchars(str_replace("[EMERGENCY BLOOD ALERT]", "", $emergency['content'])); ?>
                <small class="ml-2">Posted by <?php echo htmlspecialchars($emergency['full_name']); ?>, 
                <?php 
                if ($diff->h > 0) {
                    echo $diff->h . " hour" . ($diff->h > 1 ? "s" : "") . " ago";
                } elseif ($diff->i > 0) {
                    echo $diff->i . " minute" . ($diff->i > 1 ? "s" : "") . " ago";
                } else {
                    echo "Just now";
                }
                ?>
                </small>
            </div>
            <div>
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'donor'): ?>
                    <a href="pages/dashboard.php" class="btn btn-light btn-sm">Respond</a>
                <?php else: ?>
                    <button onclick="this.parentElement.parentElement.parentElement.parentElement.style.display='none'" class="btn btn-light btn-sm">Dismiss</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php 
    endif;
endif; 
?>

<!-- Add animation CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />

<!-- Add a small script to enhance UI interactions -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animate service cards on scroll
    const serviceCards = document.querySelectorAll('.service-card');
    
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.transform = 'translateY(-10px)';
                entry.target.style.boxShadow = '0 10px 20px rgba(0, 0, 0, 0.1)';
                entry.target.style.transition = 'all 0.3s ease';
            } else {
                entry.target.style.transform = 'translateY(0)';
                entry.target.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
                entry.target.style.transition = 'all 0.3s ease';
            }
        });
    }, { threshold: 0.1 });
    
    serviceCards.forEach(card => {
        card.style.transition = 'all 0.3s ease';
        observer.observe(card);
    });
});
</script>

<!-- Custom CSS to improve text readability -->
<style>
    body {
        color: #333;
        font-family: 'Segoe UI', Arial, sans-serif;
    }
    .text-secondary {
        color: #555 !important;
    }
    .card-text {
        line-height: 1.6;
    }
    .lead {
        font-weight: 400;
    }
    .service-card {
        transition: all 0.3s ease;
    }
    .service-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }
    .hero-section {
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        height: 500px;
        position: relative;
    }
    .hero-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1;
    }
    .hero-content {
        position: relative;
        z-index: 2;
        padding-top: 150px;
    }
    .btn-outline-danger:hover {
        background-color: #dc3545;
        color: white;
    }
    .table th, .table td {
        vertical-align: middle;
    }
</style>

<?php require_once 'includes/footer.php'; ?>