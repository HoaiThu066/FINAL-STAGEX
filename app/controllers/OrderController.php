<?php
namespace App\Controllers;


use App\Models\Booking;
use App\Models\Show;


/**
* OrderController hiển thị tóm tắt đơn hàng sau khi chọn chỗ ngồi và
* Xử lý việc đặt chỗ khi biểu mẫu được gửi. Nó yêu cầu người dùng phải * đăng nhập (khách hàng hoặc nhân viên). Nếu chưa đăng nhập
* người dùng sẽ được chuyển hướng đến trang đăng nhập.
*/


class OrderController extends BaseController
{
    public function summary(): void
    {
        // Đảm bảo có sự lựa chọn
        if (empty($_SESSION['selected_performance']) || empty($_SESSION['selected_seats'])) {
            $this->redirect('index.php');
            return;
        }
        $performanceId = $_SESSION['selected_performance'];
        $seats = $_SESSION['selected_seats'];
        // Tính tổng
        $total = array_sum($seats);
        // Lấy chi tiết suất diễn và vở diễn qua proc
        // Proc performance joins tên rạp và và tất cả các trường đáp ứng. 
        $showModel = new Show();
        $db = \App\Models\Database::connect();
        $perfStmt = $db->prepare('CALL proc_get_performance_by_id(:id)');
        $perfStmt->execute(['id' => $performanceId]);
        $performance = $perfStmt->fetch();
        // Drain
        $perfStmt->closeCursor();
        $show = $showModel->find($performance['show_id']);
        // Lấy tên ghế cho phần tóm tắt thông qua proc_get_seat_labels_by_ids.
// Truyền danh sách các ID ghế được chọn dưới dạng chuỗi phân tách bằng dấu phẩy vào proc đó.
        $seatIds = array_keys($seats);
        $idsStr  = implode(',', $seatIds);
        $seatStmt = $db->prepare('CALL proc_get_seat_labels_by_ids(:ids)');
        $seatStmt->execute(['ids' => $idsStr]);
        $seatRows = [];
        while ($row = $seatStmt->fetch()) {
            $seatRows[(int)$row['seat_id']] = $row['seat_label'];
        }
        $seatStmt->closeCursor();




        // Xử lý việc gửi biểu mẫu để tạo đặt chỗ
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Phải đăng nhập và dữ liệu người dùng được lưu trữ dưới
            //dạng một mảng
            if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
                $_SESSION['error'] = 'Bạn cần đăng nhập để đặt vé.';
            } else {
                // Lấy ID từ phiên đăng nhập
                $userId = (int)($_SESSION['user']['user_id'] ?? 0);
                if ($userId <= 0) {
                    $_SESSION['error'] = 'Thông tin người dùng không hợp lệ. Vui lòng đăng nhập lại.';
                } else {
                    $bookingModel = new Booking();
                    $bookingId = $bookingModel->create($userId, $performanceId, $seats, $total);
                    if ($bookingId) {
                        // Xóa chỗ ngồi đã chọn
                        unset($_SESSION['selected_seats'], $_SESSION['selected_performance']);
                        $_SESSION['current_booking'] = $bookingId;
                        $this->redirect('index.php?pg=pay');
                        return;
                    } else {
                        $_SESSION['error'] = 'Không thể tạo đơn hàng. Vui lòng thử lại.';
                    }
                }
            }
        }
        $this->render('order', [
            'performance' => $performance,
            'show' => $show,
            'seats' => $seats,
            'seatRows' => $seatRows,
            'total' => $total
        ]);
    }
}