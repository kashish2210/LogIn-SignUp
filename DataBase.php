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
        return $this->connect;
    }

    function prepareData($data)
    {
        return pg_escape_string($this->connect, htmlspecialchars($data));
    }

    function logIn($table, $username, $password)
    {
        $username = $this->prepareData($username);
        $query = "SELECT * FROM $table WHERE username = '$username'";
        $result = pg_query($this->connect, $query);
        $row = pg_fetch_assoc($result);

        if ($row) {
            $dbusername = $row['username'];
            $dbpassword = $row['password'];
            if ($dbusername == $username && password_verify($password, $dbpassword)) {
                return true;
            }
        }
        return false;
    }

    function signUp($table, $fullname, $email, $username, $password)
    {
        $fullname = $this->prepareData($fullname);
        $username = $this->prepareData($username);
        $email = $this->prepareData($email);
        $password = password_hash($this->prepareData($password), PASSWORD_DEFAULT);

        $query = "INSERT INTO $table (fullname, username, password, email) 
                  VALUES ('$fullname', '$username', '$password', '$email')";
        return pg_query($this->connect, $query) ? true : false;
    }
}
?>
