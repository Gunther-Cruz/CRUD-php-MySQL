<?php
    function conectar() {
        try {
            $conn = new PDO('mysql:host=localhost;dbname=agenda', 'root', '2301');
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            //echo "Connected successfully";
            return $conn;
        } catch (PDOException $e) {
            die("Erro de conexão: " . $e->getMessage());
        }
    }


?>