<footer class="main-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <strong>Pico y Placa Colombia</strong>. Todos los derechos reservados.</p>
            <p class="disclaimer">
                La información es de carácter informativo. Recomendamos consultar fuentes oficiales de cada alcaldía.
            </p>
            <div class="version">Versión 3.7 - Actualizado</div>
        </div>
    </footer>

    <div id="pwaBtnContainer" class="pwa-floating-btn">
        <div class="pwa-content">
            <div class="pwa-icon">📱</div>
            <div class="pwa-text">
                <strong>Instalar App</strong>
                <span>Acceso rápido sin internet</span>
            </div>
            <button id="installPwaBtn">Instalar</button>
            <button id="closePwaBtn">✕</button>
        </div>
    </div>

    <script>
        // RECUPERAMOS EL JSON GENERADO POR PHP CON LA NUEVA LÓGICA DE VEHÍCULOS
        // Definimos variables globales si PHP las inyectó en index.php, si no, usamos valores por defecto
        let selectedCity = '<?php echo $ciudad_sel_url ?? "bogota"; ?>';
        // En index.php generamos $datos_hoy_json. Si no existe (p.ej. página 404), usamos objeto vacío.
        const DATA_PYP = <?php echo isset($datos_hoy_json) ? $datos_hoy_json : '{}'; ?>;
        const currentVehicle = '<?php echo $vehiculo_sel ?? "particular"; ?>';
        
        let countdownInterval;

        // --- FUNCIÓN PRINCIPAL: Actualizar UI ---
        function updateTodayInfo() {
            const data = DATA_PYP[selectedCity];
            if (!data) return;

            console.log('📍 Actualizando:', selectedCity, data);

            // 1. Info Básica
            document.getElementById('city-today').textContent = data.nombre;
            document.getElementById('city-schedule').textContent = data.horario;
            
            const today = new Date();
            const options = {weekday: 'long', day: 'numeric', month: 'long'};
            const dateStr = today.toLocaleDateString('es-CO', options);
            document.getElementById('today-date').textContent = dateStr.charAt(0).toUpperCase() + dateStr.slice(1);

            // Título dinámico si no es página de fecha específica
            if (!window.location.pathname.includes('/pico-y-placa/')) {
                let vLabel = data.vehiculo_label === 'Particulares' ? '' : data.vehiculo_label;
                const pageTitle = document.getElementById('pageTitle'); // Asegúrate de tener este ID en tu header o index si lo usas
                if(pageTitle) pageTitle.textContent = '🚗 Pico y Placa ' + vLabel + ' hoy en ' + data.nombre;
                
                // Actualizar título del navegador
                document.title = `Pico y placa ${vLabel} hoy en ${data.nombre} 🚗 | Consulta 2025`;
            }

            // 2. Estado Restricción (Texto Inteligente)
            const statusEl = document.getElementById('today-status');
            const restrictedContainer = document.getElementById('plates-restricted-today');
            const allowedContainer = document.getElementById('plates-allowed-today');
            const labelRestricted = document.getElementById('label-restricted'); // Asegúrate de tener este ID en index.php
            const labelAllowed = document.getElementById('label-allowed'); // Asegúrate de tener este ID en index.php
            const msgContainer = document.getElementById('dynamic-message-container'); // Asegúrate de tener este ID en index.php
            
            // Reset de contenedores
            if(restrictedContainer) restrictedContainer.innerHTML = '';
            if(allowedContainer) allowedContainer.innerHTML = '';
            if(labelRestricted) labelRestricted.style.display = 'block';
            if(msgContainer) msgContainer.style.display = 'none';
            document.body.classList.remove('sin-pico', 'pico-activo');

            // --- LÓGICA DE MENSAJES CLAROS ---
            
            if (data.es_excepcion) {
                // CASO 1: MEDIDA LEVANTADA (Excepción)
                statusEl.innerHTML = '<span style="color:#27ae60;">🔓 Medida Levantada</span>';
                if(msgContainer) {
                    msgContainer.style.display = 'block';
                    msgContainer.className = 'alert-box success'; // Clase CSS sugerida
                    msgContainer.innerHTML = '✨ ' + (data.nombre_festivo || 'Medida levantada temporalmente');
                }
                
                if(labelRestricted) labelRestricted.style.display = 'none';
                if(allowedContainer) allowedContainer.innerHTML = '<span class="plate-badge wide">Todas las placas están autorizadas</span>';
                document.body.classList.add('sin-pico');
                
            } else if (data.nombre_festivo) {
                // CASO 2: FESTIVO
                statusEl.innerHTML = '<span style="color:#27ae60;">🎉 ' + data.nombre_festivo + '</span>';
                if(msgContainer) {
                    msgContainer.style.display = 'block';
                    msgContainer.className = 'alert-box success';
                    msgContainer.innerHTML = '🎉 Hoy es ' + data.nombre_festivo + ', pueden circular todos los vehículos.';
                }
                
                if(labelRestricted) labelRestricted.style.display = 'none';
                if(allowedContainer) allowedContainer.innerHTML = '<span class="plate-badge wide">Todas las placas están autorizadas</span>';
                document.body.classList.add('sin-pico');

            } else if (data.restricciones && data.restricciones.length > 0) {
                // CASO 3: HAY RESTRICCIÓN
                statusEl.innerHTML = '<span style="color:#e74c3c;">🚫 Hay Pico y Placa</span>';
                // Llenar placas prohibidas
                if(restrictedContainer) {
                    data.restricciones.forEach(p => restrictedContainer.innerHTML += `<span class="plate-badge restricted">${p}</span>`);
                }
                // Llenar placas permitidas
                if(allowedContainer) {
                    data.permitidas.forEach(p => allowedContainer.innerHTML += `<span class="plate-badge">${p}</span>`);
                }
                document.body.classList.add('pico-activo'); // Para estilos CSS globales si los usas

            } else {
                // CASO 4: DÍA LIBRE (Fin de semana o día sin medida normal)
                statusEl.innerHTML = '<span style="color:#27ae60;">✅ Sin Restricción</span>';
                if(msgContainer) {
                    msgContainer.style.display = 'block';
                    msgContainer.className = 'alert-box success';
                    msgContainer.innerHTML = '✅ Hoy no aplica la medida en ' + data.nombre + '.';
                }
                
                if(labelRestricted) labelRestricted.style.display = 'none';
                if(allowedContainer) allowedContainer.innerHTML = '<span class="plate-badge wide">Todas las placas están autorizadas</span>';
                document.body.classList.add('sin-pico');
            }

            // 3. Actualizar Reloj Inteligente (Usando datos del backend)
            startCountdown(data.target_ts, data.estado_reloj);

            // 4. Renderizar Pronóstico 5 Días (Si existe contenedor)
            if (document.getElementById('forecast-container')) {
                renderForecast(data.pronostico);
            }
        }

        // --- FUNCIÓN RELOJ ---
        function startCountdown(targetTimestamp, estado) {
            clearInterval(countdownInterval);
            const titleEl = document.getElementById('countdownTitle');
            const msgEl = document.getElementById('countdownMessage');
            
            if (!titleEl) return;

            if (estado === 'sin_datos' || !targetTimestamp || targetTimestamp === 0) {
                titleEl.textContent = '✅ Libre';
                if(msgEl) msgEl.textContent = 'No hay restricciones próximas.';
                return;
            }
            
            let titulo = '', mensaje = '';
            if (estado === 'inicia') { 
                titulo = '⏳ Inicia en:'; 
                mensaje = 'La medida comienza hoy.'; 
            } else if (estado === 'termina') { 
                titulo = '🚨 Termina en:'; 
                mensaje = 'Restricción activa.'; 
            } else if (estado === 'proximo') { 
                titulo = '📅 Próxima:'; 
                const d = new Date(targetTimestamp * 1000);
                const dia = d.toLocaleDateString('es-CO', {weekday:'long'});
                // Formato hora amigable
                let hours = d.getHours();
                const ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12;
                hours = hours ? hours : 12; // la hora '0' es '12'
                const horaStr = hours + ':00 ' + ampm;
                
                mensaje = `Inicia el ${dia} a las ${horaStr}`;
            }
            
            titleEl.textContent = titulo;
            if(msgEl) msgEl.textContent = mensaje;

            function tick() {
                const now = Math.floor(Date.now() / 1000);
                const diff = targetTimestamp - now;
                if (diff <= 0) { location.reload(); return; }
                
                const h = Math.floor(diff / 3600).toString().padStart(2,'0');
                const m = Math.floor((diff % 3600) / 60).toString().padStart(2,'0');
                const s = (diff % 60).toString().padStart(2,'0');
                
                const elH = document.getElementById('countdownHours');
                const elM = document.getElementById('countdownMinutes');
                const elS = document.getElementById('countdownSeconds');
                
                if(elH) elH.textContent = h;
                if(elM) elM.textContent = m;
                if(elS) elS.textContent = s;
                
                // Mostrar contenedor si estaba oculto
                const container = document.getElementById('countdownContainer');
                if (container && !container.classList.contains('show')) container.classList.add('show');
            }
            tick();
            countdownInterval = setInterval(tick, 1000);
        }

        // --- FUNCIÓN PRONÓSTICO ---
        function renderForecast(dias) {
            const container = document.getElementById('forecast-container');
            if (!container || !dias) return;
            
            container.innerHTML = '';
            
            dias.forEach(dia => {
                const esLibre = dia.estado === 'libre' || dia.estado === 'festivo';
                const colorBg = esLibre ? '#f0fff4' : '#fff5f5';
                const colorBorde = esLibre ? '#c6f6d5' : '#fed7d7';
                const icono = dia.estado === 'festivo' ? '🎉' : (esLibre ? '✅' : '🚫');
                
                // Si es festivo/excepción mostramos el Nombre, si no las placas
                let contenidoCentral = dia.placas;
                let estiloFuente = "font-size: 0.85rem; font-weight: 700; color: #333;";
                
                if (dia.motivo_libre) {
                    contenidoCentral = dia.motivo_libre; // Ej: "Jueves Santo"
                    estiloFuente = "font-size: 0.75rem; font-weight: 600; color: #276749; line-height:1.1;";
                }

                const html = `
                    <div style="min-width: 90px; background: ${colorBg}; border: 1px solid ${colorBorde}; border-radius: 8px; padding: 8px; text-align: center; display: flex; flex-direction: column; justify-content: space-between;">
                        <div style="font-size: 0.8rem; font-weight: bold; color: #555;">${dia.dia}</div>
                        <div style="font-size: 0.75rem; color: #999;">${dia.fecha}</div>
                        <div style="font-size: 1.2rem; margin: 5px 0;">${icono}</div>
                        <div style="${estiloFuente}">${contenidoCentral}</div>
                    </div>
                `;
                container.innerHTML += html;
            });
        }

        // --- UTILIDADES ---
        function selectCity(cityCode) {
            selectedCity = cityCode;
            document.querySelectorAll('.city-btn').forEach(btn => btn.classList.remove('active'));
            const btn = document.getElementById('btn-'+cityCode);
            if(btn) btn.classList.add('active');
            
            updateTodayInfo();
            
            // Actualizar URL sin recargar
            const url = new URL(window.location);
            url.searchParams.set('city', cityCode);
            window.history.pushState({}, '', url);
            
            // Limpiar input de placa
            const resBox = document.getElementById('result-box');
            if(resBox) { resBox.innerHTML = ''; resBox.className = 'result-box'; }
            const pInput = document.getElementById('plate-input');
            if(pInput) pInput.value = '';
        }

        function searchPlate() {
            const val = document.getElementById('plate-input').value;
            if (!val || isNaN(val)) { alert('Ingresa un número (0-9)'); return; }
            
            const digit = parseInt(val);
            const data = DATA_PYP[selectedCity];
            const box = document.getElementById('result-box');
            
            if (!box) return;

            // Si hay excepción o festivo, no hay restricción
            if (data.nombre_festivo || data.es_excepcion || (data.restricciones && data.restricciones.length === 0)) {
                box.className = 'result-box result-success show';
                box.innerHTML = `<strong>✅ Habilitado:</strong> Hoy no aplica medida para ninguna placa.`;
                box.style.display = 'block';
                return;
            }

            const restricted = data.restricciones.includes(digit);
            box.style.display = 'block';
            if (restricted) {
                box.className = 'result-box result-restricted show';
                box.innerHTML = `<strong>⚠️ Restricción:</strong> Tu placa ${digit} NO puede circular hoy en ${data.nombre}.`;
            } else {
                box.className = 'result-box result-success show';
                box.innerHTML = `<strong>✅ Habilitado:</strong> Puedes circular hoy con placa ${digit}.`;
            }
        }
        
        function scrollCities(dir) {
            const el = document.getElementById('citiesSlider');
            if(el) el.scrollBy({ left: dir==='left'?-150:150, behavior: 'smooth' });
        }
        
        function searchByDate(e) {
            e.preventDefault();
            const d = document.getElementById('dateInput').value;
            const c = document.getElementById('citySelect').value;
            if(d && c) window.location.href = `/pico-y-placa/${d}-${c}?vehicle=${currentVehicle}`;
        }
        
        function backToHome() { window.location.href = '/'; }

        // --- INICIALIZACIÓN ---
        document.addEventListener('DOMContentLoaded', function() {
            // Iniciar UI
            updateTodayInfo();
            
            // Listeners de interfaz
            const plateInput = document.getElementById('plate-input');
            if (plateInput) {
                plateInput.addEventListener('input', function() { this.value = this.value.replace(/[^0-9]/g, ''); });
                plateInput.addEventListener('keypress', function(e) { if (e.key === 'Enter') searchPlate(); });
            }
        });

        // --- PWA LOGIC (Install App) ---
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/service-worker.js').then(function(registration) {
                    console.log('SW registrado');
                }, function(err) { console.log('SW error:', err); });
            });
        }
        
        let deferredPrompt;
        function getOS() {
            const ua = navigator.userAgent;
            if (ua.indexOf('iPhone') > -1 || ua.indexOf('iPad') > -1) return 'ios';
            if (ua.indexOf('Android') > -1) return 'android';
            return 'desktop';
        }
        
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            showPwaButton();
        });

        function showPwaButton() {
            const container = document.getElementById('pwaBtnContainer');
            if (container) { container.classList.add('show'); container.style.display = 'flex'; }
        }
        function hidePwaButton() {
            const container = document.getElementById('pwaBtnContainer');
            if (container) { 
                container.classList.remove('show'); 
                setTimeout(() => { container.style.display = 'none'; }, 300); 
            }
        }

        const installBtn = document.getElementById('installPwaBtn');
        if (installBtn) {
            installBtn.addEventListener('click', async () => {
                const os = getOS();
                if (os === 'ios') {
                    showIOSInstructions();
                } else if (deferredPrompt) {
                    deferredPrompt.prompt();
                    const { outcome } = await deferredPrompt.userChoice;
                    deferredPrompt = null;
                    hidePwaButton();
                }
            });
        }
        
        const closePwaBtn = document.getElementById('closePwaBtn');
        if (closePwaBtn) closePwaBtn.addEventListener('click', hidePwaButton);

        function showIOSInstructions() {
            let modal = document.getElementById('iosModalPwa');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'iosModalPwa';
                modal.innerHTML = `<div class="ios-modal-content"><h2>📱 Instalar en iOS</h2><p>Sigue estos pasos:</p><div class="ios-steps"><ol><li>Toca <strong>Compartir</strong> (↗️)</li><li>Toca <strong>"Añadir a pantalla de inicio"</strong></li><li>¡Listo!</li></ol></div><button class="ios-modal-close" onclick="this.parentElement.parentElement.classList.remove('show')">Entendido</button></div>`;
                document.body.appendChild(modal);
            }
            modal.classList.add('show');
        }

        window.addEventListener('appinstalled', () => { hidePwaButton(); });
    </script>

    <style>
        .main-footer {
            margin-top: 40px;
            padding: 30px 20px;
            background-color: #f8fafc;
            border-top: 1px solid #edf2f7;
            text-align: center;
            color: #718096;
            font-size: 0.9rem;
        }
        .disclaimer { margin-top: 10px; font-size: 0.8rem; opacity: 0.8; max-width: 800px; margin-left: auto; margin-right: auto; }
        .version { margin-top: 15px; font-size: 0.75rem; color: #cbd5e0; text-transform: uppercase; letter-spacing: 1px; }
        
        /* Estilos básicos para PWA Button (si no están en css principal) */
        .pwa-floating-btn { display: none; position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); background: white; padding: 10px 15px; border-radius: 50px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); z-index: 1000; align-items: center; gap: 10px; animation: slideUp 0.3s ease; }
        .pwa-content { display: flex; align-items: center; gap: 10px; }
        .pwa-icon { font-size: 1.5rem; }
        .pwa-text { display: flex; flex-direction: column; font-size: 0.8rem; line-height: 1.1; text-align: left; }
        #installPwaBtn { background: #667eea; color: white; border: none; padding: 8px 15px; border-radius: 20px; font-weight: bold; cursor: pointer; }
        #closePwaBtn { background: transparent; border: none; color: #999; font-size: 1.2rem; cursor: pointer; }
        @keyframes slideUp { from { transform: translate(-50%, 100px); opacity: 0; } to { transform: translate(-50%, 0); opacity: 1; } }
        /* iOS Modal simple styles */
        #iosModalPwa { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; justify-content: center; align-items: center; }
        #iosModalPwa.show { display: flex; }
        .ios-modal-content { background: white; padding: 30px; border-radius: 20px; max-width: 90%; text-align: center; }
        .ios-steps { text-align: left; margin: 20px 0; }
        .ios-modal-close { background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-size: 1rem; cursor: pointer; }
        
        /* ALERT BOX (Para mensajes dinámicos) */
        .alert-box { padding: 10px; border-radius: 8px; margin-bottom: 15px; font-weight: 600; font-size: 0.95rem; }
        .alert-box.success { background: #f0fff4; color: #276749; border: 1px solid #c6f6d5; }
    </style>

</body>
</html>
