            </div>
        </div>
    </div>

    <script>
        // Fecha y hora de la barra superior
        (function () {
            function actualizarFechaHora() {
                const ahora = new Date();
                const fecha = document.getElementById('current-date');
                const hora = document.getElementById('current-time');
                if (fecha) fecha.textContent = ahora.toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                if (hora) hora.textContent = ahora.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', hour12: true });
            }
            actualizarFechaHora();
            setInterval(actualizarFechaHora, 1000);
        })();
    </script>
</body>
</html>
