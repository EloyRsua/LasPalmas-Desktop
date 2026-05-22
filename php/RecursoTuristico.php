<?php
class RecursoTuristico {
    private $conn;
    private $table_name = "recursos_turisticos";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodos() {
        $query = "SELECT r.*, t.nombre_tipo, a.nombre as nombre_agencia 
                  FROM " . $this->table_name . " r
                  JOIN tipos_recursos t ON r.id_tipo = t.id
                  JOIN agencias a ON r.id_agencia = a.id
                  ORDER BY r.id ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $query = "SELECT r.*, t.nombre_tipo, a.nombre as nombre_agencia 
                  FROM " . $this->table_name . " r
                  JOIN tipos_recursos t ON r.id_tipo = t.id
                  JOIN agencias a ON r.id_agencia = a.id
                  WHERE r.id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerPlazasDisponibles($id) {
        $recurso = $this->obtenerPorId($id);
        if (!$recurso) return 0;

        $query = "SELECT SUM(plazas_reservadas) as reservadas 
                  FROM reservas 
                  WHERE id_recurso = ? AND estado_reserva = 'confirmada'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $reservadas = $row['reservadas'] ? intval($row['reservadas']) : 0;

        return intval($recurso['capacidad_maxima']) - $reservadas;
    }
}
?>
