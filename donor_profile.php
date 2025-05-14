<?php 
require_once '../includes/header.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success_message = '';

// Check if donor profile already exists
$check_query = "SELECT * FROM donor_profiles WHERE user_id = $user_id";
$check_result = mysqli_query($conn, $check_query);
$profile_exists = mysqli_num_rows($check_result) > 0;

if ($profile_exists) {
    $profile_data = mysqli_fetch_assoc($check_result);
}

// Check if user has donor role
$role_query = "SELECT * FROM user_roles WHERE user_id = $user_id";
$role_result = mysqli_query($conn, $role_query);

if (mysqli_num_rows($role_result) > 0) {
    $role_data = mysqli_fetch_assoc($role_result);
} else {
    // Create a new entry in user_roles table
    $role_insert = "INSERT INTO user_roles (user_id, is_donor, is_recipient) VALUES ($user_id, FALSE, TRUE)";
    mysqli_query($conn, $role_insert);
    $role_data = ['is_donor' => false, 'is_recipient' => true];
}

// Get profile picture if exists
$picture_query = "SELECT * FROM profile_pictures WHERE user_id = $user_id";
$picture_result = mysqli_query($conn, $picture_query);
$has_profile_picture = mysqli_num_rows($picture_result) > 0;

if ($has_profile_picture) {
    $picture_data = mysqli_fetch_assoc($picture_result);
    $profile_picture_path = $picture_data['file_path'];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $blood_group = $_POST['blood_group'];
    $last_donation_date = !empty($_POST['last_donation_date']) ? $_POST['last_donation_date'] : NULL;
    $medical_info = trim($_POST['medical_info']);
    $smoker = $_POST['smoker'];
    $gender = $_POST['gender'];
    
    // Basic validation
    if (empty($blood_group)) {
        $errors[] = "Blood group is required";
    }
    
    if (empty($smoker)) {
        $errors[] = "Please indicate if you are a smoker";
    }
    
    if (empty($gender)) {
        $errors[] = "Gender is required";
    }
    
    // If no errors, save profile
    if (empty($errors)) {
        if ($profile_exists) {
            // Update existing profile
            $query = "UPDATE donor_profiles SET 
                blood_group = '$blood_group', 
                last_donation_date = " . ($last_donation_date ? "'$last_donation_date'" : "NULL") . ", 
                medical_info = '$medical_info', 
                smoker = '$smoker', 
                gender = '$gender'
                WHERE user_id = $user_id";
        } else {
            // Create new profile
            $query = "INSERT INTO donor_profiles (user_id, blood_group, last_donation_date, medical_info, smoker, gender) 
                    VALUES ($user_id, '$blood_group', " . ($last_donation_date ? "'$last_donation_date'" : "NULL") . ", '$medical_info', '$smoker', '$gender')";
        }
        
        if (mysqli_query($conn, $query)) {
            // Update user role to donor
            $role_update = "UPDATE user_roles SET is_donor = TRUE WHERE user_id = $user_id";
            mysqli_query($conn, $role_update);
            
            // Check and award badges
            checkAndAwardBadges($user_id, $conn);
            
            $success_message = "Profile " . ($profile_exists ? "updated" : "created") . " successfully!";
            
            if (!$profile_exists) {
                // Add notification for new donor profile
                $notification_content = "Your donor profile has been created. You can now make donation requests!";
                $notification_query = "INSERT INTO notifications (user_id, content) VALUES ($user_id, '$notification_content')";
                mysqli_query($conn, $notification_query);
            }
            
            // Refresh profile data
            $check_result = mysqli_query($conn, $check_query);
            $profile_exists = mysqli_num_rows($check_result) > 0;
            if ($profile_exists) {
                $profile_data = mysqli_fetch_assoc($check_result);
            }
        } else {
            $errors[] = "Database error: " . mysqli_error($conn);
        }
    }
}

// Get user badges
$badges_query = "SELECT b.* FROM badges b
                JOIN user_badges ub ON b.badge_id = ub.badge_id
                WHERE ub.user_id = $user_id
                ORDER BY ub.earned_date DESC
                LIMIT 3"; // Show only top 3 badges
$badges_result = mysqli_query($conn, $badges_query);
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="form-container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="text-danger mb-0">Donor Profile</h2>
                    <a href="profile_picture.php" class="btn btn-outline-danger">
                        <i class="fas fa-camera mr-1"></i> Change Profile Picture
                    </a>
                </div>
                
                <!-- Profile Header -->
                <div class="text-center mb-4">
                    <?php if ($has_profile_picture): ?>
                        <img src="<?php echo $profile_picture_path; ?>" alt="Profile Picture" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                    <?php else: ?>
                        <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 150px; height: 150px; font-size: 4rem;">
                            <span><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <h3><?php echo htmlspecialchars($_SESSION['full_name']); ?></h3>
                    
                    <!-- Badges Display -->
                    <?php if (mysqli_num_rows($badges_result) > 0): ?>
                        <div class="badge-container mb-3">
                            <?php while ($badge = mysqli_fetch_assoc($badges_result)): ?>
                                <span class="badge-icon" title="<?php echo $badge['badge_name'] . ': ' . $badge['badge_description']; ?>">
                                    <i class="<?php echo $badge['badge_icon']; ?> fa-lg"></i>
                                </span>
                            <?php endwhile; ?>
                            <a href="profile_picture.php" class="badge-link text-danger">View All Badges</a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" onsubmit="return validateForm('donorProfileForm')" id="donorProfileForm">
                    <div class="form-group">
                        <label for="blood_group">Blood Group</label>
                        <select class="form-control" id="blood_group" name="blood_group" required>
                            <option value="">Select Blood Group</option>
                            <option value="A+" <?php if (isset($profile_data) && $profile_data['blood_group'] == 'A+') echo 'selected'; ?>>A+</option>
                            <option value="A-" <?php if (isset($profile_data) && $profile_data['blood_group'] == 'A-') echo 'selected'; ?>>A-</option>
                            <option value="B+" <?php if (isset($profile_data) && $profile_data['blood_group'] == 'B+') echo 'selected'; ?>>B+</option>
                            <option value="B-" <?php if (isset($profile_data) && $profile_data['blood_group'] == 'B-') echo 'selected'; ?>>B-</option>
                            <option value="AB+" <?php if (isset($profile_data) && $profile_data['blood_group'] == 'AB+') echo 'selected'; ?>>AB+</option>
                            <option value="AB-" <?php if (isset($profile_data) && $profile_data['blood_group'] == 'AB-') echo 'selected'; ?>>AB-</option>
                            <option value="O+" <?php if (isset($profile_data) && $profile_data['blood_group'] == 'O+') echo 'selected'; ?>>O+</option>
                            <option value="O-" <?php if (isset($profile_data) && $profile_data['blood_group'] == 'O-') echo 'selected'; ?>>O-</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_donation_date">Last Donation Date (if applicable)</label>
                        <input type="date" class="form-control" id="last_donation_date" name="last_donation_date" value="<?php echo isset($profile_data) ? $profile_data['last_donation_date'] : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="medical_info">Medical Information</label>
                        <textarea class="form-control" id="medical_info" name="medical_info" rows="3" placeholder="Any relevant medical information, allergies, or conditions"><?php echo isset($profile_data) ? htmlspecialchars($profile_data['medical_info']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Are you a smoker?</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="smoker" id="smoker_yes" value="yes" <?php if (isset($profile_data) && $profile_data['smoker'] == 'yes') echo 'checked'; ?> required>
                            <label class="form-check-label" for="smoker_yes">
                                Yes
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="smoker" id="smoker_no" value="no" <?php if (isset($profile_data) && $profile_data['smoker'] == 'no') echo 'checked'; ?>>
                            <label class="form-check-label" for="smoker_no">
                                No
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Gender</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="gender" id="gender_male" value="male" <?php if (isset($profile_data) && $profile_data['gender'] == 'male') echo 'checked'; ?> required>
                            <label class="form-check-label" for="gender_male">
                                Male
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="gender" id="gender_female" value="female" <?php if (isset($profile_data) && $profile_data['gender'] == 'female') echo 'checked'; ?>>
                            <label class="form-check-label" for="gender_female">
                                Female
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="gender" id="gender_other" value="other" <?php if (isset($profile_data) && $profile_data['gender'] == 'other') echo 'checked'; ?>>
                            <label class="form-check-label" for="gender_other">
                                Other
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-danger btn-block"><?php echo $profile_exists ? 'Update' : 'Create'; ?> Profile</button>
                </form>
                
                <div class="text-center mt-3">
                    <a href="dashboard.php" class="btn btn-outline-danger">Go to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Badge styles */
.badge-container {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.badge-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #f8f9fa;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    cursor: help;
}

.badge-link {
    margin-left: 5px;
    font-size: 0.9rem;
}
</style>

<?php require_once '../includes/footer.php'; ?>