if (!localStorage.getItem('usuarioLogueado')) {
    alert("Inicia sesión para acceder a contenido exclusivo");
    window.location.href = '../Login/index.html';
}

function mostrarComentarios() {
    fetch('http://localhost:8080/api/blog')
        .then(res => res.json())
        .then(data => {
            const contenedor = document.getElementById('blogComentarios');
            contenedor.innerHTML = '';
            if (data.length === 0) {
                contenedor.innerHTML = '<p>No hay comentarios aún. ¡Sé el primero!</p>';
                return;
            }
            data.forEach(c => {
                const nombre = c.nombre && c.apellido
                    ? `${c.nombre} ${c.apellido}`
                    : c.correo;
                const fecha = new Date(c.fecha_publicacion).toLocaleString();
                contenedor.innerHTML += `
                    <div class="comentario-blog">
                        <strong>${nombre}</strong> <span class="fecha">${fecha}</span>
                        <p>${c.comentario}</p>
                    </div>
                `;
            });
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
            alert('Debes iniciar sesión');
            window.location.href = '../Login/index.html';
            return;
        }
        if (!comentario) {
            alert('El comentario no puede estar vacío');
            return;
        }
        fetch('http://localhost:8080/api/blog', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_usuario, comentario })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('comentario').value = '';
                mostrarComentarios();
            } else {
                alert(data.message || 'Error al enviar comentario');
            }
        })
        .catch(() => alert('Error de conexión con el servidor'));
    });
});