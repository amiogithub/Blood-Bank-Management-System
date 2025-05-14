<?php require_once '../includes/header.php'; ?>

<div class="container my-5">
    <?php
    // Display registration success message
    if (isset($_SESSION['registration_success'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Registration Successful!</strong> You can now donate blood.
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>';
        unset($_SESSION['registration_success']);
    }
    
    // Display login success message
    if (isset($_SESSION['login_success'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Login Successful!</strong> Welcome back to the platform.
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>';
        unset($_SESSION['login_success']);
    }
    
    // Display donor registration success message
    if (isset($_SESSION['donor_registered'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Success!</strong> You have been registered as a donor.
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>';
        unset($_SESSION['donor_registered']);
    }
    
    // Display donor registration error message
    if (isset($_SESSION['donor_error'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error!</strong> ' . $_SESSION['donor_error'] . '
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>';
        unset($_SESSION['donor_error']);
    }
    
    // Display donor registration errors
    if (isset($_SESSION['donor_errors']) && is_array($_SESSION['donor_errors'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error!</strong><ul>';
        foreach ($_SESSION['donor_errors'] as $error) {
            echo '<li>' . $error . '</li>';
        }
        echo '</ul>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>';
        unset($_SESSION['donor_errors']);
    }
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
    ?>
    
    <div class="row">
        <div class="col-md-12 text-center mb-5">
            <h1 class="text-danger">Want to Donate Blood</h1>
            <p class="lead">Join our community of blood donors and help save lives. Please login or register to proceed.</p>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-danger text-white">
                    <h4>Login</h4>
                </div>
                <div class="card-body">
                    <form action="login.php" method="post">
                        <input type="hidden" name="redirect" value="donate_blood.php">
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-danger btn-block">Login</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h4>Register</h4>
                </div>
                <div class="card-body">
                    <form action="register.php" method="post">
                        <input type="hidden" name="redirect" value="donate_blood.php">
                        
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" class="form-control" id="reg_email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" class="form-control" id="reg_password" name="password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="contact_number">Contact Number</label>
                            <input type="text" class="form-control" id="contact_number" name="contact_number" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="nid_number">NID Number</label>
                            <input type="text" class="form-control" id="nid_number" name="nid_number" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Home Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-danger btn-block">Register</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php } else { ?>
    <!-- User is logged in -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h2 class="mb-0">Want to Donate Blood</h2>
                </div>
                <div class="card-body">
                    <?php
                    // Check if user is a donor
                    $user_id = $_SESSION['user_id'];
                    $is_donor = isUserDonor($user_id, $conn);
                    $has_donor_profile = hasUserDonorProfile($user_id, $conn);
                    
                    if (!$is_donor || !$has_donor_profile) {
                        // User is not registered as a donor yet
                    ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i> Complete the form below to register as a blood donor and start saving lives.
                        </div>
                        
                        <form action="donor_registration_handler.php" method="post">
                            <div class="form-group">
                                <label for="blood_group">Your Blood Group</label>
                                <select class="form-control" id="blood_group" name="blood_group" required>
                                    <option value="">Select Blood Group</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="last_donation_date">Last Donation Date (if applicable)</label>
                                <input type="date" class="form-control" id="last_donation_date" name="last_donation_date">
                            </div>
                            
                            <div class="form-group">
                                <label for="medical_info">Medical Information (allergies, conditions, etc.)</label>
                                <textarea class="form-control" id="medical_info" name="medical_info" rows="3"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Are you a smoker?</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="smoker" id="smoker_yes" value="yes" required>
                                    <label class="form-check-label" for="smoker_yes">Yes</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="smoker" id="smoker_no" value="no">
                                    <label class="form-check-label" for="smoker_no">No</label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Gender</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="gender" id="gender_male" value="male" required>
                                    <label class="form-check-label" for="gender_male">Male</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="gender" id="gender_female" value="female">
                                    <label class="form-check-label" for="gender_female">Female</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="gender" id="gender_other" value="other">
                                    <label class="form-check-label" for="gender_other">Other</label>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-danger">Register as Donor</button>
                        </form>
                    <?php
                    } else {
                        // User is already a donor, show donation options
                    ?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-body text-center">
                                        <i class="fas fa-hand-holding-heart fa-4x text-danger mb-3"></i>
                                        <h4>Donate Blood</h4>
                                        <p>Make a blood donation request to donate at our blood bank.</p>
                                        <a href="donation_request.php" class="btn btn-danger">Donate Blood</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-body text-center">
                                        <i class="fas fa-user-edit fa-4x text-danger mb-3"></i>
                                        <h4>Manage Donor Profile</h4>
                                        <p>Update your donor profile information.</p>
                                        <a href="donor_profile.php" class="btn btn-danger">Edit Profile</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-body text-center">
                                        <i class="fas fa-comments fa-4x text-danger mb-3"></i>
                                        <h4>Post About Donation</h4>
                                        <p>Share with the community that you're willing to donate blood.</p>
                                        <a href="dashboard.php" class="btn btn-danger">Make Post</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-body text-center">
                                        <i class="fas fa-history fa-4x text-danger mb-3"></i>
                                        <h4>View Donation History</h4>
                                        <p>Check your past donations and upcoming donation eligibility.</p>
                                        <a href="donation_history.php" class="btn btn-danger">View History</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
            
            <!-- Recent Donation Activity -->
            <div class="card mt-4">
                <div class="card-header bg-danger text-white">
                    <h3 class="mb-0">Recent Donation Activity</h3>
                </div>
                <div class="card-body">
                    <?php
                    // Get recent donation logs
                    $donation_query = "SELECT dl.*, u.full_name 
                                       FROM donation_logs dl 
                                       JOIN users u ON dl.user_id = u.user_id 
                                       ORDER BY dl.donation_date DESC 
                                       LIMIT 5";
                    $donation_result = mysqli_query($conn, $donation_query);
                    
                    if (mysqli_num_rows($donation_result) > 0) {
                    ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Donor</th>
                                        <th>Blood Group</th>
                                        <th>Quantity</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($donation = mysqli_fetch_assoc($donation_result)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($donation['full_name']); ?></td>
                                            <td><span class="badge badge-danger"><?php echo $donation['blood_group']; ?></span></td>
                                            <td><?php echo $donation['quantity_ml']; ?> ml</td>
                                            <td><?php echo date('M j, Y', strtotime($donation['donation_date'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php
                    } else {
                        echo '<div class="alert alert-info">No recent donation activity found.</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
    
    <!-- Why Donate Blood Section -->
    <div class="card mt-5">
        <div class="card-header bg-danger text-white">
            <h3 class="mb-0">Why Donate Blood?</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h4 class="h5 text-danger">Save Lives</h4>
                    <p>A single blood donation can save up to three lives. Every two seconds, someone needs blood.</p>
                    
                    <h4 class="h5 text-danger mt-3">Health Benefits</h4>
                    <p>Regular blood donation reduces the risk of heart attacks and cancer. It also helps in maintaining good health.</p>
                </div>
                <div class="col-md-6">
                    <h4 class="h5 text-danger">Free Health Check-up</h4>
                    <p>Before donating blood, you'll receive a mini health check-up including blood pressure, hemoglobin levels, and pulse rate.</p>
                    
                    <h4 class="h5 text-danger mt-3">Emotional Satisfaction</h4>
                    <p>Donating blood provides a sense of belonging and reduces stress. It's an act of community service.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>