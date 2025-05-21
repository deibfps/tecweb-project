document.addEventListener('DOMContentLoaded', function() {
    // Solo muestra el enlace si el usuario es admin
    if (localStorage.getItem('rol') === 'admin') {
        const navList = document.getElementById('nav-list');
        // Evita duplicados
        if (!document.getElementById('admin-panel-link')) {
            const li = document.createElement('li');
            li.id = 'admin-panel-link';
            li.innerHTML = '<a href="../PanelAdmin/index.html">Panel de administrador</a>';
            navList.appendChild(li);
        }
    }

    // Mostrar sección solo si es admin
    if (localStorage.getItem('rol') === 'admin') {
        document.getElementById('admin-users').style.display = 'block';

        // Cargar usuarios
        fetch('http://localhost:8080/api/usuarios')
            .then(res => res.json())
            .then(data => {
                const tbody = document.querySelector('#usersTable tbody');
                tbody.innerHTML = '';
                data.forEach(u => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${u.id_usuario}</td>
                        <td>${u.correo}</td>
                        <td>${u.rol}</td>
                        <td><button class="btn-eliminar" data-id="${u.id_usuario}">Eliminar</button></td>
                    `;
                    tbody.appendChild(tr);
                });

                // Asigna el evento a todos los botones de eliminar
                document.querySelectorAll('.btn-eliminar').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const id_usuario = this.getAttribute('data-id');
                        eliminarUsuario(id_usuario);
                    });
                });
            });
    }

    // Dashboard: Total de usuarios
    fetch('http://localhost:8080/api/dashboard/usuarios')
        .then(res => res.json())
        .then(data => {
            document.getElementById('totalUsuarios').textContent = data.total || 0;
        });

    // Dashboard: Total de comentarios
    fetch('http://localhost:8080/api/dashboard/comentarios')
        .then(res => res.json())
        .then(data => {
            document.getElementById('totalComentarios').textContent = data.total || 0;
        });

    // Dashboard: Usuarios por pronombres (pastel)
    fetch('http://localhost:8080/api/dashboard/pronombres')
        .then(res => res.json())
        .then(data => {
            new Chart(document.getElementById('pronombresPie'), {
                type: 'pie',
                data: {
                    labels: data.labels,
                    datasets: [{
                        data: data.values,
                        backgroundColor: ['#8aa35c', '#b2c98f', '#e6eec7', '#f7b267', '#f4845f']
                    }]
                },
                options: { responsive: true }
            });
        });

    // Dashboard: Usuarios vs Administradores (barras)
    fetch('http://localhost:8080/api/dashboard/roles')
        .then(res => res.json())
        .then(data => {
            new Chart(document.getElementById('rolesBar'), {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Cantidad',
                        data: data.values,
                        backgroundColor: ['#8aa35c', '#f4845f']
                    }]
                },
                options: { responsive: true }
            });
        });

    // Dashboard: Actividad del foro últimos 5 días (barras)
    fetch('http://localhost:8080/api/dashboard/foro-actividad')
        .then(res => res.json())
        .then(data => {
            new Chart(document.getElementById('foroActividadBar'), {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Comentarios',
                        data: data.values,
                        backgroundColor: '#b2c98f'
                    }]
                },
                options: { responsive: true }
            });
        });
});

// Define la función en el ámbito global
function eliminarUsuario(id_usuario) {
    if (!confirm('¿Seguro que deseas eliminar este usuario?')) return;
    fetch(`http://localhost:8080/api/usuarios/${id_usuario}`, {
        method: 'DELETE'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('No se pudo eliminar el usuario');
        }
    });
}