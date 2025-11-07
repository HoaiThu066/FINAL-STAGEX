<?php
namespace App\Models;


use PDO;
class Seat extends Database
{
    public function seatsForTheater(int $theaterId): array
    {
        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->prepare('CALL proc_get_seats_for_theater(:tid)');
            $stmt->execute(['tid' => $theaterId]);
            $rows = $stmt->fetchAll();
            $stmt->closeCursor();
            return $rows ?: [];
        } catch (\Throwable $ex) {
            return [];
        }
    }

    public function bookedForPerformance(int $performanceId): array
    {
        $pdo = $this->getConnection();
        $ids = [];
        try {
            $stmt = $pdo->prepare('CALL proc_get_booked_seat_ids(:pid)');
            $stmt->execute(['pid' => $performanceId]);
            while ($row = $stmt->fetch()) {
                if (isset($row['seat_id'])) {
                    $ids[(int)$row['seat_id']] = true;
                }
            }
            $stmt->closeCursor();
        } catch (\Throwable $ex) {
            return [];
        }
        return $ids;
    }
    public function updateCategoryRange(int $theaterId, string $rowChar, int $startSeat, int $endSeat, ?int $categoryId): bool
    {
        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->prepare('CALL proc_update_seat_category_range(:tid, :row, :start, :end, :cid)');
            $stmt->execute([
                'tid'   => $theaterId,
                'row'   => $rowChar,
                'start' => $startSeat,
                'end'   => $endSeat,
                'cid'   => $categoryId ?: 0
            ]);
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }
}