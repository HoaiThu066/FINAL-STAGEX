<?php
namespace App\Models;


use PDO;

class Show extends Database
{
    public function all(): array
    {
        $pdo = $this->getConnection();
        try {
            $perfUpdate = $pdo->query('CALL proc_update_performance_statuses()');
            $perfUpdate->closeCursor();
        } catch (\Throwable $perfEx) {

        }

        try {
            $stmtUpdate = $pdo->query('CALL proc_update_show_statuses()');
            $stmtUpdate->closeCursor();
        } catch (\Throwable $ex) {

        }

        try {
            $stmt = $pdo->query('CALL proc_get_all_shows()');
            $shows = $stmt->fetchAll();
            $stmt->closeCursor();
            return $shows ?: [];
        } catch (\Throwable $ex) {
            return [];
        }
    }

    public function find(int $id)
    {
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

    public function performances(int $showId): array
    {

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

    public function create(string $title, string $description, int $duration, string $director, string $posterUrl, string $status)
    {
        $pdo = $this->getConnection();
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
    }

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

    public function updateGenres(int $showId, array $genreIds): bool
    {
        $pdo = $this->getConnection();
        try {
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

    public function delete(int $id): bool
    {
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