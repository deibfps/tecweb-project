document.querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault();

    // Aquí deberías hacer la petición AJAX al backend para validar usuario y contraseña
    // Por ahora, simulamos un login exitoso:
    // Si quieres validar, reemplaza esto por tu fetch/AJAX real

    // Simulación de login exitoso
    localStorage.setItem('usuarioLogueado', 'true');
    window.location.href = '../Home/index.html';
});