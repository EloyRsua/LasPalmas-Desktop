<?php
class Database {
    private $host = "localhost";
    private $db_name = "reservas";
    private $username = "DBUSER2026";
    private $password = "DBPWD2026";
    private $conn = null;

    public function getConnection() {
        if ($this->conn !== null) {
            return $this->conn;
        }

        // Lista de hosts para intentar la conexión (por si localhost da problemas en macOS/MAMP)
        $hosts = ["localhost", "127.0.0.1"];
        $ultimoError = null;

        foreach ($hosts as $h) {
            try {
                $this->host = $h;
                // Conectar a MySQL
                $this->conn = new PDO("mysql:host=" . $this->host, $this->username, $this->password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Crear base de datos si no existe
                $this->conn->exec("CREATE DATABASE IF NOT EXISTS `" . $this->db_name . "` CHARACTER SET utf8 COLLATE utf8_general_ci;");
                $this->conn->exec("USE `" . $this->db_name . "`;");
                
                // Verificar si las tablas existen, si no, crearlas e inicializarlas
                $this->verificarEInicializarDB();
                
                return $this->conn;
            } catch (PDOException $exception) {
                $ultimoError = $exception;
                $this->conn = null; // Asegurar que queda nulo si este host falla
            }
        }

        // Si todos los hosts fallaron, abortar
        die("Error de conexión a la base de datos: " . $ultimoError->getMessage());
    }

    private function verificarEInicializarDB() {
        try {
            // Comprobar si existe la tabla 'usuarios'. Si falla la consulta, ejecutamos el script SQL.
            $this->conn->query("SELECT 1 FROM usuarios LIMIT 1");
        } catch (Exception $e) {
            $this->ejecutarScriptSQL();
        }

        // Cargar datos desde los CSVs en aquellas tablas que estén vacías
        $this->cargarDatosDesdeCSV();
    }

    private function ejecutarScriptSQL() {
        $sqlPath = __DIR__ . '/crear_db.sql';
        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            // Ejecutar consultas múltiples
            $this->conn->exec($sql);
        }
    }

    private function cargarDatosDesdeCSV() {
        $tablasCsv = [
            'usuarios' => ['id', 'nombre', 'email', 'password'],
            'agencias' => ['id', 'nombre', 'contacto', 'telefono'],
            'tipos_recursos' => ['id', 'nombre_tipo', 'descripcion'],
            'recursos_turisticos' => ['id', 'nombre', 'descripcion', 'id_tipo', 'id_agencia', 'capacidad_maxima', 'fecha_inicio', 'fecha_fin', 'precio'],
            'reservas' => ['id', 'id_usuario', 'id_recurso', 'plazas_reservadas', 'estado_reserva', 'total_pagar']
        ];

        foreach ($tablasCsv as $tabla => $campos) {
            // Solo cargar datos si la tabla existe y está vacía
            try {
                $stmt = $this->conn->query("SELECT COUNT(*) as cnt FROM `{$tabla}`");
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (intval($row['cnt']) > 0) {
                    continue; // Ya contiene datos, pasar a la siguiente tabla
                }
            } catch (Exception $e) {
                continue; // Si la tabla no existe o falla, saltar para evitar errores
            }

            // Mapear el nombre del archivo CSV (recursos_turisticos usa recursos.csv)
            $nombreCsv = ($tabla === 'recursos_turisticos') ? 'recursos' : $tabla;
            $csvPath = __DIR__ . '/' . $nombreCsv . '.csv';
            if (!file_exists($csvPath) || !($file = fopen($csvPath, 'r'))) {
                continue;
            }

            // Omitir cabecera
            fgetcsv($file);

            $placeholders = implode(', ', array_fill(0, count($campos), '?'));
            $camposStr = implode(', ', $campos);
            $query = "INSERT INTO `{$tabla}` ({$camposStr}) VALUES ({$placeholders})";
            $stmt = $this->conn->prepare($query);

            while (($row = fgetcsv($file)) !== false) {
                // Rellenar con nulos si faltan campos o cortar si sobran utilizando array_slice y array_pad
                $row = array_pad(array_slice($row, 0, count($campos)), count($campos), null);
                
                try {
                    $stmt->execute($row);
                } catch (Exception $e) {
                    // Ignorar filas problemáticas individuales
                }
            }
            fclose($file);
        }
    }
}
?>
