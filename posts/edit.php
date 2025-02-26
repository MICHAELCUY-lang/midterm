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

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: view.php');
    exit();
}

$note_id = intval($_GET['id']);

// Generate CSRF token if not already set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Define variables
$title = $content = '';
$titleErr = $contentErr = '';
$is_public = 0;
$note = null;

// Get note data and check if user is the owner
try {
    $stmt = $pdo->prepare("SELECT * FROM community_notes WHERE id = :id AND user_id = :user_id");
    $stmt->bindParam(':id', $note_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $note = $stmt->fetch();
    
    if (!$note) {
        // Note doesn't exist or doesn't belong to the user
        header('Location: view.php');
        exit();
    }
    
    // Populate form fields with existing data (if not a POST request)
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $title = $note['title'];
        $content = $note['content'];
        $is_public = $note['is_public'];
    }
} catch(PDOException $e) {
    die("ERROR: Could not fetch note details. " . $e->getMessage());
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
    
    // If no errors, update the note
    if (empty($titleErr) && empty($contentErr)) {
        try {
            $stmt = $pdo->prepare("UPDATE community_notes 
                                  SET title = :title, content = :content, is_public = :is_public 
                                  WHERE id = :id AND user_id = :user_id");
            
            $stmt->bindParam(':title', $title, PDO::PARAM_STR);
            $stmt->bindParam(':content', $content, PDO::PARAM_STR);
            $stmt->bindParam(':is_public', $is_public, PDO::PARAM_INT);
            $stmt->bindParam(':id', $note_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            
            $stmt->execute();
            
            // Redirect to the view page after successful update
            header('Location: view.php?id=' . $note_id);
            exit();
        } catch(PDOException $e) {
            die("ERROR: Could not update note. " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Note</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h2>Edit Note</h2>
                    </div>
                    <div class="card-body">
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?id=<?php echo $note_id; ?>">
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
                                <button type="submit" class="btn btn-primary">Update Note</button>
                                <a href="view.php?id=<?php echo $note_id; ?>" class="btn btn-secondary">Cancel</a>
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