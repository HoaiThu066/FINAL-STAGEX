<?php
namespace App\Models;

class UserDetail extends Database
{
    public function find(int $userId): ?array
    {
        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->prepare('CALL proc_get_user_detail_by_id(:uid)');
            $stmt->execute(['uid' => $userId]);
            $row = $stmt->fetch();
            $stmt->closeCursor();
            return $row ?: null;
        } catch (\Throwable $ex) {
            return null;
        }
    }
    public function save(int $userId, ?string $fullName, ?string $dob, ?string $address, ?string $phone): bool
    {
        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->prepare('CALL proc_upsert_user_detail(:uid, :fullname, :dob, :addr, :phone)');
            $stmt->execute([
                'uid'      => $userId,
                'fullname' => $fullName,
                'dob'      => $dob,
                'addr'     => $address,
                'phone'    => $phone
            ]);
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }
}