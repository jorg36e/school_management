<?php
/**
 * Mensajería interna entre profesores y padres de familia.
 *
 * Un participante se identifica por (tipo, id), donde tipo es 'profesor' o 'padre'.
 * Cada lado puede borrar el mensaje de su bandeja sin afectar al otro.
 */
class MensajesHelper
{
    const TIPOS = ['profesor', 'padre'];

    /** Nombre visible de un participante */
    public static function nombre(PDO $pdo, string $tipo, int $id): string
    {
        $tabla = $tipo === 'padre' ? 'padres_familia' : 'profesores';
        $stmt = $pdo->prepare("SELECT CONCAT(nombre, ' ', apellido) FROM $tabla WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetchColumn() ?: 'Usuario eliminado';
    }

    /**
     * Personas a las que puede escribir un participante.
     * Devuelve [clave "tipo:id" => ['tipo','id','nombre','detalle','grupo']].
     */
    public static function contactos(PDO $pdo, string $tipo, int $id): array
    {
        $contactos = [];

        if ($tipo === 'profesor') {
            // Acudientes de los estudiantes de sus grados
            $stmt = $pdo->prepare("
                SELECT p.id, CONCAT(p.nombre, ' ', p.apellido) AS nombre,
                       GROUP_CONCAT(DISTINCT CONCAT(e.nombre, ' ', e.apellido, ' - ', g.nombre) ORDER BY e.nombre SEPARATOR ', ') AS detalle
                FROM asignaciones_profesor ap
                INNER JOIN estudiantes e ON e.grado_id = ap.grado_id AND e.estado = 'activo'
                INNER JOIN grados g ON g.id = e.grado_id
                INNER JOIN padres_estudiantes pe ON pe.estudiante_id = e.id
                INNER JOIN padres_familia p ON p.id = pe.padre_id AND p.estado = 'activo'
                WHERE ap.profesor_id = ? AND ap.estado = 'activo'
                GROUP BY p.id
                ORDER BY nombre
            ");
            $stmt->execute([$id]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $contactos['padre:' . $r['id']] = ['tipo' => 'padre', 'id' => (int) $r['id'], 'nombre' => $r['nombre'],
                    'detalle' => 'Acudiente de ' . $r['detalle'], 'grupo' => 'Padres de familia'];
            }

            // Otros docentes
            $stmt = $pdo->prepare("
                SELECT p.id, CONCAT(p.nombre, ' ', p.apellido) AS nombre, s.nombre AS sede
                FROM profesores p LEFT JOIN sedes s ON s.id = p.sede_id
                WHERE p.estado = 'activo' AND p.id <> ?
                ORDER BY nombre
            ");
            $stmt->execute([$id]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $contactos['profesor:' . $r['id']] = ['tipo' => 'profesor', 'id' => (int) $r['id'], 'nombre' => $r['nombre'],
                    'detalle' => 'Docente' . ($r['sede'] ? ' · ' . $r['sede'] : ''), 'grupo' => 'Docentes'];
            }
        } elseif ($tipo === 'padre') {
            // Docentes que dictan clase a sus hijos
            $stmt = $pdo->prepare("
                SELECT pr.id, CONCAT(pr.nombre, ' ', pr.apellido) AS nombre,
                       GROUP_CONCAT(DISTINCT CONCAT(m.nombre, ' (', e.nombre, ')') ORDER BY m.nombre SEPARATOR ', ') AS detalle
                FROM padres_estudiantes pe
                INNER JOIN estudiantes e ON e.id = pe.estudiante_id
                INNER JOIN asignaciones_profesor ap ON ap.grado_id = e.grado_id AND ap.estado = 'activo'
                INNER JOIN profesores pr ON pr.id = ap.profesor_id AND pr.estado = 'activo'
                LEFT JOIN materias m ON m.id = ap.materia_id
                WHERE pe.padre_id = ?
                GROUP BY pr.id
                ORDER BY nombre
            ");
            $stmt->execute([$id]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $contactos['profesor:' . $r['id']] = ['tipo' => 'profesor', 'id' => (int) $r['id'], 'nombre' => $r['nombre'],
                    'detalle' => $r['detalle'], 'grupo' => 'Docentes de sus hijos'];
            }
        }

        return $contactos;
    }

    public static function noLeidos(PDO $pdo, string $tipo, int $id): int
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM mensajes
            WHERE destinatario_tipo = ? AND destinatario_id = ? AND leido = 0 AND eliminado_destinatario = 0
        ");
        $stmt->execute([$tipo, $id]);
        return (int) $stmt->fetchColumn();
    }

    /** Bandeja: 'recibidos' o 'enviados' */
    public static function bandeja(PDO $pdo, string $tipo, int $id, string $carpeta): array
    {
        if ($carpeta === 'enviados') {
            $sql = "SELECT * FROM mensajes WHERE remitente_tipo = ? AND remitente_id = ? AND eliminado_remitente = 0 ORDER BY fecha_envio DESC, id DESC LIMIT 200";
        } else {
            $sql = "SELECT * FROM mensajes WHERE destinatario_tipo = ? AND destinatario_id = ? AND eliminado_destinatario = 0 ORDER BY fecha_envio DESC, id DESC LIMIT 200";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tipo, $id]);
        $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $cache = [];
        foreach ($mensajes as &$m) {
            [$otroTipo, $otroId] = $carpeta === 'enviados'
                ? [$m['destinatario_tipo'], (int) $m['destinatario_id']]
                : [$m['remitente_tipo'], (int) $m['remitente_id']];
            $clave = $otroTipo . ':' . $otroId;
            $cache[$clave] = $cache[$clave] ?? self::nombre($pdo, $otroTipo, $otroId);
            $m['otro_nombre'] = $cache[$clave];
            $m['otro_tipo'] = $otroTipo;
        }
        unset($m);
        return $mensajes;
    }

    /** Un mensaje, solo si el participante es remitente o destinatario. Lo marca como leído si es el destinatario. */
    public static function abrir(PDO $pdo, string $tipo, int $id, int $mensajeId): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM mensajes WHERE id = ?");
        $stmt->execute([$mensajeId]);
        $m = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$m) {
            return null;
        }

        $esRemitente = $m['remitente_tipo'] === $tipo && (int) $m['remitente_id'] === $id && !$m['eliminado_remitente'];
        $esDestinatario = $m['destinatario_tipo'] === $tipo && (int) $m['destinatario_id'] === $id && !$m['eliminado_destinatario'];
        if (!$esRemitente && !$esDestinatario) {
            return null;
        }

        if ($esDestinatario && !$m['leido']) {
            $pdo->prepare("UPDATE mensajes SET leido = 1, fecha_lectura = NOW() WHERE id = ?")->execute([$mensajeId]);
            $m['leido'] = 1;
        }

        $m['es_remitente'] = $esRemitente;
        $m['remitente_nombre'] = self::nombre($pdo, $m['remitente_tipo'], (int) $m['remitente_id']);
        $m['destinatario_nombre'] = self::nombre($pdo, $m['destinatario_tipo'], (int) $m['destinatario_id']);
        if ($m['estudiante_id']) {
            $stmt = $pdo->prepare("SELECT CONCAT(nombre, ' ', apellido) FROM estudiantes WHERE id = ?");
            $stmt->execute([$m['estudiante_id']]);
            $m['estudiante_nombre'] = $stmt->fetchColumn() ?: null;
        }
        return $m;
    }

    /** Envía un mensaje. Lanza Exception con mensaje para el usuario si algo no es válido. */
    public static function enviar(PDO $pdo, string $tipo, int $id, string $destino, string $asunto, string $contenido, ?int $respuestaA = null): int
    {
        $contactos = self::contactos($pdo, $tipo, $id);

        // Siempre se puede responder a quien le escribió, aunque ya no esté en sus contactos
        if ($respuestaA && !isset($contactos[$destino])) {
            $original = self::abrir($pdo, $tipo, $id, $respuestaA);
            if ($original && !$original['es_remitente'] && $destino === $original['remitente_tipo'] . ':' . $original['remitente_id']) {
                $contactos[$destino] = ['tipo' => $original['remitente_tipo'], 'id' => (int) $original['remitente_id']];
            }
        }

        if (!isset($contactos[$destino])) {
            throw new Exception('Seleccione un destinatario válido.');
        }
        $asunto = trim($asunto);
        $contenido = trim($contenido);
        if ($asunto === '' || $contenido === '') {
            throw new Exception('Escriba el asunto y el mensaje.');
        }
        if (mb_strlen($asunto) > 200) {
            throw new Exception('El asunto no puede superar 200 caracteres.');
        }

        $dest = $contactos[$destino];
        $stmt = $pdo->prepare("
            INSERT INTO mensajes (remitente_tipo, remitente_id, destinatario_tipo, destinatario_id, asunto, contenido, respuesta_a)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$tipo, $id, $dest['tipo'], $dest['id'], $asunto, $contenido, $respuestaA]);
        return (int) $pdo->lastInsertId();
    }

    /**
     * Procesa los formularios de la bandeja (enviar / eliminar).
     * Redirige tras una acción exitosa; si hay error devuelve [mensaje_error, datos_del_formulario].
     */
    public static function procesarFormulario(PDO $pdo, string $tipo, int $id, string $url): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return [null, []];
        }
        $accion = $_POST['accion'] ?? '';
        try {
            if ($accion === 'enviar') {
                $respuestaA = (int) ($_POST['respuesta_a'] ?? 0) ?: null;
                self::enviar($pdo, $tipo, $id, $_POST['destino'] ?? '', $_POST['asunto'] ?? '', $_POST['contenido'] ?? '', $respuestaA);
                header('Location: ' . $url . '?vista=enviados&ok=enviado');
                exit();
            }
            if ($accion === 'eliminar') {
                self::eliminar($pdo, $tipo, $id, (int) ($_POST['id'] ?? 0));
                $volver = ($_POST['carpeta'] ?? '') === 'enviados' ? 'enviados' : 'recibidos';
                header('Location: ' . $url . '?vista=' . $volver . '&ok=eliminado');
                exit();
            }
        } catch (Exception $e) {
            return [$e->getMessage(), $_POST];
        }
        return [null, []];
    }

    /** Quita el mensaje de la bandeja del participante (el otro lo conserva) */
    public static function eliminar(PDO $pdo, string $tipo, int $id, int $mensajeId): void
    {
        $pdo->prepare("UPDATE mensajes SET eliminado_remitente = 1 WHERE id = ? AND remitente_tipo = ? AND remitente_id = ?")
            ->execute([$mensajeId, $tipo, $id]);
        $pdo->prepare("UPDATE mensajes SET eliminado_destinatario = 1 WHERE id = ? AND destinatario_tipo = ? AND destinatario_id = ?")
            ->execute([$mensajeId, $tipo, $id]);
    }
}
