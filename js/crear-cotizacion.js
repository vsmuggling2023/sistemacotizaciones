document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const bsTabs = document.querySelectorAll('[data-bs-toggle="tab"]');

    // Bloquear fechas pasadas en Fecha_servicio
    const fechaInput = document.getElementById('Fecha_servicio');
        if (fechaInput) {
            const ahora = new Date();
            ahora.setSeconds(0, 0);

            const year = ahora.getFullYear();
            const month = String(ahora.getMonth() + 1).padStart(2, '0');
            const day = String(ahora.getDate()).padStart(2, '0');
            const hour = String(ahora.getHours()).padStart(2, '0');
            const minute = String(ahora.getMinutes()).padStart(2, '0');

            const hoyLocal = `${year}-${month}-${day}T${hour}:${minute}`;
            fechaInput.min = hoyLocal;
        }


    // Restaurar pestaña activa
    const activa = document.getElementById('pestana_activa')?.value;
    if (activa) {
        const tabBtn = document.querySelector(`[data-bs-target="#${activa}"]`);
        if (tabBtn) {
            const tab = new bootstrap.Tab(tabBtn);
            tab.show();
        }
    }

    bsTabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function() {
            document.getElementById('pestana_activa').value = this.getAttribute('data-bs-target').substring(1);
        });
    });

    form.addEventListener('submit', function(e) {
        const submitBtn = document.activeElement;
        const esConfirmacion = submitBtn?.name === 'confirmar';
        if (!esConfirmacion && !validarFormulario()) {
            e.preventDefault();
            mostrarPrimerError();
        }
    });

    function validarFormulario() {
        let valido = true;
        resetErrores();

        const camposRequeridos = obtenerCamposRequeridos();
        const tabsConErrores = new Set();

        camposRequeridos.forEach(campo => {
            const elemento = document.querySelector(`[name="${campo.id}"]`);
            // Validar sólo si el elemento existe, está visible y NO está deshabilitado
            if (elemento && elemento.offsetParent !== null && !elemento.disabled) {
                if (!elemento.value || elemento.value.trim() === '') {
                    marcarError(elemento, `❌ ${campo.mensaje || 'Este campo es obligatorio'}`);
                    valido = false;
                    if (campo.tab) tabsConErrores.add(campo.tab);
                }
            }
        });

        if (!validarKm()) valido = false;

        resaltarTabsConErrores(tabsConErrores);

        if (!valido) mostrarResumenErrores();
        return valido;
    }

    function obtenerCamposRequeridos() {
        const campos = [
            {id: 'id_division', mensaje: 'Seleccione una división', tab: 'tab1'},
            {id: 'id_servicio', mensaje: 'Seleccione un servicio', tab: 'tab1'},
            {id: 'id_turno', mensaje: 'Seleccione un turno', tab: 'tab1'},
            {id: 'rut_cliente', mensaje: 'Seleccione un cliente', tab: 'tab1'},
            {id: 'email', mensaje: 'Ingrese un email', tab: 'tab1'},
            {id: 'Fecha_servicio', mensaje: 'Ingrese fecha y hora', tab: 'tab1'},
            {id: 'TELEFONO', mensaje: 'Ingrese teléfono', tab: 'tab1'},
            {id: 'id_comuna_origen', mensaje: 'Seleccione comuna origen', tab: 'tab2'},
            {id: 'id_comuna_destino', mensaje: 'Seleccione comuna destino', tab: 'tab2'},
            {id: 'base_salida', mensaje: 'Ingrese base de salida', tab: 'tab2'},
            {id: 'tipo_camino', mensaje: 'Ingrese tipo de camino', tab: 'tab2'}
        ];

        // Solo agregar total_km si está habilitado (no deshabilitado)
        const kmInput = document.querySelector('[name="total_km"]');
        if (kmInput && !kmInput.disabled) {
            campos.push({id: 'total_km', mensaje: 'Ingrese total de KM', tab: 'tab2'});
        }

        if (document.getElementById('tab3')) {
            const vehiculo = document.querySelector('[name="id_tipo_vehiculo"]');
            if (vehiculo) campos.push({id: 'id_tipo_vehiculo', mensaje: 'Seleccione tipo de vehículo', tab: 'tab3'});

            const subtipo = document.querySelector('[name="id_sub_tipo"], [name="sub_tipo"]');
            if (subtipo) campos.push({id: subtipo.name, mensaje: 'Seleccione subtipo', tab: 'tab3'});

            const carga = document.querySelector('[name="carga"]');
            if (carga) campos.push({id: 'carga', mensaje: 'Seleccione carga', tab: 'tab3'});

            const direccion = document.querySelector('[name="tipo_direccion"]');
            if (direccion) campos.push({id: 'tipo_direccion', mensaje: 'Seleccione tipo de dirección', tab: 'tab3'});
        }

        return campos;
    }

    function validarKm() {
        const kmInput = document.querySelector('[name="total_km"]');
        // Solo validar si está visible Y habilitado
        if (kmInput && kmInput.offsetParent !== null && !kmInput.disabled) {
            if (!kmInput.value || isNaN(parseFloat(kmInput.value))) {
                marcarError(kmInput, '❌ Debe ser un número válido');
                return false;
            }
        }
        return true;
    }

    function marcarError(elemento, mensaje) {
        if (!elemento) return;
        elemento.classList.add('campo-faltante');

        let divError = elemento.nextElementSibling;
        if (!divError || !divError.classList.contains('mensaje-error-campo')) {
            divError = document.createElement('div');
            divError.className = 'mensaje-error-campo';
            elemento.parentNode.insertBefore(divError, elemento.nextSibling);
        }
        divError.innerHTML = mensaje;

        if (elemento.tagName === 'SELECT') {
            const arrow = document.createElement('span');
            arrow.className = 'arrow-indicator';
            arrow.innerHTML = '➤';
            elemento.parentNode.style.position = 'relative';
            elemento.parentNode.appendChild(arrow);
        }
    }

    function resaltarTabsConErrores(tabsConErrores) {
        document.querySelectorAll('.nav-link').forEach(tab => {
            tab.classList.remove('tab-con-error');
        });

        tabsConErrores.forEach(tabId => {
            const tabButton = document.querySelector(`[data-bs-target="#${tabId}"]`);
            if (tabButton) {
                tabButton.classList.add('tab-con-error');
            }
        });
    }

    function mostrarPrimerError() {
        const firstErrorField = document.querySelector('.campo-faltante');
        if (firstErrorField) {
            const tabPane = firstErrorField.closest('.tab-pane');
            if (tabPane) {
                const tabId = tabPane.id;
                const tabButton = document.querySelector(`[data-bs-target="#${tabId}"]`);
                if (tabButton) {
                    const tab = new bootstrap.Tab(tabButton);
                    tab.show();
                    setTimeout(() => {
                        firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstErrorField.focus();
                    }, 300);
                }
            }
        }
    }

    function mostrarResumenErrores() {
        const errores = [];
        document.querySelectorAll('.campo-faltante').forEach(el => {
            const label = document.querySelector(`label[for="${el.id}"]`);
            errores.push(label ? label.textContent : el.name);
        });

        let resumen = document.querySelector('.resumen-errores');
        if (!resumen) {
            resumen = document.createElement('div');
            resumen.className = 'resumen-errores';
            form.prepend(resumen);
        }

        resumen.innerHTML = `
            <h5><span style="color: #dc3545;">⚠️ ATENCIÓN:</span> Faltan datos obligatorios</h5>
            <p>Por favor complete los siguientes campos en las <span class="tab-con-error" style="padding: 2px 5px;">pestañas marcadas en rojo</span>:</p>
            <ul>
                ${errores.map(error => `<li><strong>${error}</strong></li>`).join('')}
            </ul>
            <p class="mb-0">Siga las flechas <span class="arrow-indicator" style="position: static; display: inline-block;">➤</span> y los mensajes en rojo.</p>
        `;

        resumen.scrollIntoView({ behavior: 'smooth' });
    }

    function resetErrores() {
        document.querySelectorAll('.campo-faltante').forEach(el => el.classList.remove('campo-faltante'));
        document.querySelectorAll('.mensaje-error-campo, .arrow-indicator').forEach(el => el.remove());
        const resumen = document.querySelector('.resumen-errores');
        if (resumen) resumen.remove();
        document.querySelectorAll('.nav-link.tab-con-error').forEach(el => el.classList.remove('tab-con-error'));
    }

    // Validación en tiempo real para limpiar errores cuando el usuario corrige
    document.querySelectorAll('input, select, textarea').forEach(input => {
        input.addEventListener('change', function() {
            if (this.value && this.value.trim() !== '') {
                this.classList.remove('campo-faltante');
                const errorMsg = this.nextElementSibling;
                if (errorMsg && errorMsg.classList.contains('mensaje-error-campo')) {
                    errorMsg.remove();
                }

                const tabPane = this.closest('.tab-pane');
                if (tabPane) {
                    const hasErrors = tabPane.querySelector('.campo-faltante');
                    if (!hasErrors) {
                        const tabId = tabPane.id;
                        const tabButton = document.querySelector(`[data-bs-target="#${tabId}"]`);
                        if (tabButton) tabButton.classList.remove('tab-con-error');
                    }
                }
            }
        });
    });
});