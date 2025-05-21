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

document.getElementById('togglePassword').addEventListener('click', function () {
    const passwordInput = document.getElementById('password');
    const type = passwordInput.type === 'password' ? 'text' : 'password';
    passwordInput.type = type;
    this.textContent = type === 'password' ? '👁️' : '🙈';
});