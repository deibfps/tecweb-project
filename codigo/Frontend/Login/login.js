//Función para mostrar el mensaje de alerta
function mostrarMensaje(texto, tipo = 'error') {
    const msg = document.getElementById('mensajeFlotante');
    if (!msg) return;

    msg.textContent = texto;
    msg.classList.remove('oculto', 'ok');
    if (tipo === 'ok') msg.classList.add('ok');

    setTimeout(() => {
        msg.classList.add('oculto');
    }, 3000);
}

document.querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault();

    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;

    //VALIDACIONES

    if (email === '' || !email.includes('@')) {
        mostrarMensaje('Ingresa un correo válido.');
        document.getElementById('email').focus();
        return;
    }

    if (password.length < 6) {
        mostrarMensaje('La contraseña debe tener al menos 6 caracteres.');
        document.getElementById('password').focus();
        return;
    }

    $.ajax({
        url: 'http://localhost:8080/api/login',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ email, password }),
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                localStorage.setItem('usuarioLogueado', 'true');
                localStorage.setItem('rol', data.rol);
                localStorage.setItem('id_usuario', data.id_usuario);
                window.location.href = '../Home/index.html';
            } else {
                mostrarMensaje(data.message);
            }
        },
        error: function() {
            mostrarMensaje('Error de conexión con el servidor');
        }
    });
});

// Mostrar/ocultar contraseña
document.getElementById('togglePassword').addEventListener('click', function () {
    const passwordInput = document.getElementById('password');
    const eyeOpen = document.getElementById('eyeOpen');
    const eyeClosed = document.getElementById('eyeClosed');

    const isPassword = passwordInput.type === 'password';
    passwordInput.type = isPassword ? 'text' : 'password';

    eyeOpen.style.display = isPassword ? 'none' : 'inline';
    eyeClosed.style.display = isPassword ? 'inline' : 'none';
});
