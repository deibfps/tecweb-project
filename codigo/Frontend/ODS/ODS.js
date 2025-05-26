if (!localStorage.getItem('usuarioLogueado')) {
    alert("Inicia sesión para acceder a contenido exclusivo");
    window.location.href = '../Login/index.html';
}

function mostrarComentarios() {
    $.get('http://localhost:8080/api/blog', function(data) {
        const contenedor = document.getElementById('blogComentarios');
        contenedor.innerHTML = '';
        const esAdmin = localStorage.getItem('rol') === 'admin';
        if (data.length === 0) {
            contenedor.innerHTML = '<p>No hay comentarios aún. ¡Sé el primero!</p>';
            return;
        }
        data.forEach((c, idx) => {
            const nombre = c.nombre && c.apellido
                ? `${c.nombre} ${c.apellido}`
                : c.correo;
            const fecha = new Date(c.fecha_publicacion).toLocaleString();
            contenedor.innerHTML += `
                <div class="comentario-blog" data-id="${c.id_blog || idx}">
                    <strong>${nombre}</strong> <span class="fecha">${fecha}</span>
                    <p>${c.comentario}</p>
                    ${esAdmin ? `<button class="btn-eliminar-comentario" data-id="${c.id_blog}">Eliminar</button>` : ''}
                </div>
            `;
        });

        // Asigna eventos a los botones de eliminar (solo si es admin)
        if (esAdmin) {
            document.querySelectorAll('.btn-eliminar-comentario').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id_blog = this.getAttribute('data-id');
                    if (confirm('¿Seguro que deseas eliminar este comentario?')) {
                        eliminarComentario(id_blog);
                    }
                });
            });
        }
    }, 'json');
}

// Función para eliminar comentario
function eliminarComentario(id_blog) {
    $.ajax({
        url: `http://localhost:8080/api/blog/${id_blog}`,
        method: 'DELETE',
        success: function(data) {
            if (data.success) {
                mostrarComentarios();
            } else {
                mostrarMensaje('No se pudo eliminar el comentario');
            }
        },
        error: function() {
            mostrarMensaje('Error de conexión con el servidor');
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    mostrarComentarios();

    // Actualiza en tiempo real cada 10 segundos
    setInterval(mostrarComentarios, 10000);

    document.getElementById('blogForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const comentario = document.getElementById('comentario').value.trim();
        const id_usuario = localStorage.getItem('id_usuario');
        if (!id_usuario) {
            mostrarMensaje('Debes iniciar sesión');
            window.location.href = '../Login/index.html';
            return;
        }
        if (!comentario) {
            mostrarMensaje('El comentario no puede estar vacío');
            return;
        }
        $.ajax({
            url: 'http://localhost:8080/api/blog',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ id_usuario, comentario }),
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    document.getElementById('comentario').value = '';
                    mostrarComentarios();
                } else {
                    mostrarMensaje(data.message || 'Error al enviar comentario');
                }
            },
            error: function() {
                mostrarMensaje('Error de conexión con el servidor');
            }
        });
    });
});