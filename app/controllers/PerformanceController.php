<?php
namespace App\Controllers;


use App\Models\Show;
use App\Models\Seat;
use App\Models\SeatCategory;


/**
 * PerformanceController renders the seat selection page for a given
 * performance.  It also handles the form submission to create an
 * order (stored in the session) which will be processed by the
 * OrderController.
 */
class PerformanceController extends BaseController
{
    public function select(int $performanceId): void
    {
        // Expire any pending payments that have exceeded their allowed time.
        // This ensures seats from expired transactions are freed before
        // rendering the seat selection page.  The payment model method
        // will update both payments and associated bookings.
        $paymentExpireModel = new \App\Models\Payment();
        $paymentExpireModel->expirePendingPayments();


        // If seat selection submitted
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $raw = $_POST['seats'] ?? [];
            // $raw may be array with single JSON string if using JS value
            $selectedList = [];
            if (is_array($raw) && count($raw) === 1 && strpos($raw[0], '[') === 0) {
                // decode JSON
                $selectedList = json_decode($raw[0], true) ?: [];
            } elseif (is_array($raw)) {
                $selectedList = $raw;
            }
            if (empty($selectedList)) {
                $_SESSION['error'] = 'Vui lòng chọn ít nhất một ghế.';
            } else {
                $seats = [];
                foreach ($selectedList as $pair) {
                    if (strpos($pair, '|') !== false) {
                        [$seatId, $price] = explode('|', $pair);
                        $seats[(int)$seatId] = (float)$price;
                    }
                }
                if (!empty($seats)) {
                    $_SESSION['selected_performance'] = $performanceId;
                    $_SESSION['selected_seats'] = $seats;
                    $this->redirect('index.php?pg=order');
                    return;
                } else {
                    $_SESSION['error'] = 'Lựa chọn ghế không hợp lệ.';
                }
            }
        }


        $showModel = new Show();
        $seatModel = new Seat();
        $categoriesModel = new SeatCategory();
        // Find performance details via show model
        // For convenience we reuse the show model to fetch show and performance details.
        $performance = $this->findPerformanceById($performanceId);
        if (!$performance) {
            $this->redirect('index.php');
            return;
        }
        $show = $showModel->find($performance['show_id']);
        $categories = $categoriesModel->all();
        $seats   = $seatModel->seatsForTheater((int)$performance['theater_id']);
        $booked  = $seatModel->bookedForPerformance($performanceId);
        // Do not filter out seats with a null category.  These seats
        // represent gaps in the seating layout and should be preserved
        // so that the customer seat map aligns with the physical theatre.
        // All seats are passed to the view; the view will render blank
        // placeholders for seats with no category assignment.
        $this->render('performance', [
            'performance' => $performance,
            'show'  => $show,
            'categories' => $categories,
            'seats' => $seats,
            'booked' => $booked
        ]);
    }


    /**
     * Helper to fetch a single performance record by ID including theater
     * name.  We replicate the query used in Show::performances but for
     * an individual performance.
     *
     * @param int $id
     * @return array|null
     */
    private function findPerformanceById(int $id)
    {
        // Obtain DB connection via Database static method
        $db = \App\Models\Database::connect();
        try {
            // Use the stored procedure exclusively to fetch a performance.  If the
            // procedure fails or is unavailable an exception will be thrown
            // which is caught below and results in a null return.
            $stmt = $db->prepare('CALL proc_get_performance_by_id(:id)');
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            $stmt->closeCursor();
            return $row ?: null;
        } catch (\Throwable $ex) {
            // In case of any error, return null rather than executing inline SQL
            return null;
        }
    }
}

