<?php
class Usuario {
    private $conn;
    private $table_name = "usuarios";

    public $id;
    public $nombre;
    public $email;
    public $password;
    public $fecha_registro;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function registrar() {
        // Limpieza de datos
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = htmlspecialchars(strip_tags($this->password));

        // Encriptar contraseña
        $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);

        $query = "INSERT INTO " . $this->table_name . " (nombre, email, password) VALUES (:nombre, :email, :password)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $hashed_password);

        try {
            if ($stmt->execute()) {
                return true;
            }
        } catch (PDOException $e) {
            // Manejar errores de clave única (email duplicado) u otros
            return false;
        }
        return false;
    }

    public function emailExiste() {
        $query = "SELECT id, nombre, password FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->email]);
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->nombre = $row['nombre'];
            $this->password = $row['password'];
            return true;
        }
        return false;
    }

    public function login($password_input) {
        if ($this->emailExiste()) {
            if (password_verify($password_input, $this->password)) {
                return true;
            }
        }
        return false;
    }
}
?>
