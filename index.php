<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
$message = "";

include("database.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validar y sanitizar datos
    $correo = filter_input(INPUT_POST, 'EMAIL', FILTER_SANITIZE_EMAIL);
    $nombre_usuario = isset($_POST['NOMBRE_USUARIO']) ? strip_tags($_POST['NOMBRE_USUARIO']) : '';
    $contrasena = $_POST['CONTRASENA'] ?? ''; // Evitar warning si no existe

    // Verificar que los campos obligatorios no estén vacíos
    if (empty($correo) && empty($nombre_usuario)) {
        $message = "Debes ingresar un correo o nombre de usuario.";
    } else {

        // Buscar usuario por EMAIL O NOMBRE_USUARIO
        $sql = "SELECT * FROM usuarios WHERE EMAIL = ? OR NOMBRE_USUARIO = ?";
        $stmt = $conn->prepare($sql);

        // Pasar ambos parámetros (correo y nombre_usuario)
        $stmt->bind_param("ss", $correo, $nombre_usuario);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            // Comparar contraseña hasheada (SHA-256, como en tu código original)
            if (hash('sha256', $contrasena) === $row['CONTRASENA']) {
                $_SESSION['NOMBRE_USUARIO'] = $row['NOMBRE_USUARIO']; // Asegúrate de que el campo se llama 'NOMBRE'
                $_SESSION['id_rol'] = $row['id_rol']; // Guardar el rol del usuario
                $_SESSION['ID_USUARIO'] = $row['id']; // Guardar el ID del usuario
                header("Location: ot.php");
                exit();
            } else {
                $message = "<center>Contraseña incorrecta.<br>Contacte al administrador.<center>";
            }
        } else {
            $message = "No se encontró el usuario.";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Sistema | NERA Chile</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <style>
        :root {
            --primary-color: #0d6efd;
            --primary-hover: #0b5ed7;
            --bg-gradient: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-border: rgba(255, 255, 255, 0.5);
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            overflow: hidden;
        }

        /* Fondo animado sutil */
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.8) 0%, transparent 60%);
            animation: rotateBg 30s linear infinite;
            z-index: -1;
        }

        @keyframes rotateBg {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .login-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            box-shadow: var(--card-shadow);
            padding: 3rem;
            width: 100%;
            max-width: 420px;
            transform: translateY(0);
            transition: all 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
        }

        .logo-container {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-img {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 50%;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
            border: 3px solid white;
        }

        .app-title {
            font-weight: 700;
            color: #1a1a1a;
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }

        .app-subtitle {
            color: #6c757d;
            font-size: 0.875rem;
        }

        .form-control {
            background-color: #f8f9fa;
            border: 2px solid transparent;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            padding-left: 2.75rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            background-color: white;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
            transition: color 0.2s;
            z-index: 5;
        }

        .form-control:focus+.input-icon,
        .form-floating:focus-within .input-icon {
            /* Fallback */
            color: var(--primary-color);
        }

        /* Truco para colorear ícono al foco del input hermano anterior */
        .input-group:focus-within .input-icon {
            color: var(--primary-color);
        }

        .btn-login {
            background: var(--primary-color);
            border: none;
            border-radius: 12px;
            padding: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            border: 2px solid transparent;
            /* Para evitar saltos */
            transition: all 0.2s;
        }

        .btn-login:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.25);
        }

        .alert-custom {
            border-radius: 12px;
            font-size: 0.9rem;
            border: none;
        }

        .footer-text {
            text-align: center;
            margin-top: 2rem;
            color: #adb5bd;
            font-size: 0.8rem;
        }
    </style>
</head>

<body>
    <div class="container d-flex justify-content-center">
        <div class="login-card animate__animated animate__fadeInUp">

            <div class="logo-container">
                <img src="logo/logo-login.png" alt="NERA Chile" class="logo-img">
                <h1 class="app-title">Bienvenido</h1>
                <p class="app-subtitle">Ingresa tus credenciales para continuar</p>
            </div>

            <?php if ($message != ""): ?>
                <div class="alert alert-danger alert-custom alert-dismissible fade show d-flex align-items-center"
                    role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <div><?php echo $message; ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <div class="mb-4 position-relative input-group">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" class="form-control" name="NOMBRE_USUARIO" placeholder="Usuario" required
                        autocomplete="username">
                </div>

                <div class="mb-4 position-relative input-group">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" class="form-control" name="CONTRASENA" placeholder="Contraseña" required
                        autocomplete="current-password">
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="rememberMe">
                        <label class="form-check-label text-secondary small" for="rememberMe">
                            Recordarme
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 btn-login">
                    INICIAR SESIÓN <i class="fas fa-arrow-right ms-2"></i>
                </button>
            </form>

            <div class="footer-text">
                &copy; <?php echo date("Y"); ?> Sistema de Gestión. v2.0
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>