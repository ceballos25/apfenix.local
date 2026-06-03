function buscarTickets() {
    const valor = document.getElementById('inputBuscarTickets').value.trim();
    const resultado = document.getElementById('resultadoBusqueda');

    // Validar 10 dígitos
    if (!valor) {
        resultado.innerHTML = `<div class="alert alert-danger mb-0">Ingresa un dato válido</div>`;
        return;
    }

    if (!/^\d{10}$/.test(valor)) {
        resultado.innerHTML = `<div class="alert alert-danger mb-0">El celular debe contener exactamente 10 dígitos</div>`;
        return;
    }

    // Mostrar loading
    resultado.innerHTML = `
        <div class="alert alert-info mb-0">
            Buscando tickets para: <strong>${valor}</strong>...
        </div>
    `;

    // AJAX a tu endpoint
    $.ajax({
        url: 'front/ajax/ventas.ajax.php',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'obtener_por_celular',
            phone_customer: valor
        },
        success: function(resp) {
            if (!resp.success) {
                resultado.innerHTML = `
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle"></i> ${resp.message || 'No encontrado'}
                    </div>
                `;
                return;
            }

            // Insertar el HTML de la plantilla
            resultado.innerHTML = resp.html;
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', error);
            resultado.innerHTML = `
                <div class="alert alert-danger mb-0">
                    <i class="bi bi-exclamation-circle"></i> Error consultando los tickets
                </div>
            `;
        }
    });
}

// Foco automático al abrir modal
const modal = document.getElementById('modalBuscarTickets');
modal.addEventListener('shown.bs.modal', () => {
    document.getElementById('inputBuscarTickets').focus();
});

// Enter para buscar
document.getElementById('inputBuscarTickets').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        buscarTickets();
    }
});