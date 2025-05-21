document.addEventListener('DOMContentLoaded', function() {
    const id_usuario = localStorage.getItem('id_usuario');
    if (!id_usuario) {
        window.location.href = '../Login/index.html';
        return;
    }

    // Obtén los datos del perfil y muéstralos
    fetch(`http://localhost:8080/api/perfil/${id_usuario}`)
        .then(res => res.json())
        .then(data => {
            if (data.exists) {
                document.getElementById('nombre').textContent = data.perfil.nombre;
                document.getElementById('apellido').textContent = data.perfil.apellido;
                document.getElementById('pronombres').textContent = data.perfil.pronombres;
                document.getElementById('fecha-nacimiento').textContent = data.perfil.fecha_nacimiento;
                document.getElementById('biografia').textContent = data.perfil.biografia;
            } else {
                window.location.href = '../Profile/index.html';
            }
        })
        .catch(() => alert('Error al cargar el perfil'));
});

// Función para cerrar sesión
function logout() {
    localStorage.removeItem('usuarioLogueado');
    localStorage.removeItem('rol');
    localStorage.removeItem('id_usuario');
    window.location.href = '../Login/index.html';
}

function editarPerfil() {
    window.location.href = '../Profile/index.html?edit=1';
}