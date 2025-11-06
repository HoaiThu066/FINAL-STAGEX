<?php

require_once __DIR__ . '/../config/config.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

use App\Controllers\HomeController;
use App\Controllers\ShowController;
use App\Controllers\PerformanceController;
use App\Controllers\AuthController;
use App\Controllers\BookingsController;
use App\Controllers\PaymentController;
use App\Controllers\ProfileController;


$pg = $_GET['pg'] ?? '';
switch ($pg) {
    case 'show':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        (new ShowController())->detail($id);
        break;

    case 'shows':
        (new ShowController())->index();
        break;
    case 'select':
        $performanceId = isset($_GET['performance_id']) ? (int)$_GET['performance_id'] : 0;
        (new PerformanceController())->select($performanceId);
        break;
    // Trình tự đặt vé mới loại bỏ trang tóm tắt (order) và trang thanh toán tách biệt (pay).
    // Việc đặt vé và chuyển đến cổng thanh toán VNPay được thực hiện trực tiếp sau khi chọn ghế.
    case 'vnpay_payment':
        (new PaymentController())->vnpayPayment();
        break;
    case 'vnpay_return':
        (new PaymentController())->vnpayReturn();
        break;
    case 'login':
        // Render the login page for GET requests and handle submission on POST requests.  The AuthController
        // will determine whether to show the form or process the credentials.
        (new AuthController())->login();
        break;
    case 'register':
        (new AuthController())->register();
        break;
    case 'logout':
        (new AuthController())->logout();
        break;
    case 'getpassword':
        // Display and handle forgot password workflow
        (new AuthController())->forgot();
        break;
    case 'verify':
        (new AuthController())->verify();
        break;
    case 'bookings':
        (new BookingsController())->index();
        break;
    case 'booking-detail':
        (new BookingsController())->detail();
        break;
    case 'profile':
        // Display and edit the logged in user's personal details
        (new ProfileController())->index();
        break;
    case 'profile-reset-password':
        // Allow logged in user to reset password directly from profile
        (new ProfileController())->resetPassword();
        break;
    case 'about':
        // Render a simple about page
        include __DIR__ . '/../app/views/partials/header.php';
        include __DIR__ . '/../app/views/about.php';
        include __DIR__ . '/../app/views/partials/footer.php';
        break;
    default:
        (new HomeController())->index();
        break;
}