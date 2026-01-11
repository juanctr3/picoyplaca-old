<footer>
            <p><strong>Pico y PL</strong> - Colombia 2025 | Versión 2.5</p>
        </footer>
    </div> <script>
        // RECUPERAMOS EL JSON GENERADO POR PHP CON LA NUEVA LÓGICA DE VEHÍCULOS
        let selectedCity = '<?php echo $ciudad_sel_url; ?>';
        const datosHoy = JSON.parse('<?php echo $datos_hoy_json; ?>');
        const festivosColombia = <?php echo json_encode($festivos); ?>;
        const currentVehicle = '<?php echo $vehiculo_sel; ?>'; 
        
        let countdownInterval;
        
        function updateTodayInfo() {
            const data = datosHoy[selectedCity];
            if (!data) { console.error('❌ Ciudad no encontrada:', selectedCity); return; }
            console.log('\n📍 Actualizando:', selectedCity);
            
            const today = new Date();
            const options = {weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'};
            const dateStr = today.toLocaleDateString('es-CO', options);
            
            document.getElementById('today-date').textContent = dateStr.charAt(0).toUpperCase() + dateStr.slice(1);
            document.getElementById('city-today').textContent = data.nombre;
            document.getElementById('city-schedule').textContent = data.horario;
            
            const isDatePage = window.location.pathname.includes('/pico-y-placa/');
            if (!isDatePage) {
                let vLabel = data.vehiculo_label === 'Particulares' ? '' : data.vehiculo_label;
                document.getElementById('pageTitle').textContent = '🚗 Pico y Placa ' + vLabel + ' hoy en ' + data.nombre;
            }
            
            const cityNameSubtitle = document.getElementById('cityNameSubtitle');
            if (cityNameSubtitle) cityNameSubtitle.textContent = data.nombre;
            
            const restricciones = data.restricciones;
            const permitidas = data.permitidas;
            const horarioInicio = parseInt(data.horarioInicio, 10);
            const horarioFin = parseInt(data.horarioFin, 10);
            
            function esFestivo(fecha) { const fechaStr = fecha.toISOString().split('T')[0]; return festivosColombia.includes(fechaStr); }
            const hoyFestivo = esFestivo(today);
            const diaSemana = today.getDay();
            const esFinDeSemana = diaSemana === 0 || diaSemana === 6;
            
            if (selectedCity === 'barranquilla' && currentVehicle === 'particular') {
                document.getElementById('today-status').textContent = '✅ SIN RESTRICCIONES';
                document.getElementById('restriction-label').innerHTML = '🎉 Sin pico y placa:';
                document.getElementById('plates-restricted-today').innerHTML = '<p class="no-restriction" style="font-size: 1.1rem; font-weight: 800; background: #c8e6c9; padding: 15px; border-radius: 8px;">Esta ciudad NO tiene restricciones de circulación para vehículos particulares</p>';
                document.getElementById('plates-allowed-today').innerHTML = '<p class="no-restriction">✅ Todos los vehículos (0-9) pueden circular</p>';
                document.body.className = 'sin-pico';
                if (document.getElementById('countdownContainer')) document.getElementById('countdownContainer').style.display = 'none';
                return;
            }
            
            updateCountdown(horarioInicio, horarioFin);
            
            if (hoyFestivo) {
                document.getElementById('today-status').textContent = '🎉 Festivo';
                document.getElementById('restriction-label').innerHTML = '✅ Sin restricción';
                document.getElementById('plates-restricted-today').innerHTML = '<p class="no-restriction">🎉 Día Festivo - Sin restricción</p>';
                document.getElementById('plates-allowed-today').innerHTML = '<p class="no-restriction">✅ Todos los vehículos (0-9)</p>';
                document.body.className = 'sin-pico';
            } else if (esFinDeSemana && data.vehiculo_label === 'Particulares') {
                document.getElementById('today-status').textContent = 'Libre - Fin de Semana';
                document.getElementById('restriction-label').innerHTML = '✅ Sin restricción';
                document.getElementById('plates-restricted-today').innerHTML = '<p class="no-restriction">✅ Fin de Semana - Sin restricción</p>';
                document.getElementById('plates-allowed-today').innerHTML = '<p class="no-restriction">✅ Todos los vehículos (0-9)</p>';
                document.body.className = 'sin-pico';
            } else {
                if (restricciones && restricciones.length > 0) {
                    document.getElementById('today-status').textContent = restricciones.join(', ');
                    document.getElementById('restriction-label').innerHTML = '🚫 Con restricción:';
                    document.getElementById('plates-restricted-today').innerHTML = restricciones.map(p => '<span class="plate-badge restricted">' + p + '</span>').join('');
                    document.getElementById('plates-allowed-today').innerHTML = permitidas.map(p => '<span class="plate-badge">' + p + '</span>').join('');
                } else {
                    document.getElementById('today-status').textContent = 'Libre';
                    document.getElementById('restriction-label').innerHTML = '✅ Hoy no hay restricción';
                    document.getElementById('plates-restricted-today').innerHTML = '<p class="no-restriction">✅ Hoy no hay restricción</p>';
                    document.getElementById('plates-allowed-today').innerHTML = permitidas.map(p => '<span class="plate-badge">' + p + '</span>').join('');
                    document.body.className = 'sin-pico';
                }
            }
        }
        
        function updateCountdown(inicio, fin) {
            clearInterval(countdownInterval);
            inicio = parseInt(inicio, 10);
            fin = parseInt(fin, 10);
            
            function esFestivo(fecha) { const fechaStr = fecha.toISOString().split('T')[0]; return festivosColombia.includes(fechaStr); }
            function esFinDeSemana(fecha) { const dia = fecha.getDay(); return dia === 0 || dia === 6; }
            function siguienteDiaHabil(fechaInicio) {
                let fecha = new Date(fechaInicio);
                fecha.setDate(fecha.getDate() + 1);
                fecha.setHours(inicio, 0, 0, 0);
                let intentos = 0;
                while ((esFinDeSemana(fecha) || esFestivo(fecha)) && intentos < 14) { fecha.setDate(fecha.getDate() + 1); intentos++; }
                return fecha;
            }
            
            function calcularTiempo() {
                const ahora = new Date();
                const horaActual = ahora.getHours();
                
                const tieneRestriccionHoy = datosHoy[selectedCity].restricciones.length > 0;
                let proximoTiempo = 0, titulo = '', mensaje = '';
                
                if (!tieneRestriccionHoy) {
                    const proximoDiaHabil = siguienteDiaHabil(ahora);
                    titulo = '🎉 SIN PICO Y PLACA HOY';
                    mensaje = '📅 Próxima restricción el ' + proximoDiaHabil.toLocaleDateString('es-CO', { weekday: 'long', day: 'numeric', month: 'long' }) + ':';
                    proximoTiempo = (proximoDiaHabil.getTime() - ahora.getTime()) / 1000;
                    document.body.className = 'sin-pico';
                } else if (horaActual >= inicio && horaActual < fin) {
                    titulo = '🚨 PICO Y PLACA ACTIVO';
                    mensaje = '⏱️ Falta para terminar:';
                    const finHoy = new Date(ahora);
                    finHoy.setHours(fin, 0, 0, 0);
                    proximoTiempo = Math.max(0, (finHoy.getTime() - ahora.getTime()) / 1000);
                    document.body.className = 'pico-activo';
                } else if (horaActual < inicio) {
                    titulo = '✅ PICO Y PLACA HOY';
                    mensaje = '⏳ Falta para iniciar:';
                    const inicioHoy = new Date(ahora);
                    inicioHoy.setHours(inicio, 0, 0, 0);
                    proximoTiempo = (inicioHoy.getTime() - ahora.getTime()) / 1000;
                    document.body.className = 'sin-pico';
                } else {
                    const proximoDiaHabil = siguienteDiaHabil(ahora);
                    titulo = '✅ PRÓXIMO PICO Y PLACA';
                    mensaje = '📅 Inicia el ' + proximoDiaHabil.toLocaleDateString('es-CO', { weekday: 'long', day: 'numeric', month: 'long' }) + ':';
                    proximoTiempo = (proximoDiaHabil.getTime() - ahora.getTime()) / 1000;
                    document.body.className = 'sin-pico';
                }
                
                const horas = Math.floor(proximoTiempo / 3600);
                const minutos = Math.floor((proximoTiempo % 3600) / 60);
                const segundos = Math.floor(proximoTiempo % 60);
                
                const titleEl = document.getElementById('countdownTitle');
                if (titleEl) titleEl.innerHTML = titulo + '<br><small style="font-size: 0.8rem; font-weight: 500;">' + mensaje + '</small>';
                
                document.getElementById('countdownHours').textContent = String(horas).padStart(2, '0');
                document.getElementById('countdownMinutes').textContent = String(minutos).padStart(2, '0');
                document.getElementById('countdownSeconds').textContent = String(segundos).padStart(2, '0');
                
                const container = document.getElementById('countdownContainer');
                if (container && !container.classList.contains('show')) container.classList.add('show');
            }
            calcularTiempo();
            countdownInterval = setInterval(calcularTiempo, 1000);
        }
        
        function selectCity(ciudad) {
            console.log('\n🏙️ Cambiando a ciudad:', ciudad);
            selectedCity = ciudad;
            document.querySelectorAll('.city-btn').forEach(b => b.classList.remove('active'));
            const btnCity = document.getElementById('btn-' + ciudad);
            if (btnCity) btnCity.classList.add('active');
            
            const data = datosHoy[ciudad];
            if (!data) return;
            
            const isDatePage = window.location.pathname.includes('/pico-y-placa/');
            if (!isDatePage) {
                let vLabel = data.vehiculo_label === 'Particulares' ? '' : data.vehiculo_label;
                const newTitle = `Pico y placa ${vLabel} hoy en ${data.nombre} 🚗 | Consulta en Tiempo Real`;
                document.title = newTitle;
                const url = new URL(window.location);
                url.searchParams.set('city', ciudad);
                window.history.pushState({}, '', url);
            }
            updateTodayInfo();
            document.getElementById('result-box').innerHTML = '';
            document.getElementById('plate-input').value = '';
        }
        
        function searchPlate() {
            const plate = document.getElementById('plate-input').value;
            if (!plate || isNaN(plate)) return alert('Solo 0-9');
            const data = datosHoy[selectedCity];
            const tiene_restriccion = data.restricciones.includes(parseInt(plate));
            const box = document.getElementById('result-box');
            if (tiene_restriccion) {
                box.className = 'result-box show result-restricted';
                box.innerHTML = '<h3>⚠️ ¡RESTRICCIÓN!</h3><p>Tu placa ' + plate + ' NO puede circular hoy en ' + data.nombre + '</p>';
            } else {
                box.className = 'result-box show result-success';
                box.innerHTML = '<h3>✅ Puedes circular</h3><p>Tu placa ' + plate + ' puede circular hoy en ' + data.nombre + '</p>';
            }
        }
        
        function searchByDate(e) {
            e.preventDefault();
            const date = document.getElementById('dateInput').value;
            const city = document.getElementById('citySelect').value;
            if (date) {
                const [year, month, day] = date.split('-');
                window.location.href = `/pico-y-placa/${year}-${month}-${day}-${city}?vehicle=${currentVehicle}`;
            }
        }
        function backToHome() { window.location.href = '/'; }
        
        function initSliders() {
            const citiesSlider = document.getElementById('citiesSlider');
            const citiesPrevBtn = document.getElementById('citiesPrev');
            const citiesNextBtn = document.getElementById('citiesNext');
            if (citiesSlider && citiesPrevBtn && citiesNextBtn) {
                citiesPrevBtn.onclick = () => { citiesSlider.scrollBy({ left: -150, behavior: 'smooth' }); };
                citiesNextBtn.onclick = () => { citiesSlider.scrollBy({ left: 150, behavior: 'smooth' }); };
                updateScrollButtons();
            }
        }
        function scrollCities(direction) {
            const slider = document.getElementById('citiesSlider');
            if (!slider) return;
            if (direction === 'left') slider.scrollBy({ left: -150, behavior: 'smooth' });
            else slider.scrollBy({ left: 150, behavior: 'smooth' });
        }
        function updateScrollButtons() {
            const citiesSlider = document.getElementById('citiesSlider');
            if (!citiesSlider) return;
            // Simplificado para evitar errores si botones no existen
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            selectCity(selectedCity);
            initSliders();
            const plateInput = document.getElementById('plate-input');
            if (plateInput) {
                plateInput.addEventListener('input', function() { this.value = this.value.replace(/[^0-9]/g, ''); });
                plateInput.addEventListener('keypress', function(e) { if (e.key === 'Enter') searchPlate(); });
            }
        });
        
        if ('serviceWorker' in navigator) { navigator.serviceWorker.register('/service-worker.js').catch(e => console.log('SW:', e)); }
        
        // PWA Logic
        let deferredPrompt;
        function getOS() {
            const ua = navigator.userAgent;
            if (ua.indexOf('iPhone') > -1 || ua.indexOf('iPad') > -1) return 'ios';
            if (ua.indexOf('Android') > -1) return 'android';
            return 'desktop';
        }
        window.addEventListener('beforeinstallprompt', (e) => { e.preventDefault(); deferredPrompt = e; showPwaButton(); });
        function showPwaButton() { const container = document.getElementById('pwaBtnContainer'); if (container) { container.classList.add('show'); container.style.display = 'flex'; } }
        function hidePwaButton() { const container = document.getElementById('pwaBtnContainer'); if (container) { container.classList.remove('show'); setTimeout(() => { container.style.display = 'none'; }, 300); } }
        document.getElementById('installPwaBtn').addEventListener('click', async () => {
            const os = getOS();
            if (os === 'ios') { showIOSInstructions(); } 
            else if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                deferredPrompt = null;
                hidePwaButton();
            }
        });
        document.getElementById('closePwaBtn').addEventListener('click', hidePwaButton);
        function showIOSInstructions() {
            let modal = document.getElementById('iosModalPwa');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'iosModalPwa';
                modal.innerHTML = `<div class="ios-modal-content"><h2>📱 Instalar en iOS</h2><p>Sigue estos pasos:</p><div class="ios-steps"><ol><li>Toca <strong>Compartir</strong> (↗️)</li><li>Toca <strong>"Añadir a pantalla de inicio"</strong></li><li>¡Listo! La app aparecerá en tu pantalla de inicio</li></ol></div><button class="ios-modal-close" onclick="this.parentElement.parentElement.classList.remove('show')">Entendido</button></div>`;
                document.body.appendChild(modal);
            }
            modal.classList.add('show');
        }
        window.addEventListener('appinstalled', () => { hidePwaButton(); });
    </script>
</body>
</html>
