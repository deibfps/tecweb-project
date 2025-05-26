function mostrarMensaje(texto, tipo = 'error') {
    const msg = document.getElementById('mensajeFlotante');
    if (!msg) return;

    msg.textContent = texto;
    msg.classList.remove('oculto', 'ok');
    if (tipo === 'ok') msg.classList.add('ok');

    setTimeout(() => {
        msg.classList.add('oculto');
    }, 3000);
}

/*Función de mensajes de alerta para validaciones*/