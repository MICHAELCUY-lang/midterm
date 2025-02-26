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

// Set up pagination variables
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10; // Number of notes per page
$offset = ($page - 1) * $per_page;

// Handle search query
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = '';
$search_params = [];

// Process search query
if (!empty($search)) {
    $search_condition = " AND (title LIKE :search OR content LIKE :search)";
    $search_params[':search'] = "%{$search}%";
}

// Get notes with pagination and search
try {
    // Count total records for pagination
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM community_notes WHERE user_id = :user_id" . $search_condition);
    $count_stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    
    if (!empty($search_params)) {
        foreach ($search_params as $key => $value) {
            $count_stmt->bindValue($key, $value);
        }
    }
    
    $count_stmt->execute();
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $per_page);
    
    // Fetch notes
    $stmt = $pdo->prepare("SELECT * FROM community_notes 
                          WHERE user_id = :user_id" . $search_condition . " 
                          ORDER BY created_at DESC 
                          LIMIT :offset, :per_page");
    
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':per_page', $per_page, PDO::PARAM_INT);
    
    if (!empty($search_params)) {
        foreach ($search_params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    $notes = $stmt->fetchAll();
} catch(PDOException $e) {
    die("ERROR: Could not fetch notes. " . $e->getMessage());
}

// Handle single note view
$single_note = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    try {
        $note_id = intval($_GET['id']);
        $stmt = $pdo->prepare("SELECT * FROM community_notes 
                              WHERE id = :id AND user_id = :user_id");
        $stmt->bindParam(':id', $note_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        $single_note = $stmt->fetch();
        
        if (!$single_note) {
            $error_message = "Note not found or you don't have permission to view it.";
        }
    } catch(PDOException $e) {
        die("ERROR: Could not fetch note details. " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Notes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <h1>Your Notes</h1>
            </div>
            <div class="col-md-4 text-end">
                <a href="create.php" class="btn btn-primary">Create New Note</a>
            </div>
        </div>
        
        <!-- Search Form -->
        <div class="row mb-4">
            <div class="col-md-6 offset-md-3">
                <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Search notes..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-outline-primary">Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="view.php" class="btn btn-outline-secondary ms-2">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <div class="row">
            <?php if (isset($single_note) && $single_note): ?>
                <!-- Single Note View -->
                <div class="col-md-8 offset-md-2">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h2><?php echo htmlspecialchars($single_note['title']); ?></h2>
                            <div>
                                <a href="edit.php?id=<?php echo $single_note['id']; ?>" class="btn btn-sm btn-primary me-2">Edit</a>
                                <a href="delete.php?id=<?php echo $single_note['id']; ?>" class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this note?');">Delete</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <?php echo nl2br(htmlspecialchars($single_note['content'])); ?>
                            </div>
                            <div class="text-muted small">
                                <div>Created: <?php echo date('F j, Y, g:i a', strtotime($single_note['created_at'])); ?></div>
                                <?php if ($single_note['updated_at']): ?>
                                    <div>Updated: <?php echo date('F j, Y, g:i a', strtotime($single_note['updated_at'])); ?></div>
                                <?php endif; ?>
                                <div>Status: <?php echo $single_note['is_public'] ? 'Public' : 'Private'; ?></div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="view.php" class="btn btn-secondary">Back to List</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Notes List View -->
                <div class="col-md-12">
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if (empty($notes)): ?>
                        <div class="alert alert-info">
                            <?php echo empty($search) ? 'You have no notes yet.' : 'No notes found matching your search.'; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Created</th>
                                        <th>Updated</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($notes as $note): ?>
                                        <tr>
                                            <td>
                                                <a href="view.php?id=<?php echo $note['id']; ?>">
                                                    <?php echo htmlspecialchars($note['title']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($note['created_at'])); ?></td>
                                            <td>
                                                <?php echo $note['updated_at'] ? date('M j, Y', strtotime($note['updated_at'])) : '-'; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $note['is_public'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                    <?php echo $note['is_public'] ? 'Public' : 'Private'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="edit.php?id=<?php echo $note['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                                <a href="delete.php?id=<?php echo $note['id']; ?>" class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this note?');">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>