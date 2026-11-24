<?php
/**
 * Funciones compartidas por create_parent.php, edit_parent.php y delete_parent.php
 */

const PARENTESCOS_VALIDOS = ['madre', 'padre', 'acudiente', 'abuelo(a)', 'tio(a)', 'hermano(a)', 'otro'];

/** Toma los campos del formulario y los normaliza (cadenas vacías => null) */
function leerDatosPadre(array $post): array
{
    $campos = ['usuario', 'nombre', 'apellido', 'documento_tipo', 'documento_numero',
               'telefono', 'email', 'direccion', 'ocupacion', 'estado'];
    $datos = [];
    foreach ($campos as $campo) {
        $valor = trim($post[$campo] ?? '');
        $datos[$campo] = $valor === '' ? null : $valor;
    }
    $datos['nombre'] = $datos['nombre'] ?? '';
    $datos['apellido'] = $datos['apellido'] ?? '';
    if (!in_array($datos['estado'], ['activo', 'inactivo'], true)) {
        $datos['estado'] = 'activo';
    }
    return $datos;
}

/** Devuelve [estudiante_id => parentesco] de los estudiantes marcados */
function leerVinculos(array $post): array
{
    $vinculos = [];
    foreach ((array) ($post['estudiantes'] ?? []) as $id) {
        $id = (int) $id;
        if ($id <= 0) {
            continue;
        }
        $parentesco = $post['parentesco'][$id] ?? 'acudiente';
        $vinculos[$id] = in_array($parentesco, PARENTESCOS_VALIDOS, true) ? $parentesco : 'acudiente';
    }
    return $vinculos;
}

/**
 * Valida los datos. Lanza Exception con un mensaje para el usuario si algo falla.
 * $padre_id es null al crear y el id del padre al editar.
 */
function validarPadre(PDO $pdo, array $padre, string $password, ?int $padre_id): void
{
    if ($padre['nombre'] === '' || $padre['apellido'] === '') {
        throw new Exception('Los nombres y apellidos son obligatorios.');
    }
    if ($padre['email'] !== null && !filter_var($padre['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('El correo electrónico no es válido.');
    }

    $password = trim($password);
    if ($password !== '' && strlen($password) < 6) {
        throw new Exception('La contraseña debe tener al menos 6 caracteres.');
    }

    if ($padre['usuario'] !== null) {
        if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $padre['usuario'])) {
            throw new Exception('El usuario debe tener entre 3 y 50 caracteres (letras, números, punto, guion).');
        }
        $stmt = $pdo->prepare("SELECT id FROM padres_familia WHERE usuario = ? AND id <> ?");
        $stmt->execute([$padre['usuario'], $padre_id ?? 0]);
        if ($stmt->fetch()) {
            throw new Exception('Ya existe otro acudiente con ese usuario.');
        }

        // Al crear un acceso nuevo se necesita contraseña
        $tiene_password = false;
        if ($padre_id) {
            $stmt = $pdo->prepare("SELECT password FROM padres_familia WHERE id = ?");
            $stmt->execute([$padre_id]);
            $tiene_password = !empty($stmt->fetchColumn());
        }
        if ($password === '' && !$tiene_password) {
            throw new Exception('Asigne una contraseña para que el acudiente pueda ingresar al portal.');
        }
    }

    if ($padre['documento_numero'] !== null) {
        $stmt = $pdo->prepare("SELECT id FROM padres_familia WHERE documento_numero = ? AND id <> ?");
        $stmt->execute([$padre['documento_numero'], $padre_id ?? 0]);
        if ($stmt->fetch()) {
            throw new Exception('Ya existe un acudiente registrado con ese número de documento.');
        }
    }
}

/** Reemplaza los estudiantes vinculados a un padre */
function guardarVinculos(PDO $pdo, int $padre_id, array $vinculos): void
{
    $pdo->prepare("DELETE FROM padres_estudiantes WHERE padre_id = ?")->execute([$padre_id]);
    $stmt = $pdo->prepare("INSERT INTO padres_estudiantes (padre_id, estudiante_id, parentesco) VALUES (?, ?, ?)");
    foreach ($vinculos as $estudiante_id => $parentesco) {
        $stmt->execute([$padre_id, $estudiante_id, $parentesco]);
    }
}

function obtenerEstudiantesActivos(PDO $pdo): array
{
    return $pdo->query("
        SELECT e.id, e.nombre, e.apellido, e.documento_numero,
               g.nombre AS grado_nombre, s.nombre AS sede_nombre
        FROM estudiantes e
        LEFT JOIN grados g ON e.grado_id = g.id
        LEFT JOIN sedes s ON e.sede_id = s.id
        WHERE e.estado = 'activo'
        ORDER BY e.apellido, e.nombre
    ")->fetchAll(PDO::FETCH_ASSOC);
}

function registrarActividadPadre(PDO $pdo, int $padre_id, string $accion, string $descripcion): void
{
    $stmt = $pdo->prepare("
        INSERT INTO actividad_log (tabla, registro_id, accion, descripcion, usuario_id, fecha)
        VALUES ('padres_familia', ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$padre_id, $accion, $descripcion, $_SESSION['admin_id'] ?? null]);
}
