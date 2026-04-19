document.addEventListener('DOMContentLoaded', function() {
    const input = document.querySelector('#Fecha_servicio');
    if (!input) return; // Sale si no existe el input

    const now = new Date();

    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');

    const minDate = `${year}-${month}-${day}T00:00`; // Solo bloquea días anteriores
    input.min = minDate;
});
