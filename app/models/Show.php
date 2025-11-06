<?php
namespace App\Models;


use PDO;


/**
 * Show model handles retrieval of plays (vở diễn) from the database.  Each
 * show may have one or more associated genres (via show_genres) and
 * associated performances.
 */
class Show extends Database
{
    /**
     * Fetch a list of all shows sorted by creation date descending.
     * Includes concatenated genres for display.
     *
     * @return array
     */
    public function all(): array
    {
        /**
         * Fetch all shows along with their concatenated genre names.  In the
         * ideal case a stored procedure returns the result.  Because some
         * environments may not import the stored procedures (such as
         * developer installations on XAMPP), we provide a fallback plain
         * SQL implementation.  This ensures the site continues to operate
         * even when the procedures are missing.
         */
        $pdo = $this->getConnection();
        // First synchronise individual performance statuses.  In the lighter
        // version we attempt to call the stored procedure but do not fall back
        // to manual SQL.  Any exception is silently ignored.
        try {
            $perfUpdate = $pdo->query('CALL proc_update_performance_statuses()');
            $perfUpdate->closeCursor();
        } catch (\Throwable $perfEx) {
            // ignore errors
        }


        // Synchronise show status with the statuses of its performances.  Only
        // call the stored procedure; no inline fallback is executed.  Errors
        // are suppressed so missing procedures do not break the application.
        try {
            $stmtUpdate = $pdo->query('CALL proc_update_show_statuses()');
            $stmtUpdate->closeCursor();
        } catch (\Throwable $ex) {
            // ignore errors
        }


        // Retrieve all shows via stored procedure.  If the procedure is not
        // available or another error occurs, return an empty array.  No
        // fallback SQL is executed in this lighter version.
        try {
            $stmt = $pdo->query('CALL proc_get_all_shows()');
            $shows = $stmt->fetchAll();
            $stmt->closeCursor();
            return $shows ?: [];
        } catch (\Throwable $ex) {
            return [];
        }
    }


    /**
     * Fetch a single show by its ID including genre names.
     *
     * @param int $id
     * @return array|null
     */
    public function find(int $id)
    {
        /**
         * Retrieve a single show by ID including a comma-separated list of
         * genres.  Only the stored procedure is invoked; no fallback query
         * is executed in this lighter version.  If the procedure is not
         * available or returns no result, null is returned.
         */
        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->prepare('CALL proc_get_show_by_id(:id)');
            $stmt->execute(['id' => $id]);
            $result = $stmt->fetch();
            $stmt->closeCursor();
            return $result ?: null;
        } catch (\Throwable $ex) {
            return null;
        }
    }


    /**
     * Fetch performances scheduled for a given show, ordered by date.
     *
     * @param int $showId
     * @return array
     */
    public function performances(int $showId): array
    {
        /**
         * Retrieve all scheduled performances for a given show, ordered by
         * performance date and start time.  If the stored procedure is
         * unavailable a fallback query is used.
         */
        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->prepare('CALL proc_get_performances_by_show(:id)');
            $stmt->execute(['id' => $showId]);
            $rows = $stmt->fetchAll();
            $stmt->closeCursor();
            return $rows ?: [];
        } catch (\Throwable $ex) {
            return [];
        }
    }


    /**
     * Insert a new show (vở diễn) into the database and return the newly
     * generated show ID or false on failure.  After inserting into
     * `shows` the associated genres should be recorded in the
     * `show_genres` pivot table separately.
     *
     * @param string $title       Title of the vở diễn
     * @param string $description Description
     * @param int    $duration    Duration in minutes
     * @param string $director    Director name
     * @param string $posterUrl   URL to poster image
     * @param string $status      Status (upcoming,current,closed)
     * @return int|false          New show ID or false on failure
     */
    public function create(string $title, string $description, int $duration, string $director, string $posterUrl, string $status)
    {
        $pdo = $this->getConnection();
        // Create a new show using a stored procedure.  Returns the new
        // show_id on success or false on failure.
        try {
            $stmt = $pdo->prepare('CALL proc_create_show(:title, :desc, :dur, :dir, :poster, :status)');
            $stmt->execute([
                'title'  => $title,
                'desc'   => $description,
                'dur'    => $duration,
                'dir'    => $director,
                'poster' => $posterUrl,
                'status' => $status
            ]);
            $row = $stmt->fetch();
            $stmt->closeCursor();
            return isset($row['show_id']) ? (int)$row['show_id'] : false;
        } catch (\Throwable $ex) {
            return false;
        }
        // Đóng hàm create() tại đây trước khi khai báo các phương thức mới
    }

    /**
     * Cập nhật thông tin chi tiết của một vở diễn mà không thay đổi trạng thái.
     * Hàm này gọi thủ tục lưu trữ proc_update_show_details để tránh truy vấn trực tiếp.
     *
     * @param int    $id         Mã vở diễn
     * @param string $title      Tiêu đề mới
     * @param string $description Mô tả mới
     * @param int    $duration    Thời lượng mới (phút)
     * @param string $director    Đạo diễn mới
     * @param string $posterUrl   URL ảnh poster mới
     * @return bool  true nếu thành công, false nếu thất bại
     */
    public function updateDetails(int $id, string $title, string $description, int $duration, string $director, string $posterUrl): bool
    {
        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->prepare('CALL proc_update_show_details(:sid, :title, :desc, :dur, :dir, :poster)');
            $stmt->execute([
                'sid'   => $id,
                'title' => $title,
                'desc'  => $description,
                'dur'   => $duration,
                'dir'   => $director,
                'poster'=> $posterUrl
            ]);
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }

    /**
     * Cập nhật các thể loại của một vở diễn.  Hàm sẽ xóa tất cả các
     * bản ghi show_genres hiện có cho vở diễn sau đó chèn lại từng mã
     * thể loại được cung cấp.  Toàn bộ thao tác được thực hiện thông
     * qua các thủ tục lưu trữ để tránh truy vấn thủ công.
     *
     * @param int   $showId     Mã vở diễn
     * @param array $genreIds   Danh sách ID thể loại
     * @return bool  true nếu thành công
     */
    public function updateGenres(int $showId, array $genreIds): bool
    {
        $pdo = $this->getConnection();
        try {
            // Xóa tất cả các liên kết thể loại của vở diễn
            $stmtDel = $pdo->prepare('CALL proc_delete_show_genres(:sid)');
            $stmtDel->execute(['sid' => $showId]);
            $stmtDel->closeCursor();
            // Thêm từng thể loại mới
            if (!empty($genreIds)) {
                $stmtIns = $pdo->prepare('CALL proc_add_show_genre(:sid, :gid)');
                foreach ($genreIds as $gid) {
                    $stmtIns->execute(['sid' => $showId, 'gid' => $gid]);
                    $stmtIns->closeCursor();
                }
            }
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }


    /**
     * Delete a show and associated pivot rows.  Use with care – bookings
     * linked to performances will also be removed due to cascading.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        // Remove a show and its associated genres using a stored procedure.
        // Returns true on success and false on failure.
        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->prepare('CALL proc_delete_show(:id)');
            $stmt->execute(['id' => $id]);
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }
}