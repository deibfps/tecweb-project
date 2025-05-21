document.querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault();

    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;

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
