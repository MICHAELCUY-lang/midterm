<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

// Start session
ensure_session_started();

// Check remember me cookie if not logged in
if (!is_logged_in()) {
    check_remember_cookie($pdo);
}

// Get user info if logged in
$current_user = null;
if (is_logged_in()) {
    $current_user = get_user_info($pdo, $_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSIPFix - Media Sosial Kekinian</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/ssipfix/assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/ssipfix/index.php">
                <i class="fas fa-share-alt me-2"></i>SSIPFix
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/ssipfix/index.php">
                            <i class="fas fa-home me-1"></i> Beranda
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (is_logged_in()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" 
                               data-bs-toggle="dropdown" aria-expanded="false">
                                <?php if ($current_user['is_anonymous']): ?>
                                    <i class="fas fa-user-secret me-1"></i> Anonymous
                                <?php else: ?>
                                    <img src="/ssipfix/assets/images/<?php echo $current_user['profile_picture']; ?>" 
                                         class="avatar-sm rounded-circle me-1" alt="Profile">
                                    <?php echo htmlspecialchars($current_user['username']); ?>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <?php if (!$current_user['is_anonymous']): ?>
                                    <li>
                                        <a class="dropdown-item" href="/ssipfix/profile/index.php?id=<?php echo $current_user['user_id']; ?>">
                                            <i class="fas fa-user me-1"></i> Profil
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="/ssipfix/profile/edit.php">
                                            <i class="fas fa-cog me-1"></i> Pengaturan
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                <li>
                                    <a class="dropdown-item" href="/ssipfix/auth/logout.php">
                                        <i class="fas fa-sign-out-alt me-1"></i> Keluar
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/ssipfix/auth/login.php">
                                <i class="fas fa-sign-in-alt me-1"></i> Masuk
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/ssipfix/auth/register.php">
                                <i class="fas fa-user-plus me-1"></i> Daftar
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/ssipfix/auth/anonymous.php">
                                <i class="fas fa-user-secret me-1"></i> Anonymous
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">