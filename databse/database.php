<?php
// databse/database.php

class Database {
    private $host = "localhost";
    private $db_name = "pearlz";
    private $username = "root"; // Default XAMPP username
    private $password = "";     // Default XAMPP password
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            // Using PDO for secure and robust database connections
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            
            // Set the PDO error mode to exception so we can catch errors easily
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Set default fetch mode to associative arrays (easier to work with)
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Ensure character encoding is UTF-8
            $this->conn->exec("set names utf8");
            
        } catch(PDOException $exception) {
            // In a production environment, you might want to log this instead of displaying it
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>
