<?php
namespace App\Models;

use PDO;

class Review extends Database
{
    public function getByShow(int $showId): array
    {
        $pdo = $this->getConnection();
        // Use the stored procedure only.  In the lighter version no fallback
        // direct query is executed; if the procedure fails or returns no
        // rows an empty array is returned.
        try {
            $stmt = $pdo->prepare('CALL proc_get_reviews_by_show(:sid)');
            $stmt->execute(['sid' => $showId]);
            $rows = $stmt->fetchAll();
            $stmt->closeCursor();
            return $rows ?: [];
        } catch (\Throwable $ex) {
            return [];
        }
    }
    public function create(int $showId, int $userId, int $rating, string $content): bool
    {
        // Insert a new review using a stored procedure.  Returns true on success.
        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->prepare('CALL proc_create_review(:sid, :uid, :rating, :content)');
            $stmt->execute([
                'sid'     => $showId,
                'uid'     => $userId,
                'rating'  => $rating,
                'content' => $content
            ]);
            // Fetch the returned review_id to clear the cursor.  Some drivers
            // will otherwise prevent subsequent queries with "commands out of sync".
            $stmt->fetch();
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }
    public function getAll(): array
    {
        $pdo = $this->getConnection();
        // Use stored procedure only; return an empty array on failure.
        try {
            $stmt = $pdo->query('CALL proc_get_all_reviews()');
            $rows = $stmt->fetchAll();
            $stmt->closeCursor();
            return $rows ?: [];
        } catch (\Throwable $ex) {
            return [];
        }
    }

    public function getLatest(int $limit = 15): array
    {
        $pdo = $this->getConnection();
        // Call the stored procedure to get the latest reviews.  If it fails or
        // returns no rows, an empty array is returned.  Normalize the returned
        // column names where necessary.
        try {
            $stmt = $pdo->prepare('CALL proc_get_latest_reviews(:lim)');
            $stmt->execute(['lim' => $limit]);
            $rows = $stmt->fetchAll();
            $stmt->closeCursor();
            if ($rows) {
                foreach ($rows as &$r) {
                    if (isset($r['show_title']) && !isset($r['title'])) {
                        $r['title'] = $r['show_title'];
                    }
                }
                unset($r);
            }
            return $rows ?: [];
        } catch (\Throwable $ex) {
            return [];
        }
    }

    public function delete(int $id): bool
    {
        // Delete a review using a stored procedure.  Returns true on success.
        try {
            $stmt = $this->getConnection()->prepare('CALL proc_delete_review(:id)');
            $stmt->execute(['id' => $id]);
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }

    public function getAverageRatingByShow(int $showId): ?float
    {
        $pdo = $this->getConnection();
        // Use the stored procedure only; if unavailable or returns null, return null
        try {
            $stmt = $pdo->prepare('CALL proc_get_average_rating_by_show(:sid)');
            $stmt->execute(['sid' => $showId]);
            $row = $stmt->fetch();
            $stmt->closeCursor();
            if ($row && isset($row['avg_rating']) && $row['avg_rating'] !== null) {
                return round((float)$row['avg_rating'], 1);
            }
            return null;
        } catch (\Throwable $ex) {
            return null;
        }
    }
}