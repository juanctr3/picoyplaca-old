<footer class="main-footer">
    <div class="container">
        <p>&copy; <?php echo date('Y'); ?> <strong>Pico y Placa Colombia</strong>. Todos los derechos reservados.</p>
        <p class="disclaimer">
            La información es de carácter informativo. Recomendamos consultar fuentes oficiales de cada alcaldía.
        </p>
        <div class="version">Versión 5.0 - Logic & Design Update</div>
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
    // Si no hay ciudad, el PHP forzó 'bogota', así que esto siempre tiene valor.
    let selectedCity = '<?php echo $ciudad_sel_url; ?>';
    
    // Objeto masivo con toda la data pre-cargada para el vehículo actual
    const DATA_PYP = <?php echo isset($datos_hoy_json) ? $datos_hoy_json : '{}'; ?>;
    
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

        console.log('📍 Renderizando:', data.nombre, '| Estado:', data.estado_reloj);

        // A. Actualizar Textos y Header
        const pageTitle = document.getElementById('pageTitle');
        if(pageTitle) {
            // Actualizar título dinámico para SEO visual
            let vLabel = data.vehiculo_label;
            // Solo agregar etiqueta si no es particular para no saturar
            let vText = (data.vehiculo_actual_key === 'particular') ? '' : vLabel;
            pageTitle.innerHTML = `🚗 Pico y placa ${vText} hoy en ${data.nombre}`;
        }
        
        // Subtítulo dinámico
        const citySub = document.getElementById('cityNameSubtitle');
        if(citySub) citySub.textContent = data.nombre;

        document.getElementById('city-today').textContent = data.nombre;
        document.getElementById('city-schedule').textContent = data.horario;
        
        const badgeEl = document.getElementById('vehicle-badge-current');
        if(badgeEl) badgeEl.textContent = data.vehiculo_label;
        
        const today = new Date();
        const options = {weekday: 'long', day: 'numeric', month: 'long'};
        const dateStr = today.toLocaleDateString('es-CO', options);
        document.getElementById('today-date').textContent = dateStr.charAt(0).toUpperCase() + dateStr.slice(1);

        // B. CAMBIO DE FONDO (Background Dinámico)
        // 'termina' significa que estamos DENTRO del horario de restricción (contando para que termine)
        if (data.estado_reloj === 'termina') {
            document.body.classList.remove('sin-pico');
            document.body.classList.add('pico-activo'); // Fondo Rojo/Alerta
        } else {
            document.body.classList.remove('pico-activo');
            document.body.classList.add('sin-pico'); // Fondo Verde/Azul
        }

        // C. TABS DE VEHÍCULOS
        const tabsContainer = document.getElementById('vehicle-tabs-container');
        if (tabsContainer && data.vehiculos_disponibles) {
            tabsContainer.innerHTML = ''; 
            Object.entries(data.vehiculos_disponibles).forEach(([key, label]) => {
                const isCurrent = key === data.vehiculo_actual_key;
                if (isCurrent) {
                    const span = document.createElement('span');
                    span.className = 'vehicle-tab-btn active';
                    span.textContent = label;
                    tabsContainer.appendChild(span);
                } else {
                    const link = document.createElement('a');
                    link.className = 'vehicle-tab-btn';
                    link.textContent = label;
                    link.href = `?city=${selectedCity}&vehicle=${key}`;
                    link.style.textDecoration = 'none';
                    tabsContainer.appendChild(link);
                }
            });
        }

        // D. Restricciones (Placas)
        const restrictedContainer = document.getElementById('plates-restricted-today');
        const allowedContainer = document.getElementById('plates-allowed-today');
        const labelRestricted = document.getElementById('label-restricted');
        const msgContainer = document.getElementById('dynamic-message-container');
        
        if(restrictedContainer) restrictedContainer.innerHTML = '';
        if(allowedContainer) allowedContainer.innerHTML = '';
        if(labelRestricted) labelRestricted.style.display = 'block';
        if(msgContainer) msgContainer.style.display = 'none';
        
        const statusEl = document.getElementById('today-status');

        // Lógica de visualización de placas y mensajes
        if (data.es_excepcion) {
            if(statusEl) statusEl.innerHTML = '<span style="color:#27ae60;">🔓 Medida Levantada</span>';
            if(msgContainer) {
                msgContainer.style.display = 'block';
                msgContainer.innerHTML = '✨ ' + (data.nombre_festivo || 'Medida levantada temporalmente');
                msgContainer.style.background = '#f0fff4'; msgContainer.style.color = '#276749';
            }
            if(labelRestricted) labelRestricted.style.display = 'none';
            if(allowedContainer) allowedContainer.innerHTML = '<span class="plate-badge wide">Todas las placas están autorizadas</span>';
            
        } else if (data.nombre_festivo) {
            if(statusEl) statusEl.innerHTML = '<span style="color:#27ae60;">🎉 ' + data.nombre_festivo + '</span>';
            if(msgContainer) {
                msgContainer.style.display = 'block';
                msgContainer.innerHTML = '🎉 Hoy es ' + data.nombre_festivo + ', pueden circular todos los vehículos.';
                msgContainer.style.background = '#f0fff4'; msgContainer.style.color = '#276749';
            }
            if(labelRestricted) labelRestricted.style.display = 'none';
            if(allowedContainer) allowedContainer.innerHTML = '<span class="plate-badge wide">Todas las placas están autorizadas</span>';

        } else if (data.es_fin_semana || (data.restricciones && data.restricciones.length === 0)) {
             // Fin de semana o día sin medida para este vehículo
            if(statusEl) statusEl.innerHTML = '<span style="color:#27ae60;">✅ Sin Restricción</span>';
            if(msgContainer) {
                msgContainer.style.display = 'block';
                msgContainer.innerHTML = '✅ Hoy no aplica la medida para ' + data.vehiculo_label.toLowerCase() + ' en ' + data.nombre + '.';
                msgContainer.style.background = '#f0fff4';
            }
            if(labelRestricted) labelRestricted.style.display = 'none';
            if(allowedContainer) allowedContainer.innerHTML = '<span class="plate-badge wide">Todas las placas están autorizadas</span>';

        } else {
            // RESTRICCIÓN ACTIVA (Día hábil normal)
            if(statusEl) statusEl.innerHTML = '<span style="color:#e74c3c;">🚫 Hay Pico y Placa</span>';
            if(restrictedContainer) {
                data.restricciones.forEach(p => restrictedContainer.innerHTML += `<span class="plate-badge restricted">${p}</span>`);
            }
            if(allowedContainer) {
                data.permitidas.forEach(p => allowedContainer.innerHTML += `<span class="plate-badge">${p}</span>`);
            }
        }

        // E. Iniciar Reloj
        startCountdown(data.target_ts, data.estado_reloj);
        
        // F. Pronóstico
        if (document.getElementById('forecast-container')) {
            renderForecast(data.pronostico);
        }
    }

    // --- 4. RELOJ CUENTA REGRESIVA INTELIGENTE ---
    function startCountdown(targetTimestamp, estado) {
        clearInterval(countdownInterval);
        const titleEl = document.getElementById('countdownTitle');
        const msgEl = document.getElementById('countdownMessage');
        const displayEl = document.getElementById('countdownDisplay');
        
        if (!titleEl) return;

        // Si es libre (festivo/fin de semana), ocultar números y mostrar mensaje
        if (estado === 'libre' || estado === 'sin_datos') {
            titleEl.textContent = '✅ Sin Restricción';
            if(msgEl) msgEl.textContent = 'Puedes circular libremente en este momento.';
            if(displayEl) displayEl.style.display = 'none'; // Ocultar reloj
            return;
        } else {
            if(displayEl) displayEl.style.display = 'flex'; // Mostrar reloj
        }
        
        let titulo = '', mensaje = '';
        
        if (estado === 'inicia') { 
            titulo = '⏳ Comienza en:'; 
            mensaje = 'La medida inicia pronto. ¡Prepara tu viaje!'; 
        } else if (estado === 'termina') { 
            titulo = '🚨 Termina en:'; 
            mensaje = 'Restricción vigente. Espera a que termine.'; 
        } else if (estado === 'proximo') { 
            titulo = '📅 Próximo inicio:'; 
            // Formatear fecha próxima
            const d = new Date(targetTimestamp * 1000);
            const dia = d.toLocaleDateString('es-CO', {weekday:'long'});
            let hours = d.getHours();
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;
            const min = d.getMinutes().toString().padStart(2, '0');
            mensaje = `Vuelve a iniciar el ${dia} a las ${hours}:${min} ${ampm}`;
        }
        
        titleEl.textContent = titulo;
        if(msgEl) msgEl.textContent = mensaje;

        function tick() {
            const now = Math.floor(Date.now() / 1000);
            const diff = targetTimestamp - now;
            
            // Si el tiempo llegó a 0, recargamos para actualizar estado (ej: de Inicia a Termina)
            if (diff <= 0) { location.reload(); return; }
            
            // Calculadora de tiempo
            let d = Math.floor(diff / (3600 * 24));
            let h = Math.floor((diff % (3600 * 24)) / 3600);
            let m = Math.floor((diff % 3600) / 60);
            let s = diff % 60;
            
            // Si falta más de 24h (ej: fin de semana), sumar días a horas visualmente o mostrar días
            if (d > 0) h = h + (d * 24);

            const elH = document.getElementById('countdownHours');
            const elM = document.getElementById('countdownMinutes');
            const elS = document.getElementById('countdownSeconds');
            
            if(elH) elH.textContent = h.toString().padStart(2,'0');
            if(elM) elM.textContent = m.toString().padStart(2,'0');
            if(elS) elS.textContent = s.toString().padStart(2,'0');
            
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

    // --- 6. UTILIDADES ---
    function selectCity(cityCode) {
        selectedCity = cityCode;
        
        document.querySelectorAll('.city-grid-item').forEach(btn => btn.classList.remove('active'));
        const btn = document.getElementById('btn-'+cityCode);
        if(btn) btn.classList.add('active');
        
        updateUI();
        
        const url = new URL(window.location);
        url.searchParams.set('city', cityCode);
        window.history.pushState({}, '', url);
        
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

        // Chequear excepciones globales para la ciudad antes de placa
        if (data.es_festivo || data.es_excepcion || data.es_fin_semana || (data.restricciones && data.restricciones.length === 0)) {
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

    // --- 7. INIT ---
    document.addEventListener('DOMContentLoaded', function() {
        if(typeof updateUI === 'function') updateUI();
        
        const plateInput = document.getElementById('plate-input');
        if (plateInput) {
            plateInput.addEventListener('input', function() { this.value = this.value.replace(/[^0-9]/g, ''); });
            plateInput.addEventListener('keypress', function(e) { if (e.key === 'Enter') searchPlate(); });
        }
        
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
