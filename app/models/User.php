<?php
namespace App\Models;

use PDO;

/**
 * User model handles authentication and retrieval of user records.  Each user
 * has a role: `customer` or `staff`.  This version extends the basic
 * implementation to support one‑time password (OTP) verification.  When a
 * customer registers or an unverified user logs in, a short numeric code
 * is generated and emailed to them.  The code expires after a set
 * duration (e.g. 10 minutes).  Once verified, the user can access
 * restricted features and the code is cleared.
 */
class User extends Database
{
    /**
     * Mapping arrays to convert between database user_type values (Vietnamese)
     * and internal application values (English).  The keys correspond to
     * the database values and the values correspond to the application
     * representation.  Add new roles here if needed.
     *
     * @var array<string,string>
     */
    private static array $dbToAppMap = [
        'Khách hàng' => 'customer',
        'Nhân viên'  => 'staff'
    ];

    /**
     * Inverse mapping of application user_type values (English) to
     * database values (Vietnamese).  Used when creating or updating
     * records so the correct value is persisted.
     *
     * @var array<string,string>
     */
    private static array $appToDbMap = [
        'customer' => 'Khách hàng',
        'staff'    => 'Nhân viên'
    ];

    /**
     * Map a database user_type value (Vietnamese) to the internal
     * representation used by controllers and views.  If no mapping
     * exists the original value is returned.
     *
     * @param string|null $dbValue
     * @return string|null
     */
    public static function mapUserTypeDbToApp(?string $dbValue): ?string
    {
        if ($dbValue === null) return null;
        return self::$dbToAppMap[$dbValue] ?? $dbValue;
    }

    /**
     * Map an application user_type value (English) to the database
     * value (Vietnamese).  If no mapping exists the original value
     * is returned.
     *
     * @param string|null $appValue
     * @return string|null
     */
    public static function mapUserTypeAppToDb(?string $appValue): ?string
    {
        if ($appValue === null) return null;
        return self::$appToDbMap[$appValue] ?? $appValue;
    }
    /**
     * Find a user by email address.  Returns an associative array of the
     * user's record or false if not found.
     *
     * @param string $email
     * @return array|false
     */
    public function findByEmail(string $email)
    {
        $pdo = $this->getConnection();
        // Use stored procedure only; return false if not found or on error
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

    /**
     * Find a user by ID.  Returns an associative array of the user record
     * or false if not found.
     *
     * @param int $id
     * @return array|false
     */
    public function findById(int $id)
    {
        $pdo = $this->getConnection();
        // Use stored procedure only; return false if not found or on error
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

    /**
     * Create a new user record.  New accounts are unverified by default and
     * will require OTP verification before they can access customer
     * features.  Staff accounts should be created with `$verified = true`.
     *
     * @param string $email    User's email address
     * @param string $password Plain text password (will be hashed)
     * @param string $name     Display name
     * @param string $type     Role ('customer' or 'staff')
     * @param bool   $verified Whether the account is already verified
     * @return bool           True on success
     */
    public function create(string $email, string $password, string $accountName, string $type = 'customer', bool $verified = false)
    {
        // Hash the password for storage
        $hash = password_hash($password, PASSWORD_DEFAULT);
        // Map the application user_type to the database value (Vietnamese)
        $dbType = self::mapUserTypeAppToDb($type);
        // Use a stored procedure to create a new user.  The procedure
        // returns the generated user_id; however this method only
        // returns a boolean for compatibility with the rest of the code.
        try {
            $stmt = $this->getConnection()->prepare('CALL proc_create_user(:email, :password, :account_name, :type, :verified)');
            $stmt->execute([
                'email'        => $email,
                'password'     => $hash,
                'account_name' => $accountName,
                'type'         => $dbType,
                'verified'     => $verified ? 1 : 0
            ]);
            // Drain the returned result set (contains user_id)
            $stmt->fetch();
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }

    /**
     * Assign an OTP code and expiry timestamp to a user.  Used during
     * registration and verification flows.  The OTP code should be a
     * random short string and expires in the near future (e.g. 10 minutes).
     *
     * @param int    $userId  User identifier
     * @param string $otpCode The generated OTP code
     * @param string $expires MySQL datetime string for when the OTP expires
     */
    public function setOtp(int $userId, string $otpCode, string $expires): void
    {
        $pdo = $this->getConnection();
        // Use stored procedure only; suppress errors if it fails
        try {
            $stmt = $pdo->prepare('CALL proc_set_user_otp(:id, :otp, :expires)');
            $stmt->execute([
                'id'      => $userId,
                'otp'     => $otpCode,
                'expires' => $expires
            ]);
            $stmt->closeCursor();
        } catch (\Throwable $ex) {
            // do nothing
        }
    }

    /**
     * Verify a user‑supplied OTP against the stored value.  If the codes
     * match and the current time is before the expiry timestamp, the user
     * is marked as verified and the OTP information is cleared.
     *
     * @param int    $userId  User identifier
     * @param string $otpCode The code entered by the user
     * @return bool Whether the verification succeeded
     */
    public function verifyOtp(int $userId, string $otpCode): bool
    {
        // Verify the provided OTP using a stored procedure.  The procedure
        // compares the stored code and expiry against the input and
        // updates the user if valid.  It returns a single row with a
        // `verified` column equal to 1 on success or 0 on failure.
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

    /**
     * Update a staff (admin) account.  Only account_name, email and status
     * can be updated via this method.  Customers are not affected.
     *
     * The status values are stored in Vietnamese: `hoạt động` (active) or
     * `khóa` (locked).  Passing any other value may result in the
     * account being treated as locked by the authentication logic.
     *
     * @param int    $userId      The user ID of the staff account
     * @param string $accountName New account name
     * @param string $email       New email address
     * @param string $status      New status (hoạt động|khóa)
     * @return bool  True on success, false on failure
     */
    public function updateStaff(int $userId, string $accountName, string $email, string $status): bool
    {
        // Update a staff account via stored procedure.  Returns true on success.
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

    /**
     * Retrieve all staff (admin) accounts.  Results are ordered by user_id ascending.
     * Uses a stored procedure if available, with a fallback direct query.
     *
     * @return array
     */
    public function getStaff(): array
    {
        // Retrieve all staff accounts using a stored procedure.  No fallback
        // query is executed in the lighter version.  Returns an empty
        // array on error.
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

    /**
     * Provide a publicly accessible PDO connection.  This wrapper
     * exposes the protected getConnection() method to controllers
     * that need to perform raw queries without duplicating logic.
     *
     * @return \PDO
     */
    public function getPdo(): \PDO
    {
        return $this->getConnection();
    }

    /**
     * Count the number of customers (users with user_type = 'customer').
     * Useful for admin dashboard statistics.
     *
     * @return int
     */
    public function countCustomers(): int
    {
        // Count the number of customer accounts via stored procedure.  The
        // procedure returns a single row with a `total` column.
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

    /**
     * Find a user by account name.  Returns associative array or false if none.
     * Useful when users enter their username instead of email for password resets.
     *
     * @param string $accountName
     * @return array|false
     */
    public function findByAccountName(string $accountName)
    {
        $pdo = $this->getConnection();
        // Find a user by account name via stored procedure.  Returns an
        // associative array on success or false on failure/not found.
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

    /**
     * Find a user by either email or account name.  Determines if the
     * provided identifier looks like an email and delegates to the
     * appropriate finder.  If the identifier contains an '@' and
     * is a valid email address, find by email; otherwise find by
     * account name.
     *
     * @param string $identifier Email address or account_name
     * @return array|false
     */
    public function findByEmailOrAccountName(string $identifier)
    {
        $identifier = trim($identifier);
        if (!$identifier) {
            return false;
        }
        // Check for email pattern
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return $this->findByEmail($identifier);
        }
        return $this->findByAccountName($identifier);
    }

    /**
     * Update a user's password.  The new password will be hashed before storing.
     * Clears any existing OTP codes to prevent reuse.  Returns true on success.
     *
     * @param int    $userId    User identifier
     * @param string $password  Plain text new password
     * @return bool
     */
    public function updatePassword(int $userId, string $password): bool
    {
        // Hash the password and update it via stored procedure.  The
        // procedure also clears any existing OTP codes.  Returns true
        // on success.
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

    /**
     * Delete a staff user account by ID.  Only users with a user_type of
     * "Nhân viên" or the legacy value "staff" will be removed.  This method
     * attempts to call a stored procedure first.  If the procedure
     * is unavailable, it falls back to a simple DELETE statement.  Returns
     * true on success, false otherwise.
     *
     * @param int $userId The user_id of the staff account to delete
     * @return bool       True if the deletion succeeded
     */
    public function deleteStaff(int $userId): bool
    {
        // Delete a staff user via stored procedure only.  Returns true on success.
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