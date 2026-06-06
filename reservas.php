<!--CHECKED STATIC CONTENT-->
<!--CHECKED DYNAMIC CONTENT-->
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

// Procesar descarte de presupuesto vía GET
if (isset($_GET['accion']) && $_GET['accion'] === 'descartar') {
    unset($_SESSION['presupuesto']);
    header("Location: reservas.php");
    exit();
}

$presupuesto = $_SESSION['presupuesto'] ?? [];

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
                        // Inicializar presupuesto en sesión si no existe
                        if (!isset($_SESSION['presupuesto'])) {
                            $_SESSION['presupuesto'] = [];
                        }

                        // Comprobar si el recurso ya está en el presupuesto para acumular plazas
                        $encontrado = false;
                        foreach ($_SESSION['presupuesto'] as &$item) {
                            if ($item['recurso_id'] === $id_recurso) {
                                $nuevas_plazas = $item['plazas'] + $plazas;
                                if ($nuevas_plazas > $disponibles) {
                                    $mensaje = "No hay suficientes plazas disponibles en total para añadir más. (Disponibles: {$disponibles})";
                                    $tipoMensaje = "error";
                                } else {
                                    $item['plazas'] = $nuevas_plazas;
                                    $item['total'] = $item['precio_unitario'] * $nuevas_plazas;
                                    $mensaje = "Se han añadido {$plazas} plazas adicionales a la actividad '{$recurso['nombre']}'.";
                                    $tipoMensaje = "success";
                                }
                                $encontrado = true;
                                break;
                            }
                        }
                        unset($item);

                        // Si no estaba en el presupuesto, agregarlo como nuevo item
                        if (!$encontrado) {
                            $total = $recurso['precio'] * $plazas;
                            $_SESSION['presupuesto'][] = [
                                'recurso_id' => $id_recurso,
                                'nombre_recurso' => $recurso['nombre'],
                                'precio_unitario' => $recurso['precio'],
                                'plazas' => $plazas,
                                'total' => $total
                            ];
                            $mensaje = "Actividad '{$recurso['nombre']}' añadida al presupuesto.";
                            $tipoMensaje = "success";
                        }

                        $presupuesto = $_SESSION['presupuesto'];
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
        } elseif (empty($_SESSION['presupuesto'])) {
            $mensaje = "No hay ningún presupuesto generado para confirmar.";
            $tipoMensaje = "error";
        } else {
            // Verificar disponibilidad de todos los recursos antes de confirmar
            $todoValido = true;
            $errores = [];
            
            foreach ($_SESSION['presupuesto'] as $item) {
                $id_recurso = $item['recurso_id'];
                $plazas = $item['plazas'];
                
                $recurso = $recursoModel->obtenerPorId($id_recurso);
                if (!$recurso) {
                    $todoValido = false;
                    $errores[] = "El recurso '{$item['nombre_recurso']}' ya no está disponible.";
                } else {
                    $disponibles = $recursoModel->obtenerPlazasDisponibles($id_recurso);
                    if ($plazas > $disponibles) {
                        $todoValido = false;
                        $errores[] = "No hay suficientes plazas para '{$item['nombre_recurso']}' (Solicitadas: {$plazas}, Disponibles: {$disponibles}).";
                    }
                }
            }
            
            if (!$todoValido) {
                $mensaje = implode(" | ", $errores);
                $tipoMensaje = "error";
            } else {
                // Registrar cada una de las actividades del presupuesto
                $exitos = 0;
                $fallos = 0;
                foreach ($_SESSION['presupuesto'] as $item) {
                    $reservaModel->id_usuario = $_SESSION['usuario_id'];
                    $reservaModel->id_recurso = $item['recurso_id'];
                    $reservaModel->plazas_reservadas = $item['plazas'];
                    $reservaModel->total_pagar = $item['total'];

                    if ($reservaModel->crear()) {
                        $exitos++;
                    } else {
                        $fallos++;
                    }
                }
                
                if ($fallos === 0) {
                    $mensaje = "¡Reserva confirmada con éxito! Se han registrado tus actividades.";
                    $tipoMensaje = "success";
                    unset($_SESSION['presupuesto']);
                    $presupuesto = [];
                } else {
                    $mensaje = "Se registraron {$exitos} reservas con éxito, pero fallaron {$fallos}.";
                    $tipoMensaje = "error";
                    unset($_SESSION['presupuesto']);
                    $presupuesto = [];
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
    <link rel="stylesheet" href="estilo/estilo.css" />

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
            <p>
                Mensaje: <?php echo htmlspecialchars($mensaje); ?>
            </p>
        <?php endif; ?>

        <!-- Gestión de Sesión de Usuario -->
        <?php if (isset($_SESSION['usuario_id'])): ?>
            <section>
                <h3>Sesión de Usuario</h3>
                <p>Sesión activa: <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?> (<?php echo htmlspecialchars($_SESSION['usuario_email']); ?>)</p>
                <form action="reservas.php" method="POST">
                    <input type="hidden" name="accion" value="logout" />
                    <button type="submit">Cerrar Sesión</button>
                </form>
            </section>
        <?php else: ?>
            <!-- Formularios de Registro y Login para usuarios no autenticados -->
            <!-- Registro -->
            <section>
                <h3>Registro de Nuevo Usuario</h3>
                <form action="reservas.php" method="POST">
                    <input type="hidden" name="accion" value="registro" />
                    <p>
                        <label>Nombre Completo:
                            <input type="text" name="nombre" required />
                        </label>
                    </p>
                    <p>
                        <label>Correo Electrónico:
                            <input type="email" name="email" required />
                        </label>
                    </p>
                    <p>
                        <label>Contraseña:
                            <input type="password" name="password" required />
                        </label>
                    </p>
                    <button type="submit">Registrarse</button>
                </form>
            </section>

            <!-- Login -->
            <section>
                <h3>Iniciar Sesión</h3>
                <form action="reservas.php" method="POST">
                    <input type="hidden" name="accion" value="login" />
                    <p>
                        <label>Correo Electrónico:
                            <input type="email" name="email" required />
                        </label>
                    </p>
                    <p>
                        <label>Contraseña:
                            <input type="password" name="password" required />
                        </label>
                    </p>
                    <button type="submit">Acceder</button>
                </form>
            </section>
        <?php endif; ?>

        <!-- Visualización del Presupuesto (si se ha generado) -->
        <?php if (!empty($presupuesto)): ?>
            <section>
                <h3>Presupuesto Generado</h3>
                <p>Por favor, revisa los detalles antes de confirmar la reserva.</p>
                <table>
                    <caption>Resumen de los recursos solicitados en tu presupuesto actual</caption>
                    <thead>
                        <tr>
                            <th scope="col" id="pres_recurso">Recurso Turístico</th>
                            <th scope="col" id="pres_precio">Precio Unitario</th>
                            <th scope="col" id="pres_plazas">Plazas Solicitadas</th>
                            <th scope="col" id="pres_importe">Importe</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalAcumulado = 0;
                        $i = 1;
                        foreach ($presupuesto as $item): 
                            $totalAcumulado += $item['total'];
                            $rowId = "pres_item" . $i;
                        ?>
                            <tr>
                                <th scope="row" id="<?php echo $rowId; ?>"><?php echo htmlspecialchars($item['nombre_recurso']); ?></th>
                                <td headers="pres_recurso <?php echo $rowId; ?> pres_precio"><?php echo number_format($item['precio_unitario'], 2); ?> €</td>
                                <td headers="pres_recurso <?php echo $rowId; ?> pres_plazas"><?php echo $item['plazas']; ?></td>
                                <td headers="pres_recurso <?php echo $rowId; ?> pres_importe"><?php echo number_format($item['total'], 2); ?> €</td>
                            </tr>
                        <?php 
                            $i++;
                        endforeach; 
                        ?>
                        <tr>
                            <th scope="row" id="pres_total_label" colspan="3">Importe Total a Confirmar:</th>
                            <td headers="pres_total_label pres_importe"><?php echo number_format($totalAcumulado, 2); ?> €</td>
                        </tr>
                    </tbody>
                </table>
                <form action="reservas.php" method="POST">
                    <input type="hidden" name="accion" value="confirmar_reserva" />
                    <button type="submit">Confirmar y Pagar</button>
                    <a href="reservas.php?accion=descartar">Descartar Presupuesto</a>
                </form>
            </section>
        <?php endif; ?>

        <!-- Consulta de Reservas Realizadas -->
        <?php if (isset($_SESSION['usuario_id']) && !empty($reservasUsuario)): ?>
            <section>
                <h3>Tus Recursos Reservados</h3>
                <table>
                    <caption>Listado de tus recursos turísticos reservados y confirmados</caption>
                    <thead>
                        <tr>
                            <th scope="col" id="res_recurso">Recurso Turístico</th>
                            <th scope="col" id="res_fecha">Fecha Actividad</th>
                            <th scope="col" id="res_plazas">Plazas Reservadas</th>
                            <th scope="col" id="res_precio">Precio Unitario</th>
                            <th scope="col" id="res_total">Total Pagado</th>
                            <th scope="col" id="res_acciones">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $j = 1;
                        foreach ($reservasUsuario as $res): 
                            $rowId = "res_item" . $j;
                        ?>
                            <tr>
                                <th scope="row" id="<?php echo $rowId; ?>"><?php echo htmlspecialchars($res['nombre_recurso']); ?></th>
                                <td headers="res_recurso <?php echo $rowId; ?> res_fecha"><?php echo htmlspecialchars($res['fecha_inicio']); ?></td>
                                <td headers="res_recurso <?php echo $rowId; ?> res_plazas"><?php echo $res['plazas_reservadas']; ?></td>
                                <td headers="res_recurso <?php echo $rowId; ?> res_precio"><?php echo number_format($res['precio'], 2); ?> €</td>
                                <td headers="res_recurso <?php echo $rowId; ?> res_total"><?php echo number_format($res['total_pagar'], 2); ?> €</td>
                                <td headers="res_recurso <?php echo $rowId; ?> res_acciones">
                                    <form action="reservas.php" method="POST" onsubmit="return confirm('¿Seguro que deseas anular esta reserva?');">
                                        <input type="hidden" name="accion" value="anular_reserva" />
                                        <input type="hidden" name="reserva_id" value="<?php echo $res['id']; ?>" />
                                        <button type="submit">Anular Reserva</button>
                                    </form>
                                </td>
                            </tr>
                        <?php 
                            $j++;
                        endforeach; 
                        ?>
                    </tbody>
                </table>
            </section>
        <?php endif; ?>

        <!-- Listado de Recursos Turísticos Disponibles -->
        <section>
            <h3>Recursos Turísticos Disponibles</h3>
            <p>A continuación se detallan los recursos disponibles en Las Palmas que puedes reservar para tu visita:</p>
            
            <?php foreach ($recursos as $rec): ?>
                <?php 
                $disponibles = $recursoModel->obtenerPlazasDisponibles($rec['id']);
                ?>
                <article>
                    <p>
                        <span><?php echo htmlspecialchars($rec['nombre_tipo']); ?></span> - 
                        <?php echo number_format($rec['precio'], 2); ?> €
                    </p>
                    <h4><?php echo htmlspecialchars($rec['nombre']); ?></h4>
                    <p><?php echo htmlspecialchars($rec['descripcion']); ?></p>
                    
                    <ul>
                        <li>Inicio: <?php echo htmlspecialchars($rec['fecha_inicio']); ?></li>
                        <li>Fin: <?php echo htmlspecialchars($rec['fecha_fin']); ?></li>
                        <li>Capacidad Máxima: <?php echo $rec['capacidad_maxima']; ?> plazas</li>
                        <li>Plazas Disponibles: <?php echo $disponibles; ?> plazas</li>
                        <li>Gestionado por: <?php echo htmlspecialchars($rec['nombre_agencia']); ?></li>
                    </ul>

                    <?php if (isset($_SESSION['usuario_id'])): ?>
                        <?php if ($disponibles > 0): ?>
                            <form action="reservas.php" method="POST">
                                <input type="hidden" name="accion" value="generar_presupuesto" />
                                <input type="hidden" name="recurso_id" value="<?php echo $rec['id']; ?>" />
                                <label>Plazas:
                                    <input type="number" name="plazas" min="1" max="<?php echo $disponibles; ?>" value="1" required />
                                </label>
                                <button type="submit">Reservar</button>
                            </form>
                        <?php else: ?>
                            <button disabled>Agotado</button>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>Inicia sesión para reservar</p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>
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
