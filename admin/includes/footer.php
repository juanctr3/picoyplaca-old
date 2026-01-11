<footer class="admin-footer">
            <div class="footer-content">
                <p>&copy; <?php echo date('Y'); ?> <strong>PicoYPlaca Admin</strong>. Todos los derechos reservados.</p>
                <p class="version">Versión 2.6</p>
            </div>
        </footer>

    </main> <script>
        // 1. Auto-ocultar alertas después de 5 segundos
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            if (alerts.length > 0) {
                setTimeout(function() {
                    alerts.forEach(function(alert) {
                        alert.style.transition = 'opacity 0.5s ease';
                        alert.style.opacity = '0';
                        setTimeout(() => alert.remove(), 500);
                    });
                }, 5000); // 5 segundos
            }
        });

        // 2. Confirmación de eliminación global (por si algún botón no tiene onclick)
        const deleteBtns = document.querySelectorAll('.btn-delete');
        deleteBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!this.getAttribute('onclick')) {
                    if (!confirm('¿Estás seguro de realizar esta acción? No se puede deshacer.')) {
                        e.preventDefault();
                    }
                }
            });
        });
    </script>

    <style>
        /* Estilos del Footer (Incrustados aquí para no editar el header) */
        .admin-footer {
            margin-top: auto;
            padding-top: 40px;
            border-top: 1px solid #edf2f7;
            color: #a0aec0;
            font-size: 0.85rem;
        }
        .footer-content {
            max-width: 1100px; 
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        @media (max-width: 768px) {
            .footer-content { flex-direction: column; gap: 5px; text-align: center; }
        }
    </style>

</body>
</html>
