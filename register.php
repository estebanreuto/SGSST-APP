<?php
require_once "config/db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Validación legal
    if (!isset($_POST["autorizacion"]) || $_POST["autorizacion"] !== "si") {
        die("Debe aceptar la autorización de datos personales.");
    }

    // =========================
    // INSERT USUARIO
    // =========================
    $sql = "INSERT INTO usuarios (
        nombre, apellido, cedula, email, telefono, rol,
        licencia_sst, tipo_licencia, numero_licencia, fecha_licencia, expedida_por,
        direccion, ciudad, barrio, localidad, firma
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $_POST["nombre"],
        $_POST["apellido"],
        $_POST["cedula"],
        $_POST["email"],
        $_POST["telefono"],
        $_POST["rol"],

        $_POST["licencia"] ?? null,
        $_POST["tipoLicencia"] ?? null,
        $_POST["numLicencia"] ?? null,
        $_POST["fechaLicencia"] ?? null,
        $_POST["expedida"] ?? null,

        $_POST["direccion"] ?? null,
        $_POST["ciudad"] ?? null,
        $_POST["barrio"] ?? null,
        $_POST["localidad"] ?? null,

        $_POST["firmaDigital"]
    ]);

    $usuario_id = $conn->lastInsertId();

    // =========================
    // INSERT ENCUESTA (SOLO TRABAJADOR)
    // =========================
    if (isset($_POST["rol"]) && $_POST["rol"] === "trabajador") {

        $sql2 = "INSERT INTO encuesta_sociodemografica (
            usuario_id, edad, estado_civil, genero, personas_cargo,
            escolaridad, vivienda, tiempo_libre, experiencia, estrato,
            convive_con, raza, tipo_contrato, turno, antiguedad,
            enfermedad, fuma, alcohol, deporte, tipo_personal
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

        $stmt2 = $conn->prepare($sql2);
        $stmt2->execute([
            $usuario_id,
            $_POST["edad"],
            $_POST["estado_civil"],
            $_POST["genero"],
            $_POST["personas_cargo"],
            $_POST["escolaridad"],
            $_POST["vivienda"],
            $_POST["tiempo_libre"],
            $_POST["experiencia"],
            $_POST["estrato"],
            $_POST["convive_con"],
            $_POST["raza"],
            $_POST["tipo_contrato"],
            $_POST["turno"],
            $_POST["antiguedad"],
            $_POST["enfermedad"],
            $_POST["fuma"],
            $_POST["alcohol"],
            $_POST["deporte"],
            $_POST["tipo_personal"]
        ]);
    }

    header("Location: login.php?registro=ok");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Registro | SG-SST</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            /* PALETA DE COLORES PREMIUM (Glassmorphism) */
            --primary: #ff8a1f;
            --primary2: #ff7a00;
            --bg-top: #e8f0f8;
            --bg-mid: #f3f7fb;
            --bg-bottom: #ffffff;
            --blue-main: #2b5a9e;
            --blue-dark: #1e3a8a;
            --card-bg: rgba(255, 255, 255, 0.85);
            --card-border: rgba(255, 255, 255, 0.6);
            --shadow-soft: 0 25px 50px -12px rgba(43, 90, 158, 0.15);
            --text-main: #1f2d3d;
            --text-muted: #5f6f82;
            --border: #dbe3ec;
            --radius: 18px;
        }

        * {
            box-sizing: border-box
        }

        body {
            margin: 0;
            font-family: Inter, sans-serif;
            background: linear-gradient(135deg, var(--bg-top) 0%, var(--bg-mid) 50%, var(--bg-bottom) 100%);
            color: var(--text-main);
            overflow-x: hidden;
            position: relative;
        }

        /* FIGURAS FLOTANTES DE FONDO */
        .blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            z-index: -1;
            opacity: 0.35;
            animation: float 12s infinite ease-in-out alternate;
        }
        .blob-1 { top: -5%; left: -5%; width: 500px; height: 500px; background: var(--blue-main); }
        .blob-2 { bottom: -10%; right: -5%; width: 600px; height: 600px; background: var(--primary); animation-delay: -6s; }

        @keyframes float {
            0% { transform: translateY(0px) scale(1); }
            100% { transform: translateY(40px) scale(1.05); }
        }

        /* ===== LAYOUT ===== */
        .wrapper {
            display: grid;
            grid-template-columns: 42% 58%;
            height: 100vh;
            overflow: hidden;
        }

        /* ===== BRAND ===== */
        .brand {
            padding: 64px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            z-index: 2;
        }

        .brand h1 {
            margin: 0;
            font-size: 2rem;
            line-height: 1.15;
            color: var(--blue-dark);
            font-weight: 800;
        }

        .brand h1 span {
            background: linear-gradient(135deg, var(--primary), #ff5e00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand p {
            margin: 16px 0 0;
            color: var(--text-muted);
            font-size: 1rem;
            max-width: 440px;
        }

        /* ===== FORM AREA ===== */
        .form-area {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 64px;
            overflow-y: auto;
            height: 100vh;
            z-index: 2;
        }

        .form-area::-webkit-scrollbar { width: 0; }

        .card {
            width: 100%;
            max-width: 1040px;
            margin: auto;
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-soft);
            padding: 28px 32px 24px;
        }

        .header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px;
        }

        .header h2 {
            margin: 0;
            font-size: 1.35rem;
            color: var(--blue-dark);
            font-weight: 800;
        }

        .header .hint {
            margin: 4px 0 0;
            font-size: .8rem;
            color: var(--text-muted);
        }

        /* ===== GRID FORM ===== */
        .grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px 16px;
        }

        /* ===== INPUTS ===== */
        .field label {
            display: block;
            font-size: .72rem;
            font-weight: 600;
            margin: 0 0 4px;
        }

        .label-opt {
            font-weight: 400;
            color: #94a3b8;
            font-size: 0.65rem;
        }

        .control {
            position: relative;
        }

        .icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            opacity: .45;
            color: #94a3b8;
            pointer-events: none;
            transition: all .2s ease;
        }

        input,
        select {
            width: 100%;
            padding: 10px 12px 10px 36px;
            font-size: .82rem;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: #ffffff;
            color: var(--text-main);
            transition: all .2s ease;
            font-family: inherit;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 138, 31, .14);
        }

        input:focus ~ .icon,
        select:focus ~ .icon {
            opacity: .75;
            color: var(--primary);
        }

        /* ===== SECTIONS ===== */
        .section {
            margin-top: 20px;
            border: 1px solid var(--card-border);
            background: rgba(255, 255, 255, 0.5);
            border-radius: 16px;
            padding: 16px;
        }

        .section-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .section-title h3 {
            margin: 0;
            font-size: .95rem;
            color: var(--blue-dark);
        }

        .badge {
            font-size: .72rem;
            background: rgba(43, 90, 158, 0.1);
            color: var(--blue-main);
            padding: 6px 12px;
            border-radius: 999px;
            font-weight: 600;
        }

        /* LEGAL */
        .legal {
            margin-top: 20px;
            background: rgba(241, 245, 249, 0.6);
            border: 1px dashed var(--border);
            border-radius: 16px;
            padding: 16px;
        }

        .legal p {
            margin: 0 0 10px;
            font-size: .78rem;
            color: var(--text-muted);
            line-height: 1.4;
        }

        /* FIRMA */
        .signature {
            margin-top: 20px;
        }

        .signature h3 {
            margin: 0 0 10px;
            font-size: .95rem;
            color: var(--blue-dark);
        }

        canvas {
            width: 100%;
            height: 120px;
            border: 1px dashed var(--border);
            border-radius: 14px;
            background: #fff;
        }

        #trabajador {
            max-height: 540px;
            overflow-y: auto;
            padding-right: 8px;
        }

        #trabajador::-webkit-scrollbar { width: 8px; }
        #trabajador::-webkit-scrollbar-track { background: transparent; }
        #trabajador::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        #trabajador::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        @media(max-width:1200px) {
            .wrapper { grid-template-columns: 1fr; height: auto; }
            .brand { padding: 40px 40px 20px; text-align: center; }
            .brand p { margin: 16px auto 0; }
            .form-area { padding: 20px 40px 60px; height: auto; overflow: visible; }
        }

        @media(max-width:900px) {
            .grid { grid-template-columns: repeat(2, 1fr) }
        }

        @media(max-width:560px) {
            .form-area { padding: 20px }
            .card { padding: 20px }
            .grid { grid-template-columns: 1fr }
        }

        .hidden { display: none }

        /* ===== FOOTER ===== */
        .footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border);
            flex-wrap: wrap;
            gap: 12px;
        }

        .footer a {
            text-decoration: none;
            font-size: .875rem;
            color: var(--blue-main);
            font-weight: 600;
            padding: 10px 16px;
            border-radius: 8px;
            transition: all .25s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .footer a:hover {
            background: rgba(43, 90, 158, 0.1);
            color: var(--blue-dark);
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        button {
            border: none;
            padding: 11px 26px;
            font-size: .875rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all .25s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .08);
        }

        .btn-danger {
            background: #ffffff;
            border: 1px solid var(--border);
            color: var(--text-muted);
        }

        .btn-danger:hover {
            background: #f1f5f9;
            color: var(--text-main);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary2));
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(255, 138, 31, .25);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 138, 31, .35);
        }
    </style>
</head>

<body>

    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="wrapper">

        <div class="brand">
            <h1>Gestión <span>inteligente</span><br>de Seguridad y Salud</h1>
            <p>
                Centraliza tu SG-SST, cumple la normatividad y toma decisiones basadas
                en datos reales desde una sola plataforma.
            </p>
        </div>

        <div class="form-area">
            <div class="card">

                <div class="header">
                    <div>
                        <h2>Registro de Usuario</h2>
                        <div class="hint">Completa los datos. Si eliges <b>Trabajador</b>, se habilita la encuesta
                            sociodemográfica.</div>
                    </div>
                </div>

                <form method="POST" onsubmit="return beforeSubmit()">

                    <div class="grid">
                        <div class="field">
                            <label>Nombre</label>
                            <div class="control">
                                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21a8 8 0 0 0-16 0" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                                <input name="nombre" required>
                            </div>
                        </div>

                        <div class="field">
                            <label>Apellido</label>
                            <div class="control">
                                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21a8 8 0 0 0-16 0" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                                <input name="apellido" required>
                            </div>
                        </div>

                        <div class="field">
                            <label>Cédula</label>
                            <div class="control">
                                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="16" rx="2" />
                                    <path d="M7 8h10M7 12h10M7 16h6" />
                                </svg>
                                <input name="cedula" required>
                            </div>
                        </div>

                        <div class="field">
                            <label>Email</label>
                            <div class="control">
                                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h16v16H4z" />
                                    <path d="m4 6 8 7 8-7" />
                                </svg>
                                <input type="email" name="email" required>
                            </div>
                        </div>

                        <div class="field">
                            <label>Teléfono</label>
                            <div class="control">
                                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.08 4.18 2 2 0 0 1 4.06 2h3a2 2 0 0 1 2 1.72c.12.86.31 1.7.57 2.5a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.58-1.09a2 2 0 0 1 2.11-.45c.8.26 1.64.45 2.5.57A2 2 0 0 1 22 16.92z" />
                                </svg>
                                <input name="telefono" required>
                            </div>
                        </div>

                        <div class="field">
                            <label>Rol</label>
                            <div class="control">
                                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5z" />
                                    <path d="M20 21a8 8 0 0 0-16 0" />
                                </svg>
                                <select name="rol" id="rol" onchange="mostrar()" required>
                                    <option value="">---</option>
                                    <option value="representante">Representante Legal</option>
                                    <option value="sst">Responsable SST</option>
                                    <option value="trabajador">Trabajador</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="section">
                        <div class="section-title">
                            <h3>Datos de Residencia</h3>
                            <span class="badge">Todos los roles</span>
                        </div>
                        <div class="grid">
                            <div class="field">
                                <label>Dirección</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M3 11 12 2l9 9" />
                                        <path d="M5 10v11h14V10" />
                                    </svg>
                                    <input name="direccion">
                                </div>
                            </div>

                            <div class="field">
                                <label>Ciudad / Municipio</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M3 21h18" />
                                        <path d="M7 21V7l5-4 5 4v14" />
                                        <path d="M9 9h6M9 12h6M9 15h6" />
                                    </svg>
                                    <input name="ciudad" required placeholder="Ej. Tame, Arauca">
                                </div>
                            </div>

                            <div class="field">
                                <label>Barrio / Corregimiento <span class="label-opt">(Opcional)</span></label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 21s7-4.5 7-10a7 7 0 1 0-14 0c0 5.5 7 10 7 10z" />
                                        <circle cx="12" cy="11" r="2" />
                                    </svg>
                                    <input name="barrio" placeholder="Si aplica">
                                </div>
                            </div>

                            <div class="field">
                                <label>Localidad / Vereda <span class="label-opt">(Opcional)</span></label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0z" />
                                        <circle cx="12" cy="10" r="3" />
                                    </svg>
                                    <input name="localidad" placeholder="Si aplica">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="sst" class="section hidden">
                        <div class="section-title">
                            <h3>Responsable SST</h3>
                            <span class="badge">Campos SST</span>
                        </div>

                        <div class="grid">
                            <div class="field">
                                <label>Licencia SST</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M12 2 3 6v6c0 5 3.8 9.7 9 10 5.2-.3 9-5 9-10V6z" />
                                    </svg>
                                    <select name="licencia">
                                        <option>No</option>
                                        <option>Sí</option>
                                    </select>
                                </div>
                            </div>

                            <div class="field">
                                <label>Tipo de Licencia</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20" />
                                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                                    </svg>
                                    <input name="tipoLicencia" placeholder="Ej: Profesional">
                                </div>
                            </div>

                            <div class="field">
                                <label>Número de Licencia</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M7 7h10M7 12h10M7 17h6" />
                                        <rect x="3" y="4" width="18" height="16" rx="2" />
                                    </svg>
                                    <input name="numLicencia">
                                </div>
                            </div>

                            <div class="field">
                                <label>Fecha de Expedición</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <rect x="3" y="4" width="18" height="18" rx="2" />
                                        <path d="M16 2v4M8 2v4M3 10h18" />
                                    </svg>
                                    <input type="date" name="fechaLicencia">
                                </div>
                            </div>

                            <div class="field">
                                <label>Expedida por</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M12 3v18" />
                                        <path d="M5 8h14" />
                                        <path d="M5 16h14" />
                                    </svg>
                                    <input name="expedida" placeholder="Entidad">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="trabajador" class="section hidden">
                        <div class="section-title">
                            <h3>Encuesta Sociodemográfica</h3>
                            <span class="badge">Solo Trabajador</span>
                        </div>

                        <div class="grid">
                            <div class="field"><label>Edad</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M8 2h8M9 2v4l-2 2v2a5 5 0 0 0 10 0V8l-2-2V2" />
                                    </svg>
                                    <select name="edad" id="edadSelect">
                                        <option value="">Seleccione</option>
                                        <option>18 a 29 años</option>
                                        <option>30 a 39 años</option>
                                        <option>40 a 49 años</option>
                                        <option>50 a 59 años</option>
                                        <option>60 o más años</option>
                                    </select>
                                </div>
                            </div>

                            <div class="field"><label>Estado civil</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M12 21s-8-4.5-8-11a5 5 0 0 1 9-3 5 5 0 0 1 9 3c0 6.5-10 11-10 11z" />
                                    </svg>
                                    <select name="estado_civil" id="civilSelect">
                                        <option value="">Seleccione</option>
                                        <option>Soltero(a)</option>
                                        <option>Casado(a) / Unión libre</option>
                                        <option>Separado(a)</option>
                                        <option>Viudo(a)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="field"><label>Género</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <circle cx="12" cy="7" r="4" />
                                        <path d="M5.5 21a8.5 8.5 0 0 1 13 0" />
                                    </svg>
                                    <select name="genero" id="generoSelect">
                                        <option value="">Seleccione</option>
                                        <option>Masculino</option>
                                        <option>Femenino</option>
                                    </select>
                                </div>
                            </div>

                            <div class="field"><label>Personas a cargo</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                        <circle cx="9" cy="7" r="4" />
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                    </svg>
                                    <select name="personas_cargo" id="personasSelect">
                                        <option value="">Seleccione</option>
                                        <option>Ninguna</option>
                                        <option>1 a 3 personas</option>
                                        <option>4 a 6 personas</option>
                                        <option>Más de 6 personas</option>
                                    </select>
                                </div>
                            </div>

                            <div class="field"><label>Escolaridad</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M22 10 12 5 2 10l10 5 10-5z" />
                                        <path d="M6 12v5c0 1.66 2.69 3 6 3s6-1.34 6-3v-5" />
                                    </svg>
                                    <select name="escolaridad" id="escolaridadSelect">
                                        <option value="">Seleccione</option>
                                        <option>Primaria</option>
                                        <option>Secundaria</option>
                                        <option>Técnico / Tecnólogo</option>
                                        <option>Universitario</option>
                                        <option>Especialista / Magister</option>
                                    </select>
                                </div>
                            </div>

                            <div class="field"><label>Vivienda</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M3 11 12 2l9 9" />
                                        <path d="M5 10v11h14V10" />
                                    </svg>
                                    <select name="vivienda" id="viviendaSelect">
                                        <option value="">Seleccione</option>
                                        <option>Propia</option>
                                        <option>Arrendada</option>
                                        <option>Familiar</option>
                                        <option>Compartida con otra familia</option>
                                    </select>
                                </div>
                            </div>

                            <div class="field"><label>Tiempo libre</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <circle cx="12" cy="12" r="10" />
                                        <path d="M12 6v6l4 2" />
                                    </svg>
                                    <select name="tiempo_libre" id="tiempoSelect">
                                        <option value="">Seleccione</option>
                                        <option>Otro trabajo</option>
                                        <option>Labor doméstica</option>
                                        <option>Recreación y deporte</option>
                                        <option>Estudio</option>
                                        <option>Ninguno</option>
                                    </select>
                                </div>
                            </div>

                            <div class="field"><label>Experiencia</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <rect x="2" y="7" width="20" height="14" rx="2" />
                                        <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2" />
                                    </svg>
                                    <select name="experiencia" id="experienciaSelect">
                                        <option value="">Seleccione</option>
                                        <option>Menos de 1 año</option>
                                        <option>1 a 5 años</option>
                                        <option>5 a 10 años</option>
                                        <option>10 a 15 años</option>
                                        <option>Más de 15 años</option>
                                    </select>
                                </div>
                            </div>

                            <div class="field"><label>Estrato</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M12 2v20" />
                                        <path d="M7 7h10" />
                                        <path d="M7 12h10" />
                                        <path d="M7 17h10" />
                                    </svg>
                                    <select name="estrato" id="estratoSelect">
                                        <option value="">Seleccione</option>
                                        <option>1</option>
                                        <option>2</option>
                                        <option>3</option>
                                        <option>4</option>
                                        <option>5</option>
                                        <option>6</option>
                                    </select>
                                </div>
                            </div>

                            <div class="field"><label>Convive con</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                        <circle cx="12" cy="7" r="4" />
                                    </svg>
                                    <select name="convive_con" id="conviveSelect">
                                        <option value="">Seleccione</option>
                                        <option>Pareja</option>
                                        <option>Pareja e hijos</option>
                                        <option>Pareja, hijos, padres</option>
                                        <option>Hijos</option>
                                        <option>Padres</option>
                                        <option>Padres e hijos</option>
                                        <option>Hermanos o padres</option>
                                        <option>Solo</option>
                                    </select>
                                </div>
                            </div>

                            <div class="field"><label>Raza</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2z" />
                                        <path d="M2 12h20" />
                                    </svg>
                                    <select name="raza" id="razaSelect">
                                        <option value="">Seleccione</option>
                                        <option>Mestizo</option>
                                        <option>Mulato</option>
                                        <option>Negro</option>
                                    </select>
                                </div>
                            </div>

                            <div class="field"><label>Tipo contrato</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                        <path d="M14 2v6h6" />
                                    </svg>
                                    <select name="tipo_contrato" id="contratoSelect">
                                        <option value="">Seleccione</option>
                                        <option>Fijo</option>
                                        <option>Indefinido</option>
                                        <option>Obra labor</option>
                                        <option>Prestación de Servicios</option>
                                    </select>
                                </div>
                            </div>

                            <div class="field"><label>Turno</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M12 6v6l4 2" />
                                        <circle cx="12" cy="12" r="10" />
                                    </svg>
                                    <select name="turno" id="turnoSelect">
                                        <option value="">Seleccione</option>
                                        <option>Oficina 08:00 am - 05:00 pm</option>
                                        <option>Proyecto 07:00 am - 04:00 pm / Sábado medio día</option>
                                        <option>Sala de Ventas 09:00 am - 05:00 pm</option>
                                    </select>
                                </div>
                            </div>

                            <div class="field"><label>Antigüedad</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M3 3v18h18" />
                                        <path d="M7 14l4-4 3 3 5-5" />
                                    </svg>
                                    <select name="antiguedad" id="antiguedadSelect">
                                        <option value="">Seleccione</option>
                                        <option>Menor a 1 año</option>
                                        <option>1 a 3 años</option>
                                        <option>3 a 5 años</option>
                                        <option>Más de 5 años</option>
                                    </select>
                                </div>
                            </div>

                            <div class="field"><label>Enfermedad</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M12 21s-8-4.5-8-11a5 5 0 0 1 9-3 5 5 0 0 1 9 3c0 6.5-10 11-10 11z" />
                                    </svg>
                                    <select name="enfermedad" id="enfermedadSelect">
                                        <option value="">Seleccione</option>
                                        <option>No me han diagnosticado ninguna</option>
                                        <option>Otras</option>
                                    </select>
                                </div>
                            </div>

                            <div class="field"><label>Fuma</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M2 12h20" />
                                        <path d="M2 16h20" />
                                        <path d="M6 12v4M10 12v4M14 12v4M18 12v4" />
                                    </svg>
                                    <select name="fuma" id="fumaSelect">
                                        <option value="">Seleccione</option>
                                        <option>No fumo</option>
                                        <option>Otras</option>
                                    </select>
                                </div>
                            </div>

                            <div class="field"><label>Alcohol</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M8 2h8" />
                                        <path d="M9 2v6l-3 5a4 4 0 0 0 3 6h6a4 4 0 0 0 3-6l-3-5V2" />
                                    </svg>
                                    <select name="alcohol" id="alcoholSelect">
                                        <option value="">Seleccione</option>
                                        <option>No consumo</option>
                                        <option>Semanal</option>
                                        <option>Quincenal</option>
                                        <option>Mensual</option>
                                        <option>Ocasional</option>
                                    </select>
                                </div>
                            </div>

                            <div class="field"><label>Deporte</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <circle cx="12" cy="12" r="10" />
                                        <path d="M12 2a10 10 0 0 0 0 20" />
                                    </svg>
                                    <select name="deporte" id="deporteSelect">
                                        <option value="">Seleccione</option>
                                        <option>No practico</option>
                                        <option>Diario</option>
                                        <option>Semanal</option>
                                        <option>Quincenal</option>
                                        <option>Mensual</option>
                                        <option>Ocasional</option>
                                    </select>
                                </div>
                            </div>

                            <div class="field"><label>Tipo personal</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M12 2l3 7h7l-5.5 4 2 7L12 16l-6.5 4 2-7L2 9h7z" />
                                    </svg>
                                    <select name="tipo_personal" id="personalSelect">
                                        <option value="">Seleccione</option>
                                        <option>Personal no conductor</option>
                                        <option>Mensajero motorizado</option>
                                        <option>Conductor</option>
                                        <option>Operario</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="legal">
                        <p>
                            Autorizo el tratamiento de datos personales por parte del Responsable del Tratamiento,
                            conforme a la Ley 1581 de 2012.
                        </p>
                        <div class="grid">
                            <div class="field">
                                <label>Acepto la autorización</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M9 12l2 2 4-4" />
                                        <path d="M20 12a8 8 0 1 1-16 0 8 8 0 0 1 16 0z" />
                                    </svg>
                                    <select name="autorizacion" required>
                                        <option value="">---</option>
                                        <option value="si">Sí</option>
                                        <option value="no">No</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="signature">
                        <h3>Firma Digital</h3>
                        <canvas id="firma"></canvas>
                        <input type="hidden" name="firmaDigital" id="firmaDigital">

                        <div class="footer">
                            <a href="login.php">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z"></path></svg>
                                Ya tengo cuenta
                            </a>
                            <div class="actions">
                                <button type="button" class="btn-danger" onclick="limpiar()">Limpiar</button>
                                <button type="submit" class="btn-primary" onclick="guardarFirma()">Registrar</button>
                            </div>
                        </div>
                    </div>

                </form>

            </div>
        </div>

    </div>

    <script>
        function mostrar() {
            const rol = document.getElementById("rol").value;

            document.getElementById("sst").classList.add("hidden");
            document.getElementById("trabajador").classList.add("hidden");
            
            // Si es trabajador, activamos que los selects de la encuesta sean requeridos
            const selectsTrabajador = document.querySelectorAll('#trabajador select');
            
            if (rol === "sst") {
                document.getElementById("sst").classList.remove("hidden");
                selectsTrabajador.forEach(s => s.removeAttribute('required'));
            } else if (rol === "trabajador") {
                document.getElementById("trabajador").classList.remove("hidden");
                selectsTrabajador.forEach(s => s.setAttribute('required', 'required'));
            } else {
                selectsTrabajador.forEach(s => s.removeAttribute('required'));
            }
        }

        // ===== Firma =====
        const canvas = document.getElementById("firma");
        const ctx = canvas.getContext("2d");
        let draw = false;

        function resizeCanvas() {
            const rect = canvas.getBoundingClientRect();
            canvas.width = rect.width;
            canvas.height = rect.height;
            // MAGIA BLANCA: Aseguramos que el fondo de la firma siempre sea blanco y NO transparente
            ctx.fillStyle = "#ffffff";
            ctx.fillRect(0, 0, canvas.width, canvas.height);
        }
        window.addEventListener("resize", resizeCanvas);
        
        // Llamado inicial
        setTimeout(resizeCanvas, 50);

        function getPos(e) {
            const rect = canvas.getBoundingClientRect();
            if (e.touches && e.touches.length > 0) {
                return { x: e.touches[0].clientX - rect.left, y: e.touches[0].clientY - rect.top };
            }
            return { x: e.clientX - rect.left, y: e.clientY - rect.top };
        }

        function start(e) {
            e.preventDefault();
            draw = true;
            const p = getPos(e);
            ctx.beginPath();
            ctx.moveTo(p.x, p.y);
        }
        function move(e) {
            if (!draw) return;
            e.preventDefault();
            const p = getPos(e);
            ctx.lineWidth = 2;
            ctx.lineCap = "round";
            ctx.strokeStyle = "#111";
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
        }
        function end(e) {
            if (e) e.preventDefault();
            draw = false;
        }

        canvas.addEventListener("mousedown", start);
        canvas.addEventListener("mousemove", move);
        canvas.addEventListener("mouseup", end);
        canvas.addEventListener("mouseleave", end);

        canvas.addEventListener("touchstart", start, { passive: false });
        canvas.addEventListener("touchmove", move, { passive: false });
        canvas.addEventListener("touchend", end, { passive: false });
        canvas.addEventListener("touchcancel", end, { passive: false });

        function limpiar() { 
            ctx.fillStyle = "#ffffff";
            ctx.fillRect(0, 0, canvas.width, canvas.height); 
        }

        function guardarFirma() {
            document.getElementById("firmaDigital").value = canvas.toDataURL("image/png");
        }

        function beforeSubmit() {
            guardarFirma();
            return true;
        }
    </script>

</body>

</html>