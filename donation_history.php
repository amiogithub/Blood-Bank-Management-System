<?php
require_once '../includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's donation history
$history_query = "SELECT * FROM donation_logs WHERE user_id = $user_id ORDER BY donation_date DESC";
$history_result = mysqli_query($conn, $history_query);

// Check if user is a donor and has a profile
$is_donor = isUserDonor($user_id, $conn);
$donor_profile = null;

if ($is_donor) {
    $profile_query = "SELECT * FROM donor_profiles WHERE user_id = $user_id";
    $profile_result = mysqli_query($conn, $profile_query);
    
    if (mysqli_num_rows($profile_result) > 0) {
        $donor_profile = mysqli_fetch_assoc($profile_result);
    }
}

// Calculate eligibility for next donation
$eligible_to_donate = true;
$days_until_eligible = 0;

if ($donor_profile && $donor_profile['last_donation_date']) {
    $last_donation = new DateTime($donor_profile['last_donation_date']);
    $now = new DateTime();
    $diff = $last_donation->diff($now);
    $days_since_donation = $diff->days;
    
    if ($days_since_donation < 56) { // Standard 56-day waiting period
        $eligible_to_donate = false;
        $days_until_eligible = 56 - $days_since_donation;
    }
}

// Calculate total donation volume
$total_query = "SELECT SUM(quantity_ml) as total_donated FROM donation_logs WHERE user_id = $user_id";
$total_result = mysqli_query($conn, $total_query);
$total_donated = mysqli_fetch_assoc($total_result)['total_donated'] ?? 0;

// Get user badges
$badges = getUserBadges($user_id, $conn);
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <div class="card">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><i class="fas fa-history mr-2"></i> My Donation History</h2>
                    <a href="donation_request.php" class="btn btn-light">
                        <i class="fas fa-plus mr-1"></i> New Donation
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($is_donor && $donor_profile): ?>
                        <!-- Donation Status -->
                        <div class="row mb-4">
                            <div class="col-md-4 text-center">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title text-danger">Blood Group</h5>
                                        <h2 class="display-4 text-danger"><?php echo $donor_profile['blood_group']; ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title text-danger">Total Donated</h5>
                                        <h2 class="display-4 text-danger"><?php echo $total_donated; ?> <small>ml</small></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title text-danger">Donation Status</h5>
                                        <?php if ($eligible_to_donate): ?>
                                            <h4 class="text-success">Eligible to Donate</h4>
                                            <a href="donation_request.php" class="btn btn-danger btn-sm mt-2">Donate Now</a>
                                        <?php else: ?>
                                            <h4 class="text-warning">Wait <?php echo $days_until_eligible; ?> Days</h4>
                                            <small class="text-muted">Next eligible date: <?php echo date('M j, Y', strtotime($donor_profile['last_donation_date'] . ' + 56 days')); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($badges)): ?>
                            <!-- Badges Section -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0"><i class="fas fa-award text-danger mr-2"></i> My Badges</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php foreach ($badges as $badge): ?>
                                            <div class="col-md-3 col-6 text-center mb-3">
                                                <div class="badge-icon mb-2">
                                                    <i class="<?php echo $badge['badge_icon']; ?> fa-2x"></i>
                                                </div>
                                                <h6><?php echo $badge['badge_name']; ?></h6>
                                                <small class="text-muted"><?php echo $badge['badge_description']; ?></small>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i> You are not registered as a donor yet. <a href="donate_blood.php" class="alert-link">Register as a donor</a> to start donating blood.
                        </div>
                    <?php endif; ?>
                    
                    <!-- Donation History Table -->
                    <h4 class="border-bottom pb-2 mb-3"><i class="fas fa-list mr-2"></i> Donation Records</h4>
                    
                    <?php if (mysqli_num_rows($history_result) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Date</th>
                                        <th>Blood Group</th>
                                        <th>Quantity</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $counter = 1;
                                    while ($donation = mysqli_fetch_assoc($history_result)): 
                                    ?>
                                        <tr>
                                            <td><?php echo $counter++; ?></td>
                                            <td><?php echo date('F j, Y', strtotime($donation['donation_date'])); ?></td>
                                            <td>
                                                <span class="badge badge-danger"><?php echo $donation['blood_group']; ?></span>
                                            </td>
                                            <td><?php echo $donation['quantity_ml']; ?> ml</td>
                                            <td>
                                                <span class="badge badge-success">Completed</span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i> You haven't made any donations yet.
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <a href="donation_request.php" class="btn btn-danger mr-2">
                            <i class="fas fa-tint mr-1"></i> Donate Blood
                        </a>
                        <a href="dashboard.php" class="btn btn-outline-danger">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Donation Benefits Card -->
            <div class="card mt-4">
                <div class="card-header bg-danger text-white">
                    <h3 class="mb-0">Benefits of Regular Blood Donation</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 col-6 text-center mb-3">
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-heartbeat text-danger fa-2x"></i>
                            </div>
                            <h5>Reduces Heart Disease</h5>
                            <p class="small">Regular donation can reduce risk of heart attacks and strokes.</p>
                        </div>
                        <div class="col-md-3 col-6 text-center mb-3">
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-stethoscope text-danger fa-2x"></i>
                            </div>
                            <h5>Free Health Check</h5>
                            <p class="small">Each donation includes a mini health screening.</p>
                        </div>
                        <div class="col-md-3 col-6 text-center mb-3">
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-weight text-danger fa-2x"></i>
                            </div>
                            <h5>Burns Calories</h5>
                            <p class="small">Donating blood burns approximately 650 calories.</p>
                        </div>
                        <div class="col-md-3 col-6 text-center mb-3">
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-smile-beam text-danger fa-2x"></i>
                            </div>
                            <h5>Improves Wellbeing</h5>
                            <p class="small">Helping others can improve your mental health and wellbeing.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.badge-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background-color: #f8f9fa;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
</style>

<?php require_once '../includes/footer.php'; ?>