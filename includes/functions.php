<?php
// Start session if not already started
function ensure_session_started() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Clean input data
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if user is logged in
function is_logged_in() {
    ensure_session_started();
    return isset($_SESSION['user_id']);
}

// Get current user info
function get_user_info($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

// Upload media function
function upload_media($file, $type) {
    $target_dir = "../assets/uploads/";
    $target_dir .= ($type == 'photo') ? "photos/" : "videos/";
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Check file type
    if ($type == 'photo') {
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_extension, $allowed_types)) {
            return ['success' => false, 'message' => 'Hanya file JPG, JPEG, PNG & GIF yang diperbolehkan.'];
        }
    } else if ($type == 'video') {
        $allowed_types = ['mp4', 'webm', 'ogg'];
        if (!in_array($file_extension, $allowed_types)) {
            return ['success' => false, 'message' => 'Hanya file MP4, WEBM & OGG yang diperbolehkan.'];
        }
    }
    
    // Check file size (limit to 5MB for photos, 20MB for videos)
    $max_size = ($type == 'photo') ? 5 * 1024 * 1024 : 20 * 1024 * 1024;
    if ($file["size"] > $max_size) {
        $max_size_mb = $max_size / (1024 * 1024);
        return ['success' => false, 'message' => "Ukuran file melebihi batas {$max_size_mb}MB."];
    }
    
    // Upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'file_path' => substr($target_file, 3)]; // Remove "../" from path
    } else {
        return ['success' => false, 'message' => 'Terjadi kesalahan saat mengunggah file.'];
    }
}

// Format date
function format_date($date) {
    $timestamp = strtotime($date);
    $current_time = time();
    $diff = $current_time - $timestamp;
    
    if ($diff < 60) {
        return "Baru saja";
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . " menit yang lalu";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . " jam yang lalu";
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . " hari yang lalu";
    } else {
        return date("j F Y", $timestamp);
    }
}

// Get post data
function get_post($pdo, $post_id) {
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.profile_picture, u.is_anonymous
        FROM posts p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.post_id = ?
    ");
    $stmt->execute([$post_id]);
    return $stmt->fetch();
}

// Get comments for a post
function get_comments($pdo, $post_id) {
    $stmt = $pdo->prepare("
        SELECT c.*, u.username, u.profile_picture, u.is_anonymous
        FROM comments c
        JOIN users u ON c.user_id = u.user_id
        WHERE c.post_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$post_id]);
    return $stmt->fetchAll();
}

// Check if user has reacted to post
function get_user_reaction($pdo, $user_id, $post_id) {
    $stmt = $pdo->prepare("
        SELECT type FROM reactions 
        WHERE user_id = ? AND post_id = ?
    ");
    $stmt->execute([$user_id, $post_id]);
    $result = $stmt->fetch();
    return $result ? $result['type'] : null;
}

// Set cookie for auto login
function set_remember_me_cookie($user_id) {
    $token = bin2hex(random_bytes(32));
    $expiry = time() + (30 * 24 * 60 * 60); // 30 days
    
    setcookie('remember_token', $token, $expiry, '/', '', false, true);
    setcookie('user_id', $user_id, $expiry, '/', '', false, true);
    
    // In a real application, you should store this token in the database
    // with the user_id to validate it later
    // For simplicity, we're just using the cookie here
}

// Check remember me cookie
function check_remember_cookie($pdo) {
    if (isset($_COOKIE['remember_token']) && isset($_COOKIE['user_id'])) {
        $user_id = $_COOKIE['user_id'];
        
        // In a real application, you would validate the token against the database
        // For simplicity, we're just using the cookie existence
        
        // Get user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Set session
            ensure_session_started();
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_anonymous'] = $user['is_anonymous'];
            
            return true;
        }
    }
    
    return false;
}
?>