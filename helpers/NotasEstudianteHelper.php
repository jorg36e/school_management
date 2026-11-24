<?php
/**
 * Consulta de notas de un estudiante por periodo.
 *
 * Usa la misma lógica que el módulo del profesor:
 *  - Periodo "en_curso": notas de la tabla `calificaciones`.
 *  - Periodo finalizado/cerrado: notas de `historial_calificaciones`.
 *  - Definitiva: CalificacionesHelper::calcularDefinitiva (Tareas 40%, Evaluaciones 50%, Autoevaluación 10%).
 */
require_once __DIR__ . '/CalificacionesHelper.php';

class NotasEstudianteHelper
{
    /** Periodos académicos activos, del más reciente al más antiguo */
    public static function obtenerPeriodos(PDO $pdo): array
    {
        $stmt = $pdo->query("
            SELECT p.id, p.nombre, p.numero_periodo, p.estado_periodo, p.fecha_inicio, p.fecha_fin,
                   al.nombre AS ano_lectivo
            FROM periodos_academicos p
            LEFT JOIN anos_lectivos al ON p.ano_lectivo_id = al.id
            WHERE p.estado = 'activo'
            ORDER BY p.fecha_inicio DESC, p.id DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Periodo en curso (o el más reciente si ninguno está en curso) */
    public static function obtenerPeriodoActual(PDO $pdo): ?array
    {
        $periodos = self::obtenerPeriodos($pdo);
        foreach ($periodos as $p) {
            if ($p['estado_periodo'] === 'en_curso') {
                return $p;
            }
        }
        return $periodos[0] ?? null;
    }

    /** Escala de desempeño (Decreto 1290) usada también en el boletín */
    public static function desempeno(float $nota): array
    {
        if ($nota >= 4.6) return ['texto' => 'Superior', 'clase' => 'superior'];
        if ($nota >= 4.0) return ['texto' => 'Alto', 'clase' => 'alto'];
        if ($nota >= 3.0) return ['texto' => 'Básico', 'clase' => 'basico'];
        return ['texto' => 'Bajo', 'clase' => 'bajo'];
    }

    /**
     * Devuelve las materias del grado del estudiante con sus notas en el periodo indicado.
     *
     * @return array Lista de materias: [materia, profesor, tipos[], notas[tipo_id => valor], definitiva, categorias, desempeno]
     */
    public static function obtenerNotas(PDO $pdo, int $estudianteId, ?int $periodoId): array
    {
        $stmt = $pdo->prepare("SELECT grado_id FROM estudiantes WHERE id = ?");
        $stmt->execute([$estudianteId]);
        $gradoId = $stmt->fetchColumn();
        if (!$gradoId) {
            return [];
        }

        $estadoPeriodo = 'en_curso';
        if ($periodoId) {
            $stmt = $pdo->prepare("SELECT estado_periodo FROM periodos_academicos WHERE id = ?");
            $stmt->execute([$periodoId]);
            $estadoPeriodo = $stmt->fetchColumn() ?: 'en_curso';
        }

        $stmt = $pdo->prepare("
            SELECT ap.id AS asignacion_id, m.nombre AS materia,
                   CONCAT(p.nombre, ' ', p.apellido) AS profesor
            FROM asignaciones_profesor ap
            INNER JOIN materias m ON ap.materia_id = m.id
            LEFT JOIN profesores p ON ap.profesor_id = p.id
            WHERE ap.grado_id = ? AND ap.estado = 'activo'
            ORDER BY m.nombre
        ");
        $stmt->execute([$gradoId]);
        $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtTipos = $pdo->prepare("
            SELECT id, nombre, porcentaje, COALESCE(categoria, 'TAREAS') AS categoria
            FROM tipos_notas
            WHERE asignacion_id = ? AND estado = 'activo'
            ORDER BY categoria, id
        ");

        if ($estadoPeriodo === 'en_curso') {
            $stmtNotas = $pdo->prepare("
                SELECT c.tipo_nota_id, c.valor
                FROM calificaciones c
                INNER JOIN tipos_notas tn ON c.tipo_nota_id = tn.id
                WHERE c.estudiante_id = ? AND tn.asignacion_id = ?
                  AND c.estado = 'activo' AND tn.estado = 'activo'
                  AND (c.periodo_actual_id = ? OR c.periodo_actual_id IS NULL)
            ");
        } else {
            $stmtNotas = $pdo->prepare("
                SELECT h.tipo_nota_id, h.valor
                FROM historial_calificaciones h
                INNER JOIN tipos_notas tn ON h.tipo_nota_id = tn.id
                WHERE h.estudiante_id = ? AND tn.asignacion_id = ? AND h.periodo_id = ?
            ");
        }

        foreach ($materias as &$materia) {
            $stmtTipos->execute([$materia['asignacion_id']]);
            $tipos = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);

            $stmtNotas->execute([$estudianteId, $materia['asignacion_id'], $periodoId]);
            $notas = [];
            foreach ($stmtNotas->fetchAll(PDO::FETCH_ASSOC) as $n) {
                if ($n['valor'] !== null) {
                    $notas[$n['tipo_nota_id']] = (float) $n['valor'];
                }
            }

            // Agrupar por categoría igual que el módulo del profesor
            $agrupados = ['TAREAS' => [], 'EVALUACIONES' => [], 'AUTOEVALUACION' => []];
            foreach ($tipos as $t) {
                $agrupados[$t['categoria']][] = $t;
            }

            $calculo = CalificacionesHelper::calcularDefinitiva($notas, $agrupados);
            $definitiva = (float) str_replace(',', '.', $calculo['definitiva']);

            $materia['tipos'] = $tipos;
            $materia['notas'] = $notas;
            $materia['categorias'] = $calculo['categorias'];
            $materia['definitiva'] = $definitiva;
            $materia['tiene_notas'] = !empty($notas);
            $materia['desempeno'] = self::desempeno($definitiva);
        }
        unset($materia);

        return $materias;
    }

    /** Promedio de las definitivas de las materias que ya tienen notas */
    public static function promedio(array $materias): ?float
    {
        $conNotas = array_filter($materias, fn($m) => $m['tiene_notas']);
        if (!$conNotas) {
            return null;
        }
        return round(array_sum(array_column($conNotas, 'definitiva')) / count($conNotas), 1);
    }
}
