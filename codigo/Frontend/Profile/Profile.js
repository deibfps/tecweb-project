if (!localStorage.getItem('usuarioLogueado')) {
    alert("Inicia sesión para acceder a contenido exclusivo");
    window.location.href = '../Login/index.html';
}

function logout() {
    localStorage.removeItem('usuarioLogueado');
    localStorage.removeItem('rol');
    localStorage.removeItem('id_usuario');
    window.location.href = '../Login/index.html';
}

document.addEventListener('DOMContentLoaded', function() {
    const id_usuario = localStorage.getItem('id_usuario');
    if (!id_usuario) {
        window.location.href = '../Login/index.html';
        return;
    }

    const params = new URLSearchParams(window.location.search);
    const isEdit = params.get('edit') === '1';

    // Solo redirige si NO es edición y ya tiene perfil
    if (!isEdit) {
        fetch(`http://localhost:8080/api/perfil/${id_usuario}`)
            .then(res => res.json())
            .then(data => {
                if (data.exists) {
                    window.location.href = '../ProfileInfo/index.html';
                }
            })
            .catch(() => alert('Error al verificar perfil'));
    } else {
        // Si es edición, autorrellena el formulario
        fetch(`http://localhost:8080/api/perfil/${id_usuario}`)
            .then(res => res.json())
            .then(data => {
                if (data.exists) {
                    document.getElementById('nombre').value = data.perfil.nombre;
                    document.getElementById('apellido').value = data.perfil.apellido;
                    document.getElementById('pronombres').value = data.perfil.pronombres;
                    document.getElementById('fecha-nacimiento').value = data.perfil.fecha_nacimiento;
                    document.getElementById('biografia').value = data.perfil.biografia;
                }
            });
    }

    document.getElementById('formPerfil').addEventListener('submit', function(e) {
        e.preventDefault();
        const nombre = document.getElementById('nombre').value;
        const apellido = document.getElementById('apellido').value;
        const pronombres = document.getElementById('pronombres').value;
        const fecha_nacimiento = document.getElementById('fecha-nacimiento').value;
        const biografia = document.getElementById('biografia').value;

        fetch('http://localhost:8080/api/perfil', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_usuario, nombre, apellido, pronombres, fecha_nacimiento, biografia })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.href = '../ProfileInfo/index.html';
            } else {
                alert(data.message || 'Error al guardar perfil');
            }
        })
        .catch(() => alert('Error de conexión con el servidor'));
    });
});