document.getElementById('accountType').addEventListener('change', function() {
    const adminKeyGroup = document.getElementById('adminKeyGroup');
    if (this.value === 'admin') {
        adminKeyGroup.style.display = 'block';
        document.getElementById('adminKey').required = true;
    } else {
        adminKeyGroup.style.display = 'none';
        document.getElementById('adminKey').required = false;
    }
});

document.getElementById('createAccountForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const accountType = document.getElementById('accountType').value;
    const adminKey = document.getElementById('adminKey').value;

    if (accountType === 'admin' && adminKey !== 'ADMIN33') {
        alert('Clave de administrador incorrecta');
        return;
    }

    fetch('http://localhost:8080/api/signup', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            email,
            password,
            rol: accountType
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Cuenta creada exitosamente');
            window.location.href = '../Login/index.html';
        } else {
            alert(data.message || 'Error al crear la cuenta');
        }
    })
    .catch(() => alert('Error de conexión con el servidor'));
});

document.getElementById('togglePassword').addEventListener('click', function () {
    const passwordInput = document.getElementById('password');
    const type = passwordInput.type === 'password' ? 'text' : 'password';
    passwordInput.type = type;
    this.textContent = type === 'password' ? '👁️' : '🙈';
});