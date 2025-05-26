document.getElementById('accountType').addEventListener('change', function() {
    const adminKeyGroup = document.getElementById('adminKeyGroup');
    if (this.value === 'admin') {
        adminKeyGroup.classList.add('visible');
        document.getElementById('adminKey').required = true;
    } else {
        adminKeyGroup.classList.remove('visible');
        document.getElementById('adminKey').required = false;
        // Limpia el valor del campo y el input visualmente
        document.getElementById('adminKey').value = '';
    }
});

document.getElementById('createAccountForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const accountType = document.getElementById('accountType').value;
    const adminKey = document.getElementById('adminKey').value;

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

    if (accountType === 'admin' && adminKey !== 'ADMIN33') {
        mostrarMensaje('Clave de administrador incorrecta');
        return;
    }

    $.ajax({
        url: 'http://localhost:8080/api/signup',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            email,
            password,
            rol: accountType
        }),
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                mostrarMensaje('Cuenta creada exitosamente');
                window.location.href = '../Login/index.html';
            } else {
                mostrarMensaje(data.message || 'Error al crear la cuenta');
            }
        },
        error: function() {
            mostrarMensaje('Error de conexión con el servidor');
        }
    });
});

document.getElementById('togglePassword').addEventListener('click', function () {
    const passwordInput = document.getElementById('password');
    const eyeOpen = document.getElementById('eyeOpen');
    const eyeClosed = document.getElementById('eyeClosed');

    const isPassword = passwordInput.type === 'password';
    passwordInput.type = isPassword ? 'text' : 'password';

    eyeOpen.style.display = isPassword ? 'none' : 'inline';
    eyeClosed.style.display = isPassword ? 'inline' : 'none';
});