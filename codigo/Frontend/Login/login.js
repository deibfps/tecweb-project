document.querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault();

    const email = document.getElementById('email');
    const password = document.getElementById('password');

    //VALIDACIONES FORMULARIO:
    
    if (email.value.trim() === '' || !email.value.includes('@')) {
        alert('Ingresa un correo válido.');
        email.focus();
        return;
    }

    if (password.value.length < 6) {
        alert('La contraseña debe tener al menos 6 caracteres.');
        password.focus();
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
                alert(data.message);
            }
        },
        error: function() {
            alert('Error de conexión con el servidor');
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
