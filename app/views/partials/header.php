<?php
// Extract flash messages and clear them so they don't persist.  Use
// distinct variable names to avoid clobbering local variables passed
// from controllers (e.g. $error used on login/register pages).  If
// these variables were called `$success`/`$error` directly they
// would override the controller-supplied values when the header is
// included.  Prefix them with `flash` to avoid such conflicts.
$flashSuccess = $_SESSION['success'] ?? '';
$flashError   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>StageX Demo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body class="bg-dark text-light d-flex flex-column min-vh-100">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom border-secondary-subtle">
        <div class="container">
            <!-- Site logo and title -->
            <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>index.php">
                <!-- Display only the logo.  Remove the separate StageX text and enlarge the logo for better prominence. -->
                <!-- Increase the logo size for improved visibility -->
                <img src="<?= BASE_URL ?>assets/images/logo.svg" alt="StageX" style="height:70px; width:auto;">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMain">
                <?php
                // Determine whether the current page should display a guest
                // header regardless of user type.  Public landing pages such as
                // the home page, the shows listing and individual show detail
                // should not reveal administrative actions even when an admin
                // session exists.  Treat these pages the same as the home
                // page for header purposes.
                $pageParam   = $_GET['pg'] ?? '';
                $isGuestPage = ($pageParam === '' || $pageParam === 'shows' || $pageParam === 'show');
                $currentUser = (isset($_SESSION['user']) && is_array($_SESSION['user'])) ? $_SESSION['user'] : null;
                // Treat unverified users as guests for header purposes.  If a user
                // account has not completed OTP verification (is_verified=0),
                // they should not see personalised navigation until verified.
                if ($currentUser && isset($currentUser['is_verified']) && (int)$currentUser['is_verified'] === 0) {
                    $currentUser = null;
                }
                // Preserve the search keyword from the query string so the search box retains its value after submitting.
                $searchTerm = htmlspecialchars($_GET['keyword'] ?? '');
                /*
                 * Determine which user information to display in the navigation bar.
                 * The behaviour is as follows:
                 *  - On the home page (`pg` not set or empty) guests always see login/register buttons.
                 *    Administrators should also see a guest-like header on the home page so that
                 *    privileged links such as "Quản trị" are hidden until they intentionally
                 *    navigate to the admin area.  Logged-in customers, however, should still see
                 *    their account greeting and "Vé của tôi" link on the home page.
                 *  - On other pages, the header reflects the current user session normally.
                 */
                if ($isGuestPage) {
                    if ($currentUser === null) {
                        // No session -> guest
                        $userForHeader = null;
                    } else {
                        $type = $currentUser['user_type'] ?? '';
                        if ($type === 'admin' || $type === 'staff') {
                            // Admins/staff see guest view on guest pages
                            $userForHeader = null;
                        } else {
                            // Customers retain their greeting on public pages
                            $userForHeader = $currentUser;
                        }
                    }
                } else {
                    // Non-guest pages: show user controls normally
                    $userForHeader = $currentUser;
                }
                ?>
                <!-- Search form: always visible and submits to the shows listing page.  A hidden input ensures the
                     `pg` parameter is set correctly when performing a search. -->
                <form class="d-flex me-auto my-2 my-lg-0" role="search" method="get" action="<?= BASE_URL ?>index.php">
                    <input type="hidden" name="pg" value="shows">
                    <input class="form-control me-2" type="search" name="keyword" placeholder="Tìm vở diễn..." aria-label="Tìm vở diễn" value="<?= $searchTerm ?>">
                    <button class="btn btn-outline-light" type="submit"><i class="bi bi-search"></i></button>
                </form>
                <!-- Navigation links -->
                <ul class="navbar-nav mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>index.php?pg=shows">Vở diễn</a></li>
                    <?php if ($userForHeader && ($userForHeader['user_type'] ?? '') === 'customer'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>index.php?pg=bookings">Vé của tôi</a></li>
                    <?php endif; ?>
                    <?php if ($userForHeader && ($userForHeader['user_type'] ?? '') === 'admin'): ?>
                        <!-- Hide admin portal link in the main navigation to avoid duplicate buttons -->
                    <?php endif; ?>
                </ul>
                <div class="d-flex gap-2">
                    <?php if (!$userForHeader): ?>
                        <!-- Guest users see login/register actions -->
                        <a class="btn btn-outline-light" href="<?= BASE_URL ?>index.php?pg=login">Đăng nhập</a>
                        <a class="btn btn-warning text-dark" href="<?= BASE_URL ?>index.php?pg=register">Đăng ký</a>
                    <?php else: ?>
                        <?php
                        $userType  = $userForHeader['user_type'] ?? '';
                        $greetName = '';
                        if (is_array($userForHeader)) {
                            // Use the account_name for greeting when available.  Fall back to
                            // the email address if no account name is set.  Personal details
                            // (full name) are stored in user_detail and can be edited via the
                            // profile page.
                            $greetName = $userForHeader['account_name'] ?? ($userForHeader['email'] ?? '');
                        }
                        // Determine the navigation user type.  This is the same as the
                        // actual user_type since we have already applied the home page guest logic.
                        $navUserType = $userType;
                        ?>
                        <?php if ($navUserType === 'customer'): ?>
                            <!-- Customers see a greeting linked to their profile and a logout button -->
                            <!-- Customer dropdown for managing profile and logout -->
                            <div class="dropdown">
                                <a class="nav-link dropdown-toggle text-light" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Xin chào, <?= htmlspecialchars($greetName) ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>index.php?pg=profile">Quản lý hồ sơ</a></li>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>index.php?pg=logout">Đăng xuất</a></li>
                                </ul>
                            </div>
                        <?php elseif ($navUserType === 'admin' || $navUserType === 'staff'): ?>
                            <!-- Administrators: provide links to admin portal, profile and logout -->
                            <!-- Link to the separate admin portal.  Use a relative path from the public directory. -->
                            <a class="btn btn-outline-light me-2" href="../admin/index.php?pg=admin-index">Quản trị</a>
                            <a href="../admin/index.php?pg=admin-profile" class="btn btn-outline-light me-2">Hồ sơ của tôi</a>
                            <a class="btn btn-outline-light" href="<?= BASE_URL ?>index.php?pg=logout">Đăng xuất</a>
                        <?php else: ?>
                            <!-- Fallback: unknown user type; offer logout -->
                            <a class="btn btn-outline-light" href="<?= BASE_URL ?>index.php?pg=logout">Đăng xuất</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <main class="container py-4 flex-fill">
        <?php
        // Only display success messages for non-admin users.  When an administrator logs in,
        // a flash message such as "Đăng nhập thành công!" should not show on the public
        // home page.  This avoids confusing the first-time view which should look like
        // a guest experience even if an admin session exists.
        $allowSuccess = true;
        if (isset($currentUser) && is_array($currentUser) && ($currentUser['user_type'] ?? '') === 'admin') {
            $allowSuccess = false;
        }
        ?>
        <?php if ($flashSuccess && $allowSuccess): ?>
            <div class="alert alert-success alert-dismissible" role="alert">
                <?= htmlspecialchars($flashSuccess) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($flashError): ?>
            <div class="alert alert-danger alert-dismissible" role="alert">
                <?= htmlspecialchars($flashError) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php
        // Login modal is removed; a dedicated login page is now used instead.
        ?>