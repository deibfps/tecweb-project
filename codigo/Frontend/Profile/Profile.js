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
        $.get(`http://localhost:8080/api/perfil/${id_usuario}`, function(data) {
            if (data.exists) {
                window.location.href = '../ProfileInfo/index.html';
            }
        }, 'json').fail(function() {
            mostrarMensaje('Error al verificar perfil');
        });
    } else {
        // Si es edición, autorrellena el formulario
        $.get(`http://localhost:8080/api/perfil/${id_usuario}`, function(data) {
            if (data.exists) {
                document.getElementById('nombre').value = data.perfil.nombre;
                document.getElementById('apellido').value = data.perfil.apellido;
                document.getElementById('pronombres').value = data.perfil.pronombres;
                document.getElementById('fecha-nacimiento').value = data.perfil.fecha_nacimiento;
                document.getElementById('biografia').value = data.perfil.biografia;
            }
        }, 'json');
    }

    document.getElementById('formPerfil').addEventListener('submit', function(e) {
        e.preventDefault();
        const nombre = document.getElementById('nombre').value;
        const apellido = document.getElementById('apellido').value;
        const pronombres = document.getElementById('pronombres').value;
        const fecha_nacimiento = document.getElementById('fecha-nacimiento').value;
        const biografia = document.getElementById('biografia').value;

        //VALIDACIONES

        if (!nombre.trim() || !apellido.trim() || !pronombres.trim() || !fecha_nacimiento.trim()) {
            mostrarMensaje('Por favor, completa todos los campos obligatorios.');
            return;
        }

        if (nombre.length > 50 || apellido.length > 50) {
            mostrarMensaje('Nombre y apellido no deben superar los 50 caracteres.');
            return;
        }

        if (biografia.length > 1000) {
            mostrarMensaje('La biografía no puede tener más de 1000 caracteres.');
            return;
        }

        const fechaActual = new Date();
        const fechaIngresada = new Date(fecha_nacimiento);
        if (fechaIngresada > fechaActual) {
            mostrarMensaje('La fecha de nacimiento no puede ser en el futuro.');
            return;
        }

        $.ajax({
            url: 'http://localhost:8080/api/perfil',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ id_usuario, nombre, apellido, pronombres, fecha_nacimiento, biografia }),
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    window.location.href = '../ProfileInfo/index.html';
                } else {
                    mostrarMensaje(data.message || 'Error al guardar perfil');
                }
            },
            error: function() {
                mostrarMensaje('Error de conexión con el servidor');
            }
        });
    });
});