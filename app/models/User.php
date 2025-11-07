<?php
namespace App\Models;

use PDO;

class User extends Database
{
    private static array $dbToAppMap = [
        'Khách hàng' => 'customer',
        'Nhân viên'  => 'staff'
    ];
    private static array $appToDbMap = [
        'customer' => 'Khách hàng',
        'staff'    => 'Nhân viên'
    ];
    public static function mapUserTypeDbToApp(?string $dbValue): ?string
    {
        if ($dbValue === null) return null;
        return self::$dbToAppMap[$dbValue] ?? $dbValue;
    }
    public static function mapUserTypeAppToDb(?string $appValue): ?string
    {
        if ($appValue === null) return null;
        return self::$appToDbMap[$appValue] ?? $appValue;
    }
    public function findByEmail(string $email)
    {
        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->prepare('CALL proc_get_user_by_email(:email)');
            $stmt->execute(['email' => $email]);
            $result = $stmt->fetch();
            $stmt->closeCursor();
            if ($result) {
                $result['user_type'] = self::mapUserTypeDbToApp($result['user_type'] ?? null);
                return $result;
            }
            return false;
        } catch (\Throwable $ex) {
            return false;
        }
    }
    public function findById(int $id)
    {
        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->prepare('CALL proc_get_user_by_id(:id)');
            $stmt->execute(['id' => $id]);
            $result = $stmt->fetch();
            $stmt->closeCursor();
            if ($result) {
                $result['user_type'] = self::mapUserTypeDbToApp($result['user_type'] ?? null);
                return $result;
            }
            return false;
        } catch (\Throwable $ex) {
            return false;
        }
    }
    public function create(string $email, string $password, string $accountName, string $type = 'customer', bool $verified = false)
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $dbType = self::mapUserTypeAppToDb($type);
        try {
            $stmt = $this->getConnection()->prepare('CALL proc_create_user(:email, :password, :account_name, :type, :verified)');
            $stmt->execute([
                'email'        => $email,
                'password'     => $hash,
                'account_name' => $accountName,
                'type'         => $dbType,
                'verified'     => $verified ? 1 : 0
            ]);
            $stmt->fetch();
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }
    public function setOtp(int $userId, string $otpCode, string $expires): void
    {
        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->prepare('CALL proc_set_user_otp(:id, :otp, :expires)');
            $stmt->execute([
                'id'      => $userId,
                'otp'     => $otpCode,
                'expires' => $expires
            ]);
            $stmt->closeCursor();
        } catch (\Throwable $ex) {}
    }
    public function verifyOtp(int $userId, string $otpCode): bool
    {
        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->prepare('CALL proc_verify_user_otp(:id, :code)');
            $stmt->execute([
                'id'   => $userId,
                'code' => $otpCode
            ]);
            $row = $stmt->fetch();
            $stmt->closeCursor();
            return isset($row['verified']) && (int)$row['verified'] === 1;
        } catch (\Throwable $ex) {
            return false;
        }
    }
    public function updateStaff(int $userId, string $accountName, string $email, string $status): bool
    {
        try {
            $stmt = $this->getConnection()->prepare('CALL proc_update_staff_user(:uid, :acc, :email, :status)');
            $stmt->execute([
                'uid'    => $userId,
                'acc'    => $accountName,
                'email'  => $email,
                'status' => $status
            ]);
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }
    public function getStaff(): array
    {
        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->query('CALL proc_get_staff_users()');
            $rows = $stmt->fetchAll();
            $stmt->closeCursor();
            return $rows ?: [];
        } catch (\Throwable $ex) {
            return [];
        }
    }
    public function getPdo(): \PDO
    {
        return $this->getConnection();
    }
    public function countCustomers(): int
    {
        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->query('CALL proc_count_customers()');
            $row = $stmt->fetch();
            $stmt->closeCursor();
            return isset($row['total']) ? (int)$row['total'] : 0;
        } catch (\Throwable $ex) {
            return 0;
        }
    }
    public function findByAccountName(string $accountName)
    {
        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->prepare('CALL proc_get_user_by_account_name(:name)');
            $stmt->execute(['name' => $accountName]);
            $row = $stmt->fetch();
            $stmt->closeCursor();
            if ($row) {
                $row['user_type'] = self::mapUserTypeDbToApp($row['user_type'] ?? null);
                return $row;
            }
            return false;
        } catch (\Throwable $ex) {
            return false;
        }
    }
    public function findByEmailOrAccountName(string $identifier)
    {
        $identifier = trim($identifier);
        if (!$identifier) {
            return false;
        }
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return $this->findByEmail($identifier);
        }
        return $this->findByAccountName($identifier);
    }
    public function updatePassword(int $userId, string $password): bool
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $this->getConnection()->prepare('CALL proc_update_user_password(:uid, :pwd)');
            $stmt->execute([
                'uid' => $userId,
                'pwd' => $hash
            ]);
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }
    public function deleteStaff(int $userId): bool
    {
        try {
            $stmt = $this->getConnection()->prepare('CALL proc_delete_staff(:uid)');
            $stmt->execute(['uid' => $userId]);
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }
}