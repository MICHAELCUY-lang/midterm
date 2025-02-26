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

// Check if the note exists and belongs to the current user
try {
    $check_stmt = $pdo->prepare("SELECT id FROM community_notes WHERE id = :id AND user_id = :user_id");
    $check_stmt->bindParam(':id', $note_id, PDO::PARAM_INT);
    $check_stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() === 0) {
        // Note doesn't exist or doesn't belong to the user
        $_SESSION['error_message'] = "Note not found or you don't have permission to delete it.";
        header('Location: view.php');
        exit();
    }
} catch(PDOException $e) {
    die("ERROR: Could not check note ownership. " . $e->getMessage());
}

// Process delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM community_notes WHERE id = :id AND user_id = :user_id");
        $stmt->bindParam(':id', $note_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        
        $_SESSION['success_message'] = "Note successfully deleted.";
        header('Location: view.php');
        exit();
    } catch(PDOException $e) {
        die("ERROR: Could not delete note. " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Note</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h3>Delete Note</h3>
                    </div>
                    <div class="card-body">
                        <p>Are you sure you want to delete this note? This action cannot be undone.</p>
                        
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?id=<?php echo $note_id; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-danger">Yes, Delete Note</button>
                                <a href="view.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm