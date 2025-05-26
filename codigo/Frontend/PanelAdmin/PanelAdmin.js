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
        $.get('http://localhost:8080/api/usuarios', function(data) {
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
        }, 'json');
    }

    // Dashboard: Total de usuarios
    $.get('http://localhost:8080/api/dashboard/usuarios', function(data) {
        document.getElementById('totalUsuarios').textContent = data.total || 0;
    }, 'json');

    // Dashboard: Total de comentarios
    $.get('http://localhost:8080/api/dashboard/comentarios', function(data) {
        document.getElementById('totalComentarios').textContent = data.total || 0;
    }, 'json');

    // Dashboard: Usuarios por pronombres (pastel)
    $.get('http://localhost:8080/api/dashboard/pronombres', function(data) {
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
    }, 'json');

    // Dashboard: Usuarios vs Administradores (barras)
    $.get('http://localhost:8080/api/dashboard/roles', function(data) {
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
    }, 'json');

    // Dashboard: Actividad del foro últimos 5 días (barras)
    $.get('http://localhost:8080/api/dashboard/foro-actividad', function(data) {
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
    }, 'json');
});

// Define la función en el ámbito global
function eliminarUsuario(id_usuario) {
    if (!confirm('¿Seguro que deseas eliminar este usuario?')) return;
    $.ajax({
        url: `http://localhost:8080/api/usuarios/${id_usuario}`,
        method: 'DELETE',
        success: function(data) {
            if (data.success) {
                location.reload();
            } else {
                mostrarMensaje('No se pudo eliminar el usuario');
            }
        },
        error: function() {
            mostrarMensaje('Error de conexión con el servidor');
        }
    });
}