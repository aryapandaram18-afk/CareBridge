<?php
/**
 * Database Configuration for CareBridge
 * MySQL Database Connection
 */

class Database {
    private $host = 'localhost';
    private $username = 'root';
    private $password = 'Bs$230331';
    private $database = 'carebridge';
    private $connection;

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        try {
            $this->connection = new mysqli(
                $this->host, 
                $this->username, 
                $this->password, 
                $this->database
            );

            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }

            $this->connection->set_charset("utf8mb4");
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw $e;
        }
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql) {
        try {
            $result = $this->connection->query($sql);
            if (!$result) {
                throw new Exception("Query failed: " . $this->connection->error);
            }
            return $result;
        } catch (Exception $e) {
            error_log("Database query error: " . $e->getMessage());
            throw $e;
        }
    }

    public function prepare($sql) {
        try {
            $stmt = $this->connection->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->connection->error);
            }
            return $stmt;
        } catch (Exception $e) {
            error_log("Database prepare error: " . $e->getMessage());
            throw $e;
        }
    }

    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }

    public function getLastInsertId() {
        return $this->connection->insert_id;
    }

    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }

    public function __destruct() {
        $this->close();
    }
}
?>
