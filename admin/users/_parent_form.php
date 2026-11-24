<?php
// Este archivo solo se incluye desde create_parent.php / edit_parent.php
if (!isset($estudiantes, $es_edicion)) {
    http_response_code(404);
    exit();
}

/**
 * Formulario compartido para crear / editar padres de familia.
 * Variables esperadas:
 *   $padre       array con los datos (vacío al crear)
 *   $estudiantes lista de estudiantes activos (id, nombre, apellido, documento_numero, grado_nombre, sede_nombre)
 *   $vinculos    [estudiante_id => parentesco] estudiantes ya vinculados
 *   $es_edicion  bool
 */
$v = fn($campo) => htmlspecialchars($padre[$campo] ?? '');
$parentescos = [
    'madre' => 'Madre',
    'padre' => 'Padre',
    'acudiente' => 'Acudiente',
    'abuelo(a)' => 'Abuelo(a)',
    'tio(a)' => 'Tío(a)',
    'hermano(a)' => 'Hermano(a)',
    'otro' => 'Otro',
];
$tipos_documento = ['CC' => 'Cédula de ciudadanía', 'CE' => 'Cédula de extranjería', 'PA' => 'Pasaporte', 'PPT' => 'Permiso por protección temporal'];
?>
<form method="POST" action="" id="parent-form" autocomplete="off">
    <div class="form-sections">
        <!-- Datos personales -->
        <div class="form-section">
            <h3><i class="fas fa-user"></i> Datos personales</h3>

            <div class="form-row">
                <div class="form-group">
                    <label for="nombre" class="required-label">Nombres</label>
                    <input type="text" id="nombre" name="nombre" value="<?php echo $v('nombre'); ?>" required>
                </div>
                <div class="form-group">
                    <label for="apellido" class="required-label">Apellidos</label>
                    <input type="text" id="apellido" name="apellido" value="<?php echo $v('apellido'); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="documento_tipo">Tipo de documento</label>
                    <select id="documento_tipo" name="documento_tipo">
                        <option value="">Seleccione…</option>
                        <?php foreach ($tipos_documento as $valor => $texto): ?>
                            <option value="<?php echo $valor; ?>" <?php echo ($padre['documento_tipo'] ?? '') === $valor ? 'selected' : ''; ?>>
                                <?php echo $texto; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="documento_numero">Número de documento</label>
                    <input type="text" id="documento_numero" name="documento_numero" value="<?php echo $v('documento_numero'); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="telefono">Celular</label>
                    <input type="tel" id="telefono" name="telefono" value="<?php echo $v('telefono'); ?>" pattern="[0-9+ ]{7,15}">
                </div>
                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" value="<?php echo $v('email'); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="direccion">Dirección</label>
                    <input type="text" id="direccion" name="direccion" value="<?php echo $v('direccion'); ?>">
                </div>
                <div class="form-group">
                    <label for="ocupacion">Ocupación</label>
                    <input type="text" id="ocupacion" name="ocupacion" value="<?php echo $v('ocupacion'); ?>">
                </div>
            </div>
        </div>

        <!-- Cuenta de acceso -->
        <div class="form-section">
            <h3><i class="fas fa-user-shield"></i> Acceso al portal de padres</h3>

            <div class="form-group">
                <label for="usuario">Usuario</label>
                <input type="text" id="usuario" name="usuario" value="<?php echo $v('usuario'); ?>">
                <small class="form-text">Con este usuario el acudiente consulta las notas de sus hijos. Déjelo vacío si no necesita acceso.</small>
            </div>

            <div class="form-group">
                <label for="password"><?php echo $es_edicion ? 'Nueva contraseña' : 'Contraseña'; ?></label>
                <input type="password" id="password" name="password" minlength="6" autocomplete="new-password">
                <small class="form-text">
                    <?php echo $es_edicion
                        ? 'Déjela vacía para conservar la contraseña actual.'
                        : 'Mínimo 6 caracteres. Obligatoria si asigna un usuario.'; ?>
                </small>
            </div>

            <?php if ($es_edicion): ?>
                <div class="form-group">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado">
                        <option value="activo" <?php echo ($padre['estado'] ?? '') === 'activo' ? 'selected' : ''; ?>>Activo</option>
                        <option value="inactivo" <?php echo ($padre['estado'] ?? '') === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                    <small class="form-text">Un acudiente inactivo no puede iniciar sesión.</small>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Estudiantes a cargo -->
    <div class="form-section" style="margin-top: 1.5rem;">
        <h3><i class="fas fa-children"></i> Estudiantes a cargo</h3>

        <?php if (empty($estudiantes)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Aún no hay estudiantes activos. Puede guardar el acudiente y vincularlo después.
            </div>
        <?php else: ?>
            <div class="form-group">
                <input type="search" id="buscar-estudiante" class="form-control" placeholder="Buscar estudiante por nombre, documento o grado…">
            </div>
            <div class="table-container" style="max-height: 360px; overflow-y: auto;">
                <table class="data-table" id="tabla-estudiantes">
                    <thead>
                        <tr>
                            <th style="width: 48px;"></th>
                            <th>Estudiante</th>
                            <th>Documento</th>
                            <th>Grado</th>
                            <th>Sede</th>
                            <th style="width: 180px;">Parentesco</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($estudiantes as $e):
                            $vinculado = isset($vinculos[$e['id']]);
                            $parentesco_actual = $vinculos[$e['id']] ?? 'acudiente';
                        ?>
                            <tr data-buscar="<?php echo htmlspecialchars(mb_strtolower($e['nombre'] . ' ' . $e['apellido'] . ' ' . $e['documento_numero'] . ' ' . $e['grado_nombre'])); ?>">
                                <td>
                                    <input type="checkbox" name="estudiantes[]" value="<?php echo $e['id']; ?>"
                                           id="est-<?php echo $e['id']; ?>" <?php echo $vinculado ? 'checked' : ''; ?>>
                                </td>
                                <td>
                                    <label for="est-<?php echo $e['id']; ?>" style="cursor:pointer; font-weight:500; color: var(--text-primary);">
                                        <?php echo htmlspecialchars($e['apellido'] . ' ' . $e['nombre']); ?>
                                    </label>
                                </td>
                                <td><?php echo htmlspecialchars($e['documento_numero'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($e['grado_nombre'] ?? 'Sin grado'); ?></td>
                                <td><?php echo htmlspecialchars($e['sede_nombre'] ?? ''); ?></td>
                                <td>
                                    <select name="parentesco[<?php echo $e['id']; ?>]" class="form-control" style="padding: 0.4rem 0.6rem;">
                                        <?php foreach ($parentescos as $valor => $texto): ?>
                                            <option value="<?php echo $valor; ?>" <?php echo $parentesco_actual === $valor ? 'selected' : ''; ?>>
                                                <?php echo $texto; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <small class="form-text" id="contador-seleccion"></small>
        <?php endif; ?>
    </div>

    <div class="form-actions">
        <a href="list_parents.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Cancelar
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> <?php echo $es_edicion ? 'Guardar cambios' : 'Guardar acudiente'; ?>
        </button>
    </div>
</form>

<script>
(function () {
    const buscador = document.getElementById('buscar-estudiante');
    const filas = document.querySelectorAll('#tabla-estudiantes tbody tr');
    const contador = document.getElementById('contador-seleccion');

    function actualizarContador() {
        if (!contador) return;
        const n = document.querySelectorAll('#tabla-estudiantes input[type="checkbox"]:checked').length;
        contador.textContent = n === 0 ? 'Ningún estudiante seleccionado.'
            : (n === 1 ? '1 estudiante seleccionado.' : n + ' estudiantes seleccionados.');
    }

    if (buscador) {
        buscador.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            filas.forEach(f => { f.style.display = f.dataset.buscar.includes(q) ? '' : 'none'; });
        });
    }
    document.querySelectorAll('#tabla-estudiantes input[type="checkbox"]').forEach(c => c.addEventListener('change', actualizarContador));
    actualizarContador();

    document.getElementById('parent-form').addEventListener('submit', function (e) {
        const usuario = document.getElementById('usuario').value.trim();
        const password = document.getElementById('password').value;
        const esEdicion = <?php echo $es_edicion ? 'true' : 'false'; ?>;
        if (usuario && !esEdicion && password.length < 6) {
            e.preventDefault();
            alert('Para crear el acceso al portal, la contraseña debe tener al menos 6 caracteres.');
        }
        if (password && password.length < 6) {
            e.preventDefault();
            alert('La contraseña debe tener al menos 6 caracteres.');
        }
    });
})();
</script>
