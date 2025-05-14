<?php
// Complete updated functions.php

// Common functions for the blood bank system

/**
 * Check if a post contains blood request keywords and match with donors
 */
function checkBloodRequestPost($post_content, $user_id, $conn) {
    // Keywords that might indicate a blood request
    $blood_request_keywords = ['need blood', 'require blood', 'blood required', 'looking for blood', 'urgent blood', 'emergency blood'];
    
    // Blood group patterns to extract from the post
    $blood_group_pattern = '/\b(A|B|AB|O)[+-]\b/i';
    
    $is_blood_request = false;
    
    // Check if the post contains blood request keywords
    foreach ($blood_request_keywords as $keyword) {
        if (stripos($post_content, $keyword) !== false) {
            $is_blood_request = true;
            break;
        }
    }
    
    // If it's a blood request, try to extract blood group and find matches
    if ($is_blood_request) {
        // Extract blood group from the post
        $matches = [];
        preg_match($blood_group_pattern, $post_content, $matches);
        
        $blood_group = null;
        if (!empty($matches)) {
            $blood_group = strtoupper($matches[0]);
        }
        
        // Get user information
        $user_query = "SELECT full_name FROM users WHERE user_id = $user_id";
        $user_result = mysqli_query($conn, $user_query);
        $user_data = mysqli_fetch_assoc($user_result);
        $requester_name = $user_data['full_name'];
        
        // Check blood stock availability
        if ($blood_group) {
            $stock_query = "SELECT quantity_ml FROM blood_stock WHERE blood_group = '$blood_group'";
            $stock_result = mysqli_query($conn, $stock_query);
            
            if (mysqli_num_rows($stock_result) > 0) {
                $stock_data = mysqli_fetch_assoc($stock_result);
                $available_quantity = $stock_data['quantity_ml'];
                
                // Notify the user about blood availability
                if ($available_quantity > 0) {
                    $notification_content = "Good news! We have $available_quantity ml of $blood_group blood available for your request. Please visit the Blood Stock page to make a formal request.";
                    $notification_query = "INSERT INTO notifications (user_id, content) VALUES ($user_id, '$notification_content')";
                    mysqli_query($conn, $notification_query);
                    
                    return true;
                }
            }
        }
        
        // If blood group wasn't specified or no stock available, look for potential donors
        $donor_query = "SELECT dp.user_id, dp.blood_group, u.full_name 
                       FROM donor_profiles dp 
                       JOIN users u ON dp.user_id = u.user_id 
                       JOIN user_roles ur ON dp.user_id = ur.user_id
                       WHERE dp.is_available = 1 
                       AND ur.is_donor = 1";
        
        // If blood group was specified, filter by compatible blood groups
        if ($blood_group) {
            // Define blood compatibility
            $compatible_groups = [];
            switch ($blood_group) {
                case 'A+':
                    $compatible_groups = ['A+', 'A-', 'O+', 'O-'];
                    break;
                case 'A-':
                    $compatible_groups = ['A-', 'O-'];
                    break;
                case 'B+':
                    $compatible_groups = ['B+', 'B-', 'O+', 'O-'];
                    break;
                case 'B-':
                    $compatible_groups = ['B-', 'O-'];
                    break;
                case 'AB+':
                    $compatible_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                    break;
                case 'AB-':
                    $compatible_groups = ['A-', 'B-', 'AB-', 'O-'];
                    break;
                case 'O+':
                    $compatible_groups = ['O+', 'O-'];
                    break;
                case 'O-':
                    $compatible_groups = ['O-'];
                    break;
            }
            
            if (!empty($compatible_groups)) {
                $compatible_groups_str = "'" . implode("','", $compatible_groups) . "'";
                $donor_query .= " AND dp.blood_group IN ($compatible_groups_str)";
            }
        }
        
        $donor_query .= " LIMIT 5"; // Limit to 5 potential donors
        $donor_result = mysqli_query($conn, $donor_query);
        
        // If potential donors found, notify the user
        if (mysqli_num_rows($donor_result) > 0) {
            $donors = [];
            while ($donor = mysqli_fetch_assoc($donor_result)) {
                $donors[] = $donor['full_name'] . " (" . $donor['blood_group'] . ")";
                
                // Also notify potential donors about the blood request
                $donor_notification = "$requester_name is looking for " . ($blood_group ? $blood_group . " blood" : "blood") . ". Check the recent posts to help if you can.";
                $donor_notification_query = "INSERT INTO notifications (user_id, content) VALUES ({$donor['user_id']}, '$donor_notification')";
                mysqli_query($conn, $donor_notification_query);
            }
            
            $donors_list = implode(", ", $donors);
            $notification_content = "We found potential donors matching your blood request: $donors_list. You can contact them through the Available Donors page.";
            $notification_query = "INSERT INTO notifications (user_id, content) VALUES ($user_id, '$notification_content')";
            mysqli_query($conn, $notification_query);
            
            return true;
        } else if (!$blood_group) {
            // If no blood group specified and no donors found
            $notification_content = "We noticed you might be looking for blood. Please specify your required blood group to get better matches.";
            $notification_query = "INSERT INTO notifications (user_id, content) VALUES ($user_id, '$notification_content')";
            mysqli_query($conn, $notification_query);
            
            return true;
        } else {
            // If blood group specified but no donors found
            $notification_content = "We currently don't have any donors available for $blood_group blood. Please contact our blood bank directly for emergency assistance.";
            $notification_query = "INSERT INTO notifications (user_id, content) VALUES ($user_id, '$notification_content')";
            mysqli_query($conn, $notification_query);
            
            return true;
        }
    }
    
    return false;
}

/**
 * Check and award badges to a user based on their activity
 */
function checkAndAwardBadges($user_id, $conn) {
    // Get donation count
    $donation_count_query = "SELECT COUNT(*) as donations_count FROM donation_logs WHERE user_id = $user_id";
    $donation_count_result = mysqli_query($conn, $donation_count_query);
    $donation_count = mysqli_fetch_assoc($donation_count_result)['donations_count'];
    
    // Check if user has donor profile
    $profile_query = "SELECT COUNT(*) as has_profile FROM donor_profiles WHERE user_id = $user_id";
    $profile_result = mysqli_query($conn, $profile_query);
    $has_donor_profile = mysqli_fetch_assoc($profile_result)['has_profile'] > 0 ? 1 : 0;
    
    // Check emergency responses
    $emergency_query = "SELECT COUNT(*) as emergency_count FROM blood_request_logs 
                      WHERE donor_id = $user_id 
                      AND request_date >= (SELECT COALESCE(MAX(emergency_date), '1970-01-01') 
                                         FROM emergency_logs 
                                         WHERE emergency_date <= blood_request_logs.request_date)";
    $emergency_result = mysqli_query($conn, $emergency_query);
    $emergency_responses = mysqli_fetch_assoc($emergency_result)['emergency_count'];
    
    // Get all badges
    $badges_query = "SELECT * FROM badges";
    $badges_result = mysqli_query($conn, $badges_query);
    
    while ($badge = mysqli_fetch_assoc($badges_result)) {
        $badge_id = $badge['badge_id'];
        $criteria = $badge['badge_criteria'];
        
        // Replace variables in criteria with actual values
        $criteria = str_replace('donations_count', $donation_count, $criteria);
        $criteria = str_replace('has_donor_profile', $has_donor_profile, $criteria);
        $criteria = str_replace('emergency_responses', $emergency_responses, $criteria);
        
        // Evaluate criteria
        $award_badge = false;
        
        // Create a safe environment to evaluate the criteria
        if (strpos($criteria, '>=') !== false) {
            list($a, $b) = explode('>=', $criteria);
            $a = trim($a);
            $b = trim($b);
            $award_badge = intval($a) >= intval($b);
        } elseif (strpos($criteria, '<=') !== false) {
            list($a, $b) = explode('<=', $criteria);
            $a = trim($a);
            $b = trim($b);
            $award_badge = intval($a) <= intval($b);
        } elseif (strpos($criteria, '>') !== false) {
            list($a, $b) = explode('>', $criteria);
            $a = trim($a);
            $b = trim($b);
            $award_badge = intval($a) > intval($b);
        } elseif (strpos($criteria, '<') !== false) {
            list($a, $b) = explode('<', $criteria);
            $a = trim($a);
            $b = trim($b);
            $award_badge = intval($a) < intval($b);
        } elseif (strpos($criteria, '==') !== false) {
            list($a, $b) = explode('==', $criteria);
            $a = trim($a);
            $b = trim($b);
            $award_badge = intval($a) == intval($b);
        } elseif (strpos($criteria, '!=') !== false) {
            list($a, $b) = explode('!=', $criteria);
            $a = trim($a);
            $b = trim($b);
            $award_badge = intval($a) != intval($b);
        }
        
        if ($award_badge) {
            // Check if user already has this badge
            $check_query = "SELECT * FROM user_badges WHERE user_id = $user_id AND badge_id = $badge_id";
            $check_result = mysqli_query($conn, $check_query);
            
            if (mysqli_num_rows($check_result) === 0) {
                // Award the badge
                $award_query = "INSERT INTO user_badges (user_id, badge_id) VALUES ($user_id, $badge_id)";
                mysqli_query($conn, $award_query);
                
                // Create notification
                $notification_content = "Congratulations! You've earned the '{$badge['badge_name']}' badge!";
                $notification_query = "INSERT INTO notifications (user_id, content) VALUES ($user_id, '$notification_content')";
                mysqli_query($conn, $notification_query);
            }
        }
    }
}

/**
 * Get user's profile picture path
 */
function getUserProfilePicture($user_id, $conn) {
    $picture_query = "SELECT file_path FROM profile_pictures WHERE user_id = $user_id";
    $picture_result = mysqli_query($conn, $picture_query);
    
    if (mysqli_num_rows($picture_result) > 0) {
        $picture_data = mysqli_fetch_assoc($picture_result);
        return $picture_data['file_path'];
    }
    
    return false;
}

/**
 * Get user's badges
 */
function getUserBadges($user_id, $conn, $limit = null) {
    $badges_query = "SELECT b.* FROM badges b
                    JOIN user_badges ub ON b.badge_id = ub.badge_id
                    WHERE ub.user_id = $user_id
                    ORDER BY ub.earned_date DESC";
    
    if ($limit !== null) {
        $badges_query .= " LIMIT $limit";
    }
    
    $badges_result = mysqli_query($conn, $badges_query);
    $badges = [];
    
    if (mysqli_num_rows($badges_result) > 0) {
        while ($badge = mysqli_fetch_assoc($badges_result)) {
            $badges[] = $badge;
        }
    }
    
    return $badges;
}

/**
 * Get user's donation count
 */
function getUserDonationCount($user_id, $conn) {
    $donation_count_query = "SELECT COUNT(*) as donations_count FROM donation_logs WHERE user_id = $user_id";
    $donation_count_result = mysqli_query($conn, $donation_count_query);
    
    return mysqli_fetch_assoc($donation_count_result)['donations_count'];
}

/**
 * Get blood request count for a user
 */
function getUserBloodRequestCount($user_id, $conn) {
    $request_count_query = "SELECT COUNT(*) as requests_count FROM blood_requests WHERE recipient_id = $user_id";
    $request_count_result = mysqli_query($conn, $request_count_query);
    
    return mysqli_fetch_assoc($request_count_result)['requests_count'];
}

/**
 * Get user's unread notifications count
 */
function getUnreadNotificationsCount($user_id, $conn) {
    $notifications_query = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = $user_id AND is_read = 0";
    $notifications_result = mysqli_query($conn, $notifications_query);
    
    return mysqli_fetch_assoc($notifications_result)['unread_count'];
}

/**
 * Get user's unread messages count
 */
function getUnreadMessagesCount($user_id, $conn) {
    $messages_query = "SELECT COUNT(*) as unread_count FROM messages WHERE receiver_id = $user_id AND is_read = 0";
    $messages_result = mysqli_query($conn, $messages_query);
    
    return mysqli_fetch_assoc($messages_result)['unread_count'];
}

/**
 * Format time elapsed since a timestamp
 */
function timeElapsedString($datetime) {
    // Set timezone to Bangladesh timezone
    date_default_timezone_set('Asia/Dhaka');
    
    $now = new DateTime();
    $timestamp = new DateTime($datetime);
    $interval = $timestamp->diff($now);
    
    if ($interval->y > 0) {
        return $interval->y . " year" . ($interval->y > 1 ? "s" : "") . " ago";
    } elseif ($interval->m > 0) {
        return $interval->m . " month" . ($interval->m > 1 ? "s" : "") . " ago";
    } elseif ($interval->d > 0) {
        return $interval->d . " day" . ($interval->d > 1 ? "s" : "") . " ago";
    } elseif ($interval->h > 0) {
        return $interval->h . " hour" . ($interval->h > 1 ? "s" : "") . " ago";
    } elseif ($interval->i > 0) {
        return $interval->i . " minute" . ($interval->i > 1 ? "s" : "") . " ago";
    } else {
        return "Just now";
    }
}

/**
 * Sanitize input
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Check if user is a donor
 */
function isUserDonor($user_id, $conn) {
    $query = "SELECT is_donor FROM user_roles WHERE user_id = $user_id";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        return (bool) mysqli_fetch_assoc($result)['is_donor'];
    }
    
    return false;
}

/**
 * Check if user is a recipient
 */
function isUserRecipient($user_id, $conn) {
    $query = "SELECT is_recipient FROM user_roles WHERE user_id = $user_id";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        return (bool) mysqli_fetch_assoc($result)['is_recipient'];
    }
    
    return false;
}

/**
 * Check if user has donor profile
 */
function hasUserDonorProfile($user_id, $conn) {
    $query = "SELECT profile_id FROM donor_profiles WHERE user_id = $user_id";
    $result = mysqli_query($conn, $query);
    
    return mysqli_num_rows($result) > 0;
}

/**
 * Get post likes count
 */
function getPostLikesCount($post_id, $conn) {
    $query = "SELECT COUNT(*) as likes_count FROM likes WHERE post_id = $post_id";
    $result = mysqli_query($conn, $query);
    
    return mysqli_fetch_assoc($result)['likes_count'];
}

/**
 * Check if user liked a post
 */
function userLikedPost($user_id, $post_id, $conn) {
    $query = "SELECT * FROM likes WHERE user_id = $user_id AND post_id = $post_id";
    $result = mysqli_query($conn, $query);
    
    return mysqli_num_rows($result) > 0;
}

/**
 * Get user's reaction to a post
 */
function getUserPostReaction($user_id, $post_id, $conn) {
    $query = "SELECT reaction_type FROM likes WHERE user_id = $user_id AND post_id = $post_id";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result)['reaction_type'];
    }
    
    return null;
}

/**
 * Check blood type compatibility
 */
function areBloodTypesCompatible($donor_blood_type, $recipient_blood_type) {
    $compatibility_map = [
        'O-' => ['O-', 'O+', 'A-', 'A+', 'B-', 'B+', 'AB-', 'AB+'],
        'O+' => ['O+', 'A+', 'B+', 'AB+'],
        'A-' => ['A-', 'A+', 'AB-', 'AB+'],
        'A+' => ['A+', 'AB+'],
        'B-' => ['B-', 'B+', 'AB-', 'AB+'],
        'B+' => ['B+', 'AB+'],
        'AB-' => ['AB-', 'AB+'],
        'AB+' => ['AB+']
    ];
    
    if (isset($compatibility_map[$donor_blood_type])) {
        return in_array($recipient_blood_type, $compatibility_map[$donor_blood_type]);
    }
    
    return false;
}

/**
 * Record donation log
 */
function recordDonationLog($user_id, $blood_group, $quantity_ml, $date, $conn) {
    $query = "INSERT INTO donation_logs (user_id, blood_group, quantity_ml, donation_date) 
              VALUES ($user_id, '$blood_group', $quantity_ml, '$date')";
    
    return mysqli_query($conn, $query);
}

/**
 * Record blood request log
 */
function recordBloodRequestLog($recipient_id, $donor_id, $blood_group, $quantity_ml, $date, $conn) {
    $donor_id_value = $donor_id ? $donor_id : "NULL";
    
    $query = "INSERT INTO blood_request_logs (recipient_id, donor_id, blood_group, quantity_ml, request_date) 
              VALUES ($recipient_id, $donor_id_value, '$blood_group', $quantity_ml, '$date')";
    
    return mysqli_query($conn, $query);
}

/**
 * Record emergency log
 */
function recordEmergencyLog($user_id, $blood_group, $location, $contact, $details, $conn) {
    $query = "INSERT INTO emergency_logs (user_id, blood_group, location, contact, details) 
              VALUES ($user_id, '$blood_group', '$location', '$contact', '$details')";
    
    return mysqli_query($conn, $query);
}

/**
 * Create notification
 */
function createNotification($user_id, $content, $conn) {
    $content = mysqli_real_escape_string($conn, $content);
    $query = "INSERT INTO notifications (user_id, content) VALUES ($user_id, '$content')";
    
    return mysqli_query($conn, $query);
}

/**
 * Mark notification as read
 */
function markNotificationAsRead($notification_id, $conn) {
    $query = "UPDATE notifications SET is_read = TRUE WHERE notification_id = $notification_id";
    
    return mysqli_query($conn, $query);
}

/**
 * Mark all user notifications as read
 */
function markAllNotificationsAsRead($user_id, $conn) {
    $query = "UPDATE notifications SET is_read = TRUE WHERE user_id = $user_id";
    
    return mysqli_query($conn, $query);
}

/**
 * Check if a string contains HTML
 */
function containsHTML($string) {
    return $string != strip_tags($string);
}

/**
 * Log system events for debugging
 */
function logSystemEvent($event_type, $description, $user_id = null, $conn = null) {
    // Log to file
    $log_file = $_SERVER['DOCUMENT_ROOT'] . '/BloodBankSystem/logs/system_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $user_info = $user_id ? " | User ID: $user_id" : "";
    $log_entry = "[$timestamp] $event_type$user_info | $description\n";
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    
    // If connection is provided, also log to database
    if ($conn) {
        $event_type = mysqli_real_escape_string($conn, $event_type);
        $description = mysqli_real_escape_string($conn, $description);
        $user_id = $user_id ? $user_id : "NULL";
        
        $query = "INSERT INTO system_logs (event_type, description, user_id) 
                 VALUES ('$event_type', '$description', $user_id)";
        mysqli_query($conn, $query);
    }
}

/**
 * Handle database connection errors
 */
function handleDatabaseError($conn, $query = '') {
    $error = mysqli_error($conn);
    $error_no = mysqli_errno($conn);
    
    logSystemEvent('DATABASE_ERROR', "Error $error_no: $error | Query: $query");
    
    // Return user-friendly message
    return "A database error occurred. Please try again later or contact support.";
}