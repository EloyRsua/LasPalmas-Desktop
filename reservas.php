<?php
// Iniciar sesión
session_start();

// Cargar clases
require_once __DIR__ . '/php/Database.php';
require_once __DIR__ . '/php/Usuario.php';
require_once __DIR__ . '/php/RecursoTuristico.php';
require_once __DIR__ . '/php/Reserva.php';

// Inicializar base de datos y obtener conexión
$database = new Database();
$db = $database->getConnection();

// Instanciar modelos
$usuarioModel = new Usuario($db);
$recursoModel = new RecursoTuristico($db);
$reservaModel = new Reserva($db);

$mensaje = "";
$tipoMensaje = ""; // 'success' o 'error'
$presupuesto = null;

// Procesar Acciones (Formularios)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. REGISTRO DE USUARIO
    if (isset($_POST['accion']) && $_POST['accion'] === 'registro') {
        $usuarioModel->nombre = $_POST['nombre'] ?? '';
        $usuarioModel->email = $_POST['email'] ?? '';
        $usuarioModel->password = $_POST['password'] ?? '';

        if (empty($usuarioModel->nombre) || empty($usuarioModel->email) || empty($usuarioModel->password)) {
            $mensaje = "Todos los campos son obligatorios para registrarse.";
            $tipoMensaje = "error";
        } elseif (!filter_var($usuarioModel->email, FILTER_VALIDATE_EMAIL)) {
            $mensaje = "El formato de correo electrónico no es válido.";
            $tipoMensaje = "error";
        } else {
            // Verificar si el email ya existe
            if ($usuarioModel->emailExiste()) {
                $mensaje = "El correo electrónico ya está registrado.";
                $tipoMensaje = "error";
            } else {
                if ($usuarioModel->registrar()) {
                    $mensaje = "Registro completado con éxito. Ya puedes iniciar sesión.";
                    $tipoMensaje = "success";
                } else {
                    $mensaje = "Hubo un error al registrar el usuario.";
                    $tipoMensaje = "error";
                }
            }
        }
    }

    // 2. INICIO DE SESIÓN
    if (isset($_POST['accion']) && $_POST['accion'] === 'login') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $mensaje = "El email y la contraseña son obligatorios.";
            $tipoMensaje = "error";
        } else {
            $usuarioModel->email = $email;
            if ($usuarioModel->login($password)) {
                // Almacenar en sesión
                $_SESSION['usuario_id'] = $usuarioModel->id;
                $_SESSION['usuario_nombre'] = $usuarioModel->nombre;
                $_SESSION['usuario_email'] = $usuarioModel->email;
                $mensaje = "¡Sesión iniciada con éxito! Bienvenido, " . $usuarioModel->nombre . ".";
                $tipoMensaje = "success";
            } else {
                $mensaje = "Credenciales incorrectas. Inténtalo de nuevo.";
                $tipoMensaje = "error";
            }
        }
    }

    // 3. CERRAR SESIÓN
    if (isset($_POST['accion']) && $_POST['accion'] === 'logout') {
        session_unset();
        session_destroy();
        header("Location: reservas.php");
        exit();
    }

    // 4. GENERAR PRESUPUESTO
    if (isset($_POST['accion']) && $_POST['accion'] === 'generar_presupuesto') {
        if (!isset($_SESSION['usuario_id'])) {
            $mensaje = "Debes iniciar sesión para realizar una reserva.";
            $tipoMensaje = "error";
        } else {
            $id_recurso = intval($_POST['recurso_id'] ?? 0);
            $plazas = intval($_POST['plazas'] ?? 0);

            if ($id_recurso <= 0 || $plazas <= 0) {
                $mensaje = "Selecciona un recurso válido y al menos 1 plaza.";
                $tipoMensaje = "error";
            } else {
                $recurso = $recursoModel->obtenerPorId($id_recurso);
                if (!$recurso) {
                    $mensaje = "El recurso seleccionado no existe.";
                    $tipoMensaje = "error";
                } else {
                    $disponibles = $recursoModel->obtenerPlazasDisponibles($id_recurso);
                    if ($plazas > $disponibles) {
                        $mensaje = "No hay suficientes plazas disponibles. (Disponibles: {$disponibles})";
                        $tipoMensaje = "error";
                    } else {
                        // Presupuesto temporal
                        $total = $recurso['precio'] * $plazas;
                        $presupuesto = [
                            'recurso_id' => $id_recurso,
                            'nombre_recurso' => $recurso['nombre'],
                            'precio_unitario' => $recurso['precio'],
                            'plazas' => $plazas,
                            'total' => $total
                        ];
                    }
                }
            }
        }
    }

    // 5. CONFIRMAR RESERVA
    if (isset($_POST['accion']) && $_POST['accion'] === 'confirmar_reserva') {
        if (!isset($_SESSION['usuario_id'])) {
            $mensaje = "Sesión caducada. Inicia sesión de nuevo.";
            $tipoMensaje = "error";
        } else {
            $id_recurso = intval($_POST['recurso_id'] ?? 0);
            $plazas = intval($_POST['plazas'] ?? 0);

            if ($id_recurso <= 0 || $plazas <= 0) {
                $mensaje = "Datos de reserva inválidos.";
                $tipoMensaje = "error";
            } else {
                $recurso = $recursoModel->obtenerPorId($id_recurso);
                if (!$recurso) {
                    $mensaje = "El recurso ya no está disponible.";
                    $tipoMensaje = "error";
                } else {
                    $disponibles = $recursoModel->obtenerPlazasDisponibles($id_recurso);
                    if ($plazas > $disponibles) {
                        $mensaje = "Lamentablemente, ya no hay suficientes plazas libres.";
                        $tipoMensaje = "error";
                    } else {
                        // Crear la reserva
                        $reservaModel->id_usuario = $_SESSION['usuario_id'];
                        $reservaModel->id_recurso = $id_recurso;
                        $reservaModel->plazas_reservadas = $plazas;
                        $reservaModel->total_pagar = $recurso['precio'] * $plazas;

                        if ($reservaModel->crear()) {
                            $mensaje = "¡Reserva confirmada con éxito! Se ha registrado el recurso.";
                            $tipoMensaje = "success";
                        } else {
                            $mensaje = "Ocurrió un error al procesar tu reserva.";
                            $tipoMensaje = "error";
                        }
                    }
                }
            }
        }
    }

    // 6. ANULAR RESERVA
    if (isset($_POST['accion']) && $_POST['accion'] === 'anular_reserva') {
        if (!isset($_SESSION['usuario_id'])) {
            $mensaje = "Debes iniciar sesión para anular una reserva.";
            $tipoMensaje = "error";
        } else {
            $id_reserva = intval($_POST['reserva_id'] ?? 0);
            if ($id_reserva <= 0) {
                $mensaje = "Reserva inválida.";
                $tipoMensaje = "error";
            } else {
                if ($reservaModel->anular($id_reserva, $_SESSION['usuario_id'])) {
                    $mensaje = "La reserva ha sido anulada correctamente.";
                    $tipoMensaje = "success";
                } else {
                    $mensaje = "No se pudo anular la reserva.";
                    $tipoMensaje = "error";
                }
            }
        }
    }
}

// Obtener datos para la vista
$recursos = $recursoModel->obtenerTodos();
$reservasUsuario = [];
if (isset($_SESSION['usuario_id'])) {
    $reservasUsuario = $reservaModel->obtenerPorUsuario($_SESSION['usuario_id']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Central de Reservas - Las Palmas Desktop</title>
    <meta name="author" content="Eloy Rubio Suárez" />
    <meta name="description" content="Central de reservas de recursos turísticos en Las Palmas" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" href="multimedia/favicon.ico" />
    
    <!-- Estilos base del proyecto -->
    <link rel="stylesheet" href="estilo/layout.css" />
    <link rel="stylesheet" href="estilo/estilos.css" />

    <style>
        /* Estilos específicos y premium para la sección de Reservas */
        main {
            max-width: 1000px;
            margin: 0 auto;
            padding: 1rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .auth-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .auth-container {
                grid-template-columns: 1fr;
            }
        }

        .panel-form {
            background-color: #ffffff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        .panel-form h3 {
            margin-top: 0;
            color: #004685;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #4a5568;
        }

        .form-control {
            width: 100%;
            padding: 0.6rem;
            border: 1px solid #cbd5e0;
            border-radius: 4px;
            font-size: 1rem;
            box-sizing: border-box;
        }

        .form-control:focus {
            border-color: #004685;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,70,133,0.15);
        }

        .btn {
            background-color: #004685;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.1s ease;
            display: inline-block;
        }

        .btn:hover {
            background-color: #002d57;
        }

        .btn-secondary {
            background-color: #718096;
        }

        .btn-secondary:hover {
            background-color: #4a5568;
        }

        .btn-danger {
            background-color: #e53e3e;
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }

        .btn-danger:hover {
            background-color: #c53030;
        }

        .btn-confirm {
            background-color: #38a169;
        }

        .btn-confirm:hover {
            background-color: #276749;
        }

        .user-welcome-bar {
            background-color: #004685;
            color: white;
            padding: 1rem;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .user-welcome-bar p {
            margin: 0;
            font-weight: bold;
        }

        .user-welcome-bar form {
            margin: 0;
        }

        .recursos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .recurso-card {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .recurso-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px rgba(0,0,0,0.08);
        }

        .recurso-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .recurso-type {
            background-color: #ebf8ff;
            color: #2b6cb0;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .recurso-price {
            font-size: 1.25rem;
            color: #2f855a;
            font-weight: bold;
        }

        .recurso-card h4 {
            margin: 0 0 0.75rem 0;
            font-size: 1.15rem;
            color: #2d3748;
        }

        .recurso-card p {
            margin: 0 0 1rem 0;
            font-size: 0.9rem;
            color: #4a5568;
            line-height: 1.4;
        }

        .recurso-meta {
            font-size: 0.8rem;
            color: #718096;
            background-color: #f7fafc;
            padding: 0.75rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .recurso-meta div {
            margin-bottom: 0.4rem;
        }

        .recurso-meta div:last-child {
            margin-bottom: 0;
        }

        .recurso-reserve-form {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .recurso-reserve-form input[type="number"] {
            width: 70px;
            padding: 0.4rem;
            border: 1px solid #cbd5e0;
            border-radius: 4px;
        }

        .presupuesto-box {
            background-color: #ebf8ff;
            border-left: 5px solid #3182ce;
            padding: 1.5rem;
            border-radius: 4px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .presupuesto-box h3 {
            margin-top: 0;
            color: #2b6cb0;
        }

        .presupuesto-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }

        .presupuesto-table th, .presupuesto-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #cbd5e0;
        }

        .presupuesto-table tr.total-row {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2d3748;
            background-color: #e2e8f0;
        }

        .reservas-table-container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            padding: 1.5rem;
            margin-bottom: 2rem;
            overflow-x: auto;
        }

        .reservas-table-container h3 {
            margin-top: 0;
            color: #004685;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 0.5rem;
        }

        .tab-reservas {
            width: 100%;
            border-collapse: collapse;
        }

        .tab-reservas th, .tab-reservas td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .tab-reservas th {
            background-color: #f7fafc;
            color: #4a5568;
            font-weight: bold;
        }

        .tab-reservas tr:hover {
            background-color: #f8fafc;
        }
    </style>
</head>
<body>
    <header>
        <h1>
            <a href="index.html">Las Palmas Desktop</a>
        </h1>
        <nav>
            <a href="index.html" title="Página de inicio">Inicio</a>
            <a href="gastronomia.html" title="Gastronomía">Gastronomía</a>
            <a href="rutas.html" title="Rutas">Rutas</a>
            <a href="meteorologia.html" title="Información de la meteorología">Meteorología</a>
            <a href="juego.html" title="Juego">Juego</a>
            <a href="reservas.php" title="Reservas" class="activo">Reservas</a>
            <a href="ayuda.html" title="Página de ayuda">Ayuda</a>
        </nav>
    </header>

    <p>
        Estas en: <a href="index.html">Inicio</a> >> <strong>Reservas</strong>
    </p>

    <main>
        <h2>Central de Reservas Turísticas</h2>

        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?php echo $tipoMensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <!-- Gestión de Sesión de Usuario -->
        <?php if (isset($_SESSION['usuario_id'])): ?>
            <div class="user-welcome-bar">
                <p>Sesión activa: <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?> (<?php echo htmlspecialchars($_SESSION['usuario_email']); ?>)</p>
                <form action="reservas.php" method="POST">
                    <input type="hidden" name="accion" value="logout" />
                    <button type="submit" class="btn btn-secondary">Cerrar Sesión</button>
                </form>
            </div>
        <?php else: ?>
            <!-- Formularios de Registro y Login para usuarios no autenticados -->
            <div class="auth-container">
                <!-- Registro -->
                <div class="panel-form">
                    <h3>Registro de Nuevo Usuario</h3>
                    <form action="reservas.php" method="POST">
                        <input type="hidden" name="accion" value="registro" />
                        <div class="form-group">
                            <label for="reg_nombre">Nombre Completo:</label>
                            <input type="text" id="reg_nombre" name="nombre" class="form-control" required />
                        </div>
                        <div class="form-group">
                            <label for="reg_email">Correo Electrónico:</label>
                            <input type="email" id="reg_email" name="email" class="form-control" required />
                        </div>
                        <div class="form-group">
                            <label for="reg_password">Contraseña:</label>
                            <input type="password" id="reg_password" name="password" class="form-control" required />
                        </div>
                        <button type="submit" class="btn">Registrarse</button>
                    </form>
                </div>

                <!-- Login -->
                <div class="panel-form">
                    <h3>Iniciar Sesión</h3>
                    <form action="reservas.php" method="POST">
                        <input type="hidden" name="accion" value="login" />
                        <div class="form-group">
                            <label for="login_email">Correo Electrónico:</label>
                            <input type="email" id="login_email" name="email" class="form-control" required />
                        </div>
                        <div class="form-group">
                            <label for="login_password">Contraseña:</label>
                            <input type="password" id="login_password" name="password" class="form-control" required />
                        </div>
                        <button type="submit" class="btn">Acceder</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Visualización del Presupuesto (si se ha generado) -->
        <?php if ($presupuesto): ?>
            <div class="presupuesto-box">
                <h3>Presupuesto Generado</h3>
                <p>Por favor, revisa los detalles antes de confirmar la reserva.</p>
                <table class="presupuesto-table">
                    <thead>
                        <tr>
                            <th>Recurso Turístico</th>
                            <th>Precio Unitario</th>
                            <th>Plazas Solicitadas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo htmlspecialchars($presupuesto['nombre_recurso']); ?></td>
                            <td><?php echo number_format($presupuesto['precio_unitario'], 2); ?> €</td>
                            <td><?php echo $presupuesto['plazas']; ?></td>
                        </tr>
                        <tr class="total-row">
                            <td colspan="2">Importe Total a Confirmar:</td>
                            <td><?php echo number_format($presupuesto['total'], 2); ?> €</td>
                        </tr>
                    </tbody>
                </table>
                <div style="display: flex; gap: 1rem;">
                    <form action="reservas.php" method="POST">
                        <input type="hidden" name="accion" value="confirmar_reserva" />
                        <input type="hidden" name="recurso_id" value="<?php echo $presupuesto['recurso_id']; ?>" />
                        <input type="hidden" name="plazas" value="<?php echo $presupuesto['plazas']; ?>" />
                        <button type="submit" class="btn btn-confirm">Confirmar y Pagar</button>
                    </form>
                    <a href="reservas.php" class="btn btn-secondary">Descartar Presupuesto</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Consulta de Reservas Realizadas -->
        <?php if (isset($_SESSION['usuario_id']) && !empty($reservasUsuario)): ?>
            <div class="reservas-table-container">
                <h3>Tus Recursos Reservados</h3>
                <table class="tab-reservas">
                    <thead>
                        <tr>
                            <th>Recurso Turístico</th>
                            <th>Fecha Actividad</th>
                            <th>Plazas Reservadas</th>
                            <th>Precio Unitario</th>
                            <th>Total Pagado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservasUsuario as $res): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($res['nombre_recurso']); ?></td>
                                <td><?php echo htmlspecialchars($res['fecha_inicio']); ?></td>
                                <td><?php echo $res['plazas_reservadas']; ?></td>
                                <td><?php echo number_format($res['precio'], 2); ?> €</td>
                                <td><?php echo number_format($res['total_pagar'], 2); ?> €</td>
                                <td>
                                    <form action="reservas.php" method="POST" onsubmit="return confirm('¿Seguro que deseas anular esta reserva?');">
                                        <input type="hidden" name="accion" value="anular_reserva" />
                                        <input type="hidden" name="reserva_id" value="<?php echo $res['id']; ?>" />
                                        <button type="submit" class="btn btn-danger">Anular Reserva</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Listado de Recursos Turísticos Disponibles -->
        <h3>Recursos Turísticos Disponibles</h3>
        <p>A continuación se detallan los recursos disponibles en Las Palmas que puedes reservar para tu visita:</p>
        
        <div class="recursos-grid">
            <?php foreach ($recursos as $rec): ?>
                <?php 
                $disponibles = $recursoModel->obtenerPlazasDisponibles($rec['id']);
                ?>
                <div class="recurso-card">
                    <div>
                        <div class="recurso-header">
                            <span class="recurso-type"><?php echo htmlspecialchars($rec['nombre_tipo']); ?></span>
                            <span class="recurso-price"><?php echo number_format($rec['precio'], 2); ?> €</span>
                        </div>
                        <h4><?php echo htmlspecialchars($rec['nombre']); ?></h4>
                        <p><?php echo htmlspecialchars($rec['descripcion']); ?></p>
                        
                        <div class="recurso-meta">
                            <div><strong>Inicio:</strong> <?php echo htmlspecialchars($rec['fecha_inicio']); ?></div>
                            <div><strong>Fin:</strong> <?php echo htmlspecialchars($rec['fecha_fin']); ?></div>
                            <div><strong>Capacidad Máxima:</strong> <?php echo $rec['capacidad_maxima']; ?> plazas</div>
                            <div><strong>Plazas Disponibles:</strong> <?php echo $disponibles; ?> plazas</div>
                            <div><strong>Gestionado por:</strong> <?php echo htmlspecialchars($rec['nombre_agencia']); ?></div>
                        </div>
                    </div>

                    <div>
                        <?php if (isset($_SESSION['usuario_id'])): ?>
                            <?php if ($disponibles > 0): ?>
                                <form action="reservas.php" method="POST" class="recurso-reserve-form">
                                    <input type="hidden" name="accion" value="generar_presupuesto" />
                                    <input type="hidden" name="recurso_id" value="<?php echo $rec['id']; ?>" />
                                    <label for="plazas_<?php echo $rec['id']; ?>" style="display:none;">Plazas:</label>
                                    <input type="number" id="plazas_<?php echo $rec['id']; ?>" name="plazas" min="1" max="<?php echo $disponibles; ?>" value="1" required />
                                    <button type="submit" class="btn">Reservar</button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-secondary" disabled style="width:100%;">Agotado</button>
                            <?php endif; ?>
                        <?php else: ?>
                            <p style="margin: 0; font-size: 0.85rem; color: #a0aec0; text-align: center;">Inicia sesión para reservar</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 - Proyecto de Software y Estándares para la Web</p>
        <p>
            Grado en Ingeniería Informática del Software - Escuela de Ingeniería
            Informática de Oviedo
        </p>
    </footer>
</body>
</html>
