<?php
namespace App\Controllers;


use App\Models\Booking;


/*Hiển thị danh sách đặt chỗ của khách hàng hiện tại.*/
class BookingsController extends BaseController
{
    public function index(): void
    {
        // Yêu cầu người dùng đã đăng nhập được lưu trữ dưới dạng mảng
        if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
            $_SESSION['error'] = 'Bạn cần đăng nhập để xem vé.';
            $this->redirect('index.php');
            return;
        }
        $user = $_SESSION['user'];
        $bookingModel = new Booking();
        // Quản trị viên đến bảng điều khiển quản trị
        if (($user['user_type'] ?? '') === 'admin') {
            $this->redirect('../admin/index.php?pg=admin-index');
            return;
        }
        $userId = (int)($user['user_id'] ?? 0);
        // Truy xuất thông tin đặt chỗ chi tiết bao gồm chỗ ngồi
        $allBookings = $bookingModel->forUserDetailed($userId);
        // Tạo model thanh toán để lấy trạng thái thanh toán 
        $paymentModel = new \App\Models\Payment();
        $paidBookings = [];
        foreach ($allBookings as $b) {
            $payment = $paymentModel->findByBooking((int)$b['booking_id']);
            // Chỉ bao gồm các booking đã thanh toán thành công
            if ($payment && ($payment['status'] ?? '') === 'Thành công') {
                $paidBookings[] = $b;
            }
        }
        $this->render('bookings', ['bookings' => $paidBookings]);
    }


    /*Hiển thị chế độ xem chi tiết của một lần đặt chỗ. Bao gồm thông tin về từng ghế,
    mã vé, chi tiết suất diễn và thông tin thanh toán.
    Chỉ chủ sở hữu của lần đặt chỗ mới có thể xem. Nếu lần đặt chỗ
    không tồn tại hoặc thuộc về người dùng khác, người dùng sẽ được chuyển hướng đến danh sách đặt chỗ.
    */
    public function detail(): void
    {
        // Yêu cầu login
        if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
            $_SESSION['error'] = 'Bạn cần đăng nhập để xem vé.';
            $this->redirect('index.php');
            return;
        }
        $user = $_SESSION['user'];
        // Quản trị viên không được truy cập thông tin chi tiết đặt chỗ của khách hàng
        if (($user['user_type'] ?? '') === 'admin') {
            // Chuyển hướng người quản trị đến cổng thông tin quản trị khi cố gắng xem các đặt chỗ của khách hàng
            $this->redirect('../admin/index.php?pg=admin-index');
            return;
        }
        $bookingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($bookingId <= 0) {
            $this->redirect('index.php?pg=bookings');
            return;
        }
        $bookingModel = new Booking();
        $booking = $bookingModel->find($bookingId);
        if (!$booking || $booking['user_id'] != $user['user_id']) {
            $_SESSION['error'] = 'Không tìm thấy đơn hàng.';
            $this->redirect('index.php?pg=bookings');
            return;
        }
 
        $db = \App\Models\Database::connect();
        $perfStmt = $db->prepare('CALL proc_get_performance_by_id(:id)');
        $perfStmt->execute(['id' => $booking['performance_id']]);
        $performance = $perfStmt->fetch();
        // Đọc và bỏ qua phần dữ liệu còn lại trong result set. Sau đó giải phóng tài nguyên của statement đó. 
        // Cho phép PDO tái sử dụng kết nối cho các truy vấn khác.

        $perfStmt->closeCursor();
        // Lấy tiêu đề và thông tin vở diễn
        $showModel = new \App\Models\Show();
        $show = $showModel->find($performance['show_id'] ?? 0);
        // Lấy payment
        $paymentModel = new \App\Models\Payment();
        $payment = $paymentModel->findByBooking($bookingId);
        // Lấy thông tin chi tiết của người dùng để hiển thị tên đầy đủ trong chi tiết booking
        $detailModel = new \App\Models\UserDetail();
        $userDetail = $detailModel->find((int)$booking['user_id']);
        $this->render('booking_detail', [
            'booking'     => $booking,
            'performance' => $performance,
            'show'        => $show,
            'payment'     => $payment,
            'userDetail'  => $userDetail
        ]);
    }
}