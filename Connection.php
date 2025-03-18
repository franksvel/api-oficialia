

<?php
header('Content-Type: application/json');  // Asegura que la respuesta sea en formato JSON
header("Access-Control-Allow-Origin: http://localhost:4200"); // Permite solicitudes desde localhost:4200
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");




class Connection {
    private $host = 'localhost';
    private $dbname = 'oficialia';
    private $username = 'root';
    private $password = '';

    public function connect() {
        try {
            // Corregimos la interpolación de variables y accedemos correctamente a las propiedades
            $dsn = "mysql:host={$this->host};dbname={$this->dbname}";
            $options = [
                PDO::ATTR_ERRMODE  => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            // Utilizamos $this->password en lugar de $this->email
            return new PDO($dsn, $this->username, $this->password, $options);
        } catch (\Throwable $th) {
            echo "Error en la conexión: " . $th->getMessage();
            exit;
        }
    }
    
}
?>