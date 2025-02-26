<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Include database connection
require_once 'config/db.php';

// Define variables and set to empty values
$title = $content = '';
$titleErr = $contentErr = '';
$is_public = 0;

// Generate CSRF token if not already set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Process form data when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }
    
    // Validate title
    if (empty($_POST['title'])) {
        $titleErr = 'Title is required';
    } else {
        $title = trim($_POST['title']);
        // Check if title is too long
        if (strlen($title) > 255) {
            $titleErr = 'Title must be less than 255 characters';
        }
    }
    
    // Validate content
    if (empty($_POST['content'])) {
        $contentErr = 'Content is required';
    } else {
        $content = trim($_POST['content']);
    }
    
    // Check if public checkbox is set
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    
    // If no errors, insert data into database
    if (empty($titleErr) && empty($contentErr)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO community_notes (user_id, title, content, is_public) 
                                   VALUES (:user_id, :title, :content, :is_public)");
            
            $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':title', $title, PDO::PARAM_STR);
            $stmt->bindParam(':content', $content, PDO::PARAM_STR);
            $stmt->bindParam(':is_public', $is_public, PDO::PARAM_INT);
            
            $stmt->execute();
            
            // Redirect to view page after successful insertion
            header('Location: view.php');
            exit();
        } catch(PDOException $e) {
            die("ERROR: Could not insert data. " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Note</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h2>Create New Note</h2>
                    </div>
                    <div class="card-body">
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control <?php echo (!empty($titleErr)) ? 'is-invalid' : ''; ?>" 
                                       id="title" name="title" value="<?php echo htmlspecialchars($title); ?>">
                                <span class="invalid-feedback"><?php echo $titleErr; ?></span>
                            </div>
                            
                            <div class="mb-3">
                                <label for="content" class="form-label">Content</label>
                                <textarea class="form-control <?php echo (!empty($contentErr)) ? 'is-invalid' : ''; ?>" 
                                          id="content" name="content" rows="6"><?php echo htmlspecialchars($content); ?></textarea>
                                <span class="invalid-feedback"><?php echo $contentErr; ?></span>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="is_public" name="is_public" 
                                       value="1" <?php echo $is_public ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_public">Make note public</label>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary">Save Note</button>
                                <a href="view.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>