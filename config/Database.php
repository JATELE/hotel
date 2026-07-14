<?php

class Database {
    // Configuración para LOCAL con XAMPP.
    // Si tu base de datos local tiene otro nombre, usuario o clave, cambia estos valores.
    private $host = 'localhost';
    private $db_name = 'eboiskqx_cmrhoteles_bd';
    private $username = 'root';
    private $password = '';
    public $conn;

    public function connect() {
        $this->conn = null;

        try {
            $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=utf8mb4';
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            http_response_code(500);
            echo "Error de conexión: " . $e->getMessage();
        }

        return $this->conn;
    }
}
?>