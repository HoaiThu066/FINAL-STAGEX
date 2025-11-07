<?php
namespace App\Models;

class Genre extends Database
{
    public function all(): array
    {
        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->query('CALL proc_get_all_genres()');
            $rows = $stmt->fetchAll();
            $stmt->closeCursor();
            return $rows ?: [];
        } catch (\Throwable $ex) {
            return [];
        }
    }

    public function create(string $name): bool
    {
        try {
            $stmt = $this->getConnection()->prepare('CALL proc_create_genre(:name)');
            $stmt->execute(['name' => $name]);
            $stmt->fetch();
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            $stmt = $this->getConnection()->prepare('CALL proc_delete_genre(:id)');
            $stmt->execute(['id' => $id]);
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }

    public function update(int $id, string $name): bool
    {
        try {
            $stmt = $this->getConnection()->prepare('CALL proc_update_genre(:id, :name)');
            $stmt->execute([
                'id'   => $id,
                'name' => $name
            ]);
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }
}







