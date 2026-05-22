<?php
class Reserva {
    private $conn;
    private $table_name = "reservas";

    public $id;
    public $id_usuario;
    public $id_recurso;
    public $plazas_reservadas;
    public $estado_reserva;
    public $total_pagar;
    public $fecha_reserva;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " (id_usuario, id_recurso, plazas_reservadas, total_pagar) 
                  VALUES (:id_usuario, :id_recurso, :plazas_reservadas, :total_pagar)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id_usuario", $this->id_usuario);
        $stmt->bindParam(":id_recurso", $this->id_recurso);
        $stmt->bindParam(":plazas_reservadas", $this->plazas_reservadas);
        $stmt->bindParam(":total_pagar", $this->total_pagar);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function obtenerPorUsuario($id_usuario) {
        $query = "SELECT r.*, rt.nombre as nombre_recurso, rt.fecha_inicio, rt.precio 
                  FROM " . $this->table_name . " r
                  JOIN recursos_turisticos rt ON r.id_recurso = rt.id
                  WHERE r.id_usuario = ? AND r.estado_reserva = 'confirmada'
                  ORDER BY r.fecha_reserva DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id_usuario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function anular($id_reserva, $id_usuario) {
        $query = "UPDATE " . $this->table_name . " 
                  SET estado_reserva = 'anulada' 
                  WHERE id = ? AND id_usuario = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id_reserva, $id_usuario]);
    }
}
?>
