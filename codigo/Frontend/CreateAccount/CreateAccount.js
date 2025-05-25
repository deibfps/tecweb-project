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

    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const accountType = document.getElementById('accountType').value;
    const adminKey = document.getElementById('adminKey');

    //VALIDACIONES DE LLENADO DE CAMPOS:

    if (accountType === 'admin' && adminKey !== 'ADMIN33') {
        alert('Clave de administrador incorrecta');
        return;
    }

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

    if (accountType === 'admin' && adminKey.value !== 'ADMIN33') {
        alert('Clave de administrador incorrecta.');
        adminKey.focus();
        return;
    }

    $.ajax({
        url: 'http://localhost:8080/api/signup',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            email:email.value,
            password: password.value,
            rol: accountType
        }),
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                alert('Cuenta creada exitosamente');
                window.location.href = '../Login/index.html';
            } else {
                alert(data.message || 'Error al crear la cuenta');
            }
        },
        error: function() {
            alert('Error de conexión con el servidor');
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