<?php
class DataBaseConfig
{
    public $conn_string;

    public function __construct()
    {
        // Use the Internal URL for apps running on Render (faster & secure)
        $this->conn_string = "postgresql://loginsample_user:Y8SRQWascB2kZpHfq4gIX1Hnpcdt7TH0@dpg-d2mbva1r0fns73d03840-a/loginsample";
    }
}
?>
