<?php
require_once '../includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success_message = '';

// Process profile picture upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];
    
    // Validate file
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        // Check file type
        if (!in_array($file['type'], $allowed_types)) {
            $errors[] = "Invalid file type. Only JPEG, PNG, and GIF images are allowed.";
        }
        
        // Check file size
        if ($file['size'] > $max_size) {
            $errors[] = "File size exceeds the limit. Maximum file size is 2MB.";
        }
        
        if (empty($errors)) {
            // Create upload directory if it doesn't exist
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/BloodBankSystem/uploads/profile_pictures/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_ext;
            $file_path = $upload_dir . $new_filename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                // Save file info to database
                $file_path_db = '/BloodBankSystem/uploads/profile_pictures/' . $new_filename;
                
                // Check if user already has a profile picture
                $check_query = "SELECT * FROM profile_pictures WHERE user_id = $user_id";
                $check_result = mysqli_query($conn, $check_query);
                
                if (mysqli_num_rows($check_result) > 0) {
                    // Update existing record
                    $update_query = "UPDATE profile_pictures SET file_name = '$new_filename', file_path = '$file_path_db', uploaded_at = CURRENT_TIMESTAMP WHERE user_id = $user_id";
                    if (mysqli_query($conn, $update_query)) {
                        $success_message = "Profile picture updated successfully!";
                    } else {
                        $errors[] = "Database error: " . mysqli_error($conn);
                    }
                } else {
                    // Insert new record
                    $insert_query = "INSERT INTO profile_pictures (user_id, file_name, file_path) VALUES ($user_id, '$new_filename', '$file_path_db')";
                    if (mysqli_query($conn, $insert_query)) {
                        $success_message = "Profile picture uploaded successfully!";
                    } else {
                        $errors[] = "Database error: " . mysqli_error($conn);
                    }
                }
            } else {
                $errors[] = "Failed to upload file.";
            }
        }
    } else {
        // Handle upload errors
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errors[] = "File size exceeds the limit.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $errors[] = "The file was only partially uploaded.";
                break;
            case UPLOAD_ERR_NO_FILE:
                $errors[] = "No file was uploaded.";
                break;
            default:
                $errors[] = "Unknown upload error.";
        }
    }
}

// Get current profile picture
$profile_picture_query = "SELECT * FROM profile_pictures WHERE user_id = $user_id";
$profile_picture_result = mysqli_query($conn, $profile_picture_query);
$has_profile_picture = mysqli_num_rows($profile_picture_result) > 0;

if ($has_profile_picture) {
    $profile_picture = mysqli_fetch_assoc($profile_picture_result);
    $profile_picture_path = $profile_picture['file_path'];
}

// Get user badges
$badges_query = "SELECT b.* FROM badges b
                JOIN user_badges ub ON b.badge_id = ub.badge_id
                WHERE ub.user_id = $user_id
                ORDER BY ub.earned_date DESC";
$badges_result = mysqli_query($conn, $badges_query);
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h2 class="mb-0"><i class="fas fa-user-circle mr-2"></i> Profile Picture</h2>
                </div>
                <div class="card-body">
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
                            <i class="fas fa-check-circle mr-2"></i> <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6 text-center mb-4">
                            <h4 class="mb-3">Current Profile Picture</h4>
                            <?php if ($has_profile_picture): ?>
                                <img src="<?php echo $profile_picture_path; ?>" alt="Profile Picture" class="img-fluid rounded-circle" style="width: 200px; height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center mx-auto" style="width: 200px; height: 200px; font-size: 5rem;">
                                    <span><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6">
                            <h4 class="mb-3">Upload New Picture</h4>
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="profile_picture">Select Image</label>
                                    <input type="file" class="form-control-file" id="profile_picture" name="profile_picture" required>
                                    <small class="form-text text-muted">Max file size: 2MB. Allowed formats: JPEG, PNG, GIF.</small>
                                </div>
                                <button type="submit" class="btn btn-danger">Upload</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Badges Section -->
            <div class="card mt-4">
                <div class="card-header bg-danger text-white">
                    <h2 class="mb-0"><i class="fas fa-award mr-2"></i> My Badges</h2>
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($badges_result) > 0): ?>
                        <div class="row">
                            <?php while ($badge = mysqli_fetch_assoc($badges_result)): ?>
                                <div class="col-md-4 col-6 mb-4 text-center">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <i class="<?php echo $badge['badge_icon']; ?> fa-3x mb-3"></i>
                                            <h5><?php echo $badge['badge_name']; ?></h5>
                                            <p class="small text-muted"><?php echo $badge['badge_description']; ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-medal text-muted fa-3x mb-3"></i>
                            <h4>No Badges Yet</h4>
                            <p>Complete actions like donating blood to earn badges!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <a href="donor_profile.php" class="btn btn-outline-danger mr-2">Edit Donor Profile</a>
                <a href="dashboard.php" class="btn btn-outline-danger">Back to Dashboard</a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>