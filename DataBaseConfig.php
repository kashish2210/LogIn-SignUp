<?php
class DataBaseConfig
{
    public $conn_string;

    public function __construct()
    {
        // Load from environment variable
        $this->conn_string = getenv("DATABASE_URL");

        if (!$this->conn_string) {
            die("Database connection string not found. Please set DATABASE_URL.");
        }
    }
}
?>
