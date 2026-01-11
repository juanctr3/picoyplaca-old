<footer class="main-footer">
    <div class="container">
        <p>&copy; <?php echo date('Y'); ?> <strong>Pico y Placa Colombia</strong>. Todos los derechos reservados.</p>
        <p class="disclaimer">
            La información es de carácter informativo. Recomendamos consultar fuentes oficiales de cada alcaldía.
        </p>
        <div class="version">Versión 4.0 - Grid & Multi-Vehículo</div>
    </div>
</footer>

<div id="pwaBtnContainer" style="display: none;">
    <div class="pwa-icon">📱</div>
    <div class="pwa-text">
        <strong>Instalar App</strong>
        <span>Acceso rápido sin internet</span>
    </div>
    <button id="installPwaBtn">Instalar</button>
    <button id="closePwaBtn">✕</button>
</div>

<script>
    // --- 1. VARIABLES GLOBALES (Inyectadas por PHP) ---
    // selectedCity inicia con la que traiga la URL o el defecto PHP
    let selectedCity = '<?php echo $ciudad_sel_url ?? "bogota"; ?>';
    
    // Objeto masivo con toda la data pre-cargada para el vehículo actual
    const DATA_PYP = <?php echo isset($datos_hoy_json) ? $datos_hoy_json : '{}'; ?>;
    
    // Vehículo seleccionado en PHP (para referencias iniciales)
    const currentVehiclePHP = '<?php echo $vehiculo_sel ?? "particular"; ?>';
    
    let countdownInterval;

    // --- 2. LÓGICA PWA (Instalación App) ---
    document.addEventListener('DOMContentLoaded', () => {
        let deferredPrompt; 
        const pwaContainer = document.getElementById('pwaBtnContainer');
        const installBtn = document.getElementById('installPwaBtn');
        const closeBtn = document.getElementById('closePwaBtn');

        const hidePWA = () => {
            if (pwaContainer) {
                pwaContainer.classList.remove('show');
                setTimeout(() => { pwaContainer.style.display = 'none'; }, 300);
            }
        };

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault(); 
            deferredPrompt = e; 
            const isDismissed = localStorage.getItem('pwa_dismissed') === 'true';
            if (isDismissed) return;
            
            if (pwaContainer) {
                pwaContainer.style.display = 'flex';
                setTimeout(() => pwaContainer.classList.add('show'), 50);
            }
        });

        if (installBtn) {
            installBtn.addEventListener('click', async () => {
                const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
                if (isIOS) { showIOSInstructions(); return; }
                if (deferredPrompt) {
                    deferredPrompt.prompt(); 
                    const { outcome } = await deferredPrompt.userChoice;
                    if(outcome === 'accepted') hidePWA();
                    deferredPrompt = null; 
                }
            });
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', (e) => {
                e.preventDefault(); e.stopPropagation();
                localStorage.setItem('pwa_dismissed', 'true');
                hidePWA();
            });
        }

        window.addEventListener('appinstalled', () => {
            hidePWA();
            localStorage.setItem('pwa_dismissed', 'true');
        });
    });

    function showIOSInstructions() {
        let modal = document.getElementById('iosModalPwa');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'iosModalPwa';
            modal.innerHTML = `
                <div class="ios-modal-content">
                    <h3>📱 Instalar en iPhone</h3>
                    <p>iOS no permite instalación directa. Sigue estos pasos:</p>
                    <div class="ios-steps">
                        <ol>
                            <li>Toca el botón <strong>Compartir</strong> <span style="font-size:1.2em">⎋</span></li>
                            <li>Busca y toca <strong>"Agregar a Inicio"</strong> <span style="font-size:1.2em">➕</span></li>
                            <li>Toca <strong>Agregar</strong> (arriba derecha)</li>
                        </ol>
                    </div>
                    <button class="ios-modal-close" onclick="document.getElementById('iosModalPwa').classList.remove('show')">Entendido</button>
                </div>`;
            document.body.appendChild(modal);
        }
        setTimeout(() => modal.classList.add('show'), 10);
    }

    // --- 3. LÓGICA PRINCIPAL DE INTERFAZ (UI) ---

    function updateUI() {
        const data = DATA_PYP[selectedCity];
        if (!data) return;

        console.log('📍 Renderizando ciudad:', data.nombre);

        // A. Actualizar TÍTULO PRINCIPAL (Header) Dinámicamente
        const pageTitle = document.getElementById('pageTitle');
        if(pageTitle) {
            // Lógica: "🚗 Pico y placa [Taxis] hoy en [Cali]"
            // Si es particular, a veces se prefiere omitir la palabra, pero la dejaremos para claridad
            let vLabel = data.vehiculo_label;
            pageTitle.innerHTML = `🚗 Pico y placa ${vLabel} hoy en ${data.nombre}`;
        }

        // B. Actualizar Textos Básicos
        const cityTodayEl = document.getElementById('city-today');
        if(cityTodayEl) cityTodayEl.textContent = data.nombre;
        
        const citySchedEl = document.getElementById('city-schedule');
        if(citySchedEl) citySchedEl.textContent = data.horario;
        
        const badgeEl = document.getElementById('vehicle-badge-current');
        if(badgeEl) badgeEl.textContent = data.vehiculo_label;
        
        const today = new Date();
        const options = {weekday: 'long', day: 'numeric', month: 'long'};
        const dateStr = today.toLocaleDateString('es-CO', options);
        const todayDateEl = document.getElementById('today-date');
        if(todayDateEl) todayDateEl.textContent = dateStr.charAt(0).toUpperCase() + dateStr.slice(1);

        // C. GENERAR PESTAÑAS DE VEHÍCULOS (Tabs)
        const tabsContainer = document.getElementById('vehicle-tabs-container');
        if (tabsContainer && data.vehiculos_disponibles) {
            tabsContainer.innerHTML = ''; // Limpiar
            
            // Recorrer los vehículos disponibles en esta ciudad
            Object.entries(data.vehiculos_disponibles).forEach(([key, label]) => {
                const isCurrent = key === data.vehiculo_actual_key;
                
                if (isCurrent) {
                    // Botón activo (Solo visual, ya estamos aquí)
                    const span = document.createElement('span');
                    span.className = 'vehicle-tab-btn active';
                    span.textContent = label;
                    tabsContainer.appendChild(span);
                } else {
                    // Enlace a la otra vista (Recarga necesaria para traer nuevos datos PHP)
                    const link = document.createElement('a');
                    link.className = 'vehicle-tab-btn';
                    link.textContent = label;
                    link.href = `?city=${selectedCity}&vehicle=${key}`; // Mantiene la ciudad actual
                    link.style.textDecoration = 'none';
                    tabsContainer.appendChild(link);
                }
            });
        }

        // D. Elementos del DOM de Restricciones
        const statusEl = document.getElementById('today-status');
        const restrictedContainer = document.getElementById('plates-restricted-today');
        const allowedContainer = document.getElementById('plates-allowed-today');
        const labelRestricted = document.getElementById('label-restricted');
        const msgContainer = document.getElementById('dynamic-message-container');
        
        // Limpiar contenedores
        if(restrictedContainer) restrictedContainer.innerHTML = '';
        if(allowedContainer) allowedContainer.innerHTML = '';
        if(labelRestricted) labelRestricted.style.display = 'block';
        if(msgContainer) msgContainer.style.display = 'none';
        document.body.classList.remove('sin-pico', 'pico-activo');

        // E. Lógica de Estados (Restricción, Festivo, Excepción)
        if(!statusEl) return; 

        if (data.es_excepcion) {
            statusEl.innerHTML = '<span style="color:#27ae60;">🔓 Medida Levantada</span>';
            if(msgContainer) {
                msgContainer.style.display = 'block';
                msgContainer.innerHTML = '✨ ' + (data.nombre_festivo || 'Medida levantada temporalmente');
                msgContainer.style.background = '#f0fff4'; msgContainer.style.color = '#276749';
            }
            if(labelRestricted) labelRestricted.style.display = 'none';
            if(allowedContainer) allowedContainer.innerHTML = '<span class="plate-badge wide">Todas las placas están autorizadas</span>';
            document.body.classList.add('sin-pico');
            
        } else if (data.nombre_festivo) {
            statusEl.innerHTML = '<span style="color:#27ae60;">🎉 ' + data.nombre_festivo + '</span>';
            if(msgContainer) {
                msgContainer.style.display = 'block';
                msgContainer.innerHTML = '🎉 Hoy es ' + data.nombre_festivo + ', pueden circular todos los vehículos.';
                msgContainer.style.background = '#f0fff4'; msgContainer.style.color = '#276749';
            }
            if(labelRestricted) labelRestricted.style.display = 'none';
            if(allowedContainer) allowedContainer.innerHTML = '<span class="plate-badge wide">Todas las placas están autorizadas</span>';
            document.body.classList.add('sin-pico');

        } else if (data.restricciones && data.restricciones.length > 0) {
            statusEl.innerHTML = '<span style="color:#e74c3c;">🚫 Hay Pico y Placa</span>';
            if(restrictedContainer) {
                data.restricciones.forEach(p => restrictedContainer.innerHTML += `<span class="plate-badge restricted">${p}</span>`);
            }
            if(allowedContainer) {
                data.permitidas.forEach(p => allowedContainer.innerHTML += `<span class="plate-badge">${p}</span>`);
            }
            document.body.classList.add('pico-activo');

        } else {
            statusEl.innerHTML = '<span style="color:#27ae60;">✅ Sin Restricción</span>';
            if(msgContainer) {
                msgContainer.style.display = 'block';
                msgContainer.innerHTML = '✅ Hoy no aplica la medida en ' + data.nombre + '.';
                msgContainer.style.background = '#f0fff4';
            }
            if(labelRestricted) labelRestricted.style.display = 'none';
            if(allowedContainer) allowedContainer.innerHTML = '<span class="plate-badge wide">Todas las placas están autorizadas</span>';
            document.body.classList.add('sin-pico');
        }

        // F. Reloj y Pronóstico
        startCountdown(data.target_ts, data.estado_reloj);
        if (document.getElementById('forecast-container')) {
            renderForecast(data.pronostico);
        }
    }

    // --- 4. RELOJ CUENTA REGRESIVA ---
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
            titulo = '⏳ Inicia en:'; mensaje = 'La medida comienza hoy.'; 
        } else if (estado === 'termina') { 
            titulo = '🚨 Termina en:'; mensaje = 'Restricción activa.'; 
        } else if (estado === 'proximo') { 
            titulo = '📅 Próxima:'; 
            const d = new Date(targetTimestamp * 1000);
            const dia = d.toLocaleDateString('es-CO', {weekday:'long'});
            let hours = d.getHours();
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;
            const min = d.getMinutes().toString().padStart(2, '0');
            mensaje = `Inicia el ${dia} a las ${hours}:${min} ${ampm}`;
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
            
            const container = document.getElementById('countdownContainer');
            if (container && !container.classList.contains('show')) container.classList.add('show');
        }
        tick();
        countdownInterval = setInterval(tick, 1000);
    }

    // --- 5. PRONÓSTICO ---
    function renderForecast(dias) {
        const container = document.getElementById('forecast-container');
        if (!container || !dias) return;
        
        container.innerHTML = '';
        
        dias.forEach(dia => {
            const esLibre = dia.estado === 'libre' || dia.estado === 'festivo';
            const colorBg = esLibre ? '#f0fff4' : '#fff5f5';
            const colorBorde = esLibre ? '#c6f6d5' : '#fed7d7';
            const icono = dia.estado === 'festivo' ? '🎉' : (esLibre ? '✅' : '🚫');
            
            let contenidoCentral = dia.placas;
            let estiloFuente = "font-size: 0.85rem; font-weight: 700; color: #333;";
            
            if (dia.motivo_libre) {
                contenidoCentral = dia.motivo_libre; 
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

    // --- 6. UTILIDADES UI (Grid, Buscador, Placas) ---

    // Función llamada al hacer click en un botón del GRID de ciudades
    function selectCity(cityCode) {
        selectedCity = cityCode;
        
        // Actualizar visualmente el grid
        document.querySelectorAll('.city-grid-item').forEach(btn => btn.classList.remove('active'));
        const btn = document.getElementById('btn-'+cityCode);
        if(btn) btn.classList.add('active');
        
        // Renderizar todos los datos de esa ciudad
        updateUI();
        
        // Actualizar URL sin recargar (para compartir enlace)
        const url = new URL(window.location);
        url.searchParams.set('city', cityCode);
        // Si el vehículo actual es 'particular' (default), limpiamos param para url limpia, opcional
        window.history.pushState({}, '', url);
        
        // Limpiar resultados de búsqueda de placa anterior
        const resBox = document.getElementById('result-box');
        if(resBox) { resBox.innerHTML = ''; resBox.className = 'result-box'; resBox.style.display = 'none'; }
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
    
    // Función llamada por el formulario de "Buscar otra fecha"
    // (Definida en index.php, pero si se necesita aquí como fallback)
    /* function searchByDate(e) {
        e.preventDefault();
        const d = document.getElementById('dateInput').value;
        const c = document.getElementById('citySelect').value;
        const v = document.getElementById('vehicleSelect').value; // Nuevo campo
        if(d && c) window.location.href = `/pico-y-placa/${d}-${c}?vehicle=${v}`;
    }
    */

    // --- 7. INICIALIZACIÓN ---
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar UI con los datos cargados por PHP
        if(typeof updateUI === 'function') updateUI();
        
        // Listeners para input de placa
        const plateInput = document.getElementById('plate-input');
        if (plateInput) {
            plateInput.addEventListener('input', function() { this.value = this.value.replace(/[^0-9]/g, ''); });
            plateInput.addEventListener('keypress', function(e) { if (e.key === 'Enter') searchPlate(); });
        }
        
        // Registrar Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/service-worker.js')
                .then(r => console.log('SW Registrado'))
                .catch(e => console.log('SW Error:', e));
            });
        }
    });
</script>
</body>
</html>
