<?php
require "DataBaseConfig.php";

class DataBase
{
    public $connect;
    private $conn_string;

    public function __construct()
    {
        $dbc = new DataBaseConfig();
        $this->conn_string = $dbc->conn_string;
    }

    function dbConnect()
    {
        $this->connect = pg_connect($this->conn_string);
        if (!$this->connect) {
            die("Database connection failed: " . pg_last_error());
        }
        return $this->connect;
    }

    function prepareData($data)
    {
        return htmlspecialchars(trim($data));
    }

    function logIn($table, $username, $password)
    {
        $username = $this->prepareData($username);

        // ✅ safer query
        $query = "SELECT username, password FROM $table WHERE username = $1 LIMIT 1";
        $result = pg_query_params($this->connect, $query, [$username]);

        if ($result && $row = pg_fetch_assoc($result)) {
            if (password_verify($password, $row['password'])) {
                return true;
            }
        }
        return false;
    }

    function signUp($table, $fullname, $email, $username, $password)
    {
        $fullname = $this->prepareData($fullname);
        $email    = $this->prepareData($email);
        $username = $this->prepareData($username);
        $password = password_hash($this->prepareData($password), PASSWORD_DEFAULT);

        $query = "INSERT INTO $table (fullname, username, password, email) 
                  VALUES ($1, $2, $3, $4)";
        $result = pg_query_params($this->connect, $query, [$fullname, $username, $password, $email]);

        if (!$result) {
            error_log("Signup error: " . pg_last_error($this->connect));
            return false;
        }
        return true;
    }
}
?>
