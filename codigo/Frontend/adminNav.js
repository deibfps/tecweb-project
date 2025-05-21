$(document).ready(function() {
    if (localStorage.getItem('rol') === 'admin') {
        const navList = document.getElementById('nav-list');
        if (navList && !document.getElementById('admin-panel-link')) {
            const li = document.createElement('li');
            li.id = 'admin-panel-link';
            li.innerHTML = '<a href="../PanelAdmin/index.html">Panel de administrador</a>';
            navList.appendChild(li);
        }
    }
});