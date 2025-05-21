document.querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault();

    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;

    fetch('http://localhost:8080/api/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            localStorage.setItem('usuarioLogueado', 'true');
            localStorage.setItem('rol', data.rol);
            localStorage.setItem('id_usuario', data.id_usuario);
            window.location.href = '../Home/index.html';
        } else {
            alert(data.message);
        }
    })
    .catch(() => alert('Error de conexión con el servidor'));
});