if (!localStorage.getItem('usuarioLogueado')) {
    alert("Inicia sesión para acceder a contenido exclusivo");
    window.location.href = '../Login/index.html';
}