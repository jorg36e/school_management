<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-graduation-cap"></i>
            <span>Sistema Escolar</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <!-- Gestión de Usuarios -->
            <li class="menu-section">
                <span class="menu-title">Gestión de Usuarios</span>
            </li>
            <li>
                <a href="users/list.php?type=teacher" class="<?php echo ($_GET['type'] ?? '') == 'teacher' ? 'active' : ''; ?>">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Profesores</span>
                </a>
            </li>
            <li>
                <a href="users/list.php?type=student" class="<?php echo ($_GET['type'] ?? '') == 'student' ? 'active' : ''; ?>">
                    <i class="fas fa-user-graduate"></i>
                    <span>Estudiantes</span>
                </a>
            </li>
            <li>
                <a href="users/list.php?type=parent" class="<?php echo ($_GET['type'] ?? '') == 'parent' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Padres de Familia</span>
                </a>
            </li>
            
        </ul>
    </nav>
</aside>