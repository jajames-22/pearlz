<?php
// models/User.php

class User {
    private $conn;
    private $table_name = "Users";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Check if an email already exists in the database
     * @param string $email
     * @return bool
     */
    public function emailExists($email) {
        $query = "SELECT user_id FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        
        $email = htmlspecialchars(strip_tags($email));
        $stmt->bindParam(':email', $email);
        
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Register a new user
     * @param string $first_name
     * @param string $last_name
     * @param string $email
     * @param string $phone
     * @param string $password_hash
     * @return bool
     */
    public function register($first_name, $last_name, $email, $phone, $password_hash) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (first_name, last_name, email, phone, password_hash) 
                  VALUES (:first_name, :last_name, :email, :phone, :password_hash)";
        
        $stmt = $this->conn->prepare($query);

        // Bind parameters safely
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':password_hash', $password_hash);

        return $stmt->execute();
    }

    /**
     * Verify login credentials
     * @param string $email
     * @param string $password
     * @return array ['status' => bool, 'user' => array, 'message' => string]
     */
    public function login($email, $password) {
        $query = "SELECT user_id, first_name, password_hash, role FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        
        $email = htmlspecialchars(strip_tags($email));
        $stmt->bindParam(':email', $email);
        
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password_hash'])) {
                // Password is correct
                return [
                    'status' => true,
                    'user' => $user
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'Invalid password.'
                ];
            }
        } else {
            return [
                'status' => false,
                'message' => 'No account found with that email.'
            ];
        }
    }
}
?>
