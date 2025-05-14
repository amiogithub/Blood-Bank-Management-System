<?php 
require_once '../includes/header.php';

// Initialize variables
$email = '';
$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Basic validation
    if (empty($email)) {
        $errors[] = "Email is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    // If no errors, attempt login
    if (empty($errors)) {
        $query = "SELECT * FROM users WHERE email = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Password is correct, start a new session
                session_regenerate_id();
                
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['user_type'] = $user['user_type'];
                
                // Create welcome back notification
                $notification_content = "Welcome back! You've successfully logged in.";
                $notification_query = "INSERT INTO notifications (user_id, content) VALUES (?, ?)";
                $stmt = mysqli_prepare($conn, $notification_query);
                mysqli_stmt_bind_param($stmt, "is", $user['user_id'], $notification_content);
                mysqli_stmt_execute($stmt);
                
                // If admin, redirect to admin dashboard
                if ($user['user_type'] == 'admin') {
                    header("Location: ../admin/dashboard.php");
                } else {
                    // Set login success message
                    $_SESSION['login_success'] = true;
                    
                    // Redirect to dashboard if not specified
                    if (isset($_POST['redirect']) && !empty($_POST['redirect'])) {
                        header("Location: " . $_POST['redirect']);
                    } else {
                        header("Location: dashboard.php");
                    }
                }
                exit();
            } else {
                $errors[] = "Invalid email or password";
            }
        } else {
            $errors[] = "Invalid email or password";
        }
    }
}
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="form-container">
                <h2 class="text-center text-danger mb-4">Login to Your Account</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <?php if (isset($_GET['redirect'])): ?>
                        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect']); ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    
                    <button type="submit" class="btn btn-danger btn-block">Login</button>
                </form>
                
                <div class="text-center mt-3">
                    <p>Don't have an account? <a href="register.php" class="text-danger">Register here</a></p>
                    <p><a href="#" class="text-danger">Forgot your password?</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>