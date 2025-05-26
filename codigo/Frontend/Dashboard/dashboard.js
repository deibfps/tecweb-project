$(document).ready(function () {
    // Verifica si el usuario es administrador y carga la configuración
    $.get('http://localhost:8080/api/dashboard/configuracion', function (configuracion) {
        if (configuracion.error) {
            console.log('Acceso denegado: Solo los administradores pueden modificar la configuración.');
            return;
        }

        // Muestra los controles de administrador
        $('#adminControls').show();

        // Aplica la configuración inicial
        for (const [chartId, visible] of Object.entries(configuracion)) {
            if (!visible) {
                $(`#${chartId}`).closest('.card').hide();
            }
        }

        // Maneja los cambios en los checkboxes
        $('#adminControls input[type="checkbox"]').each(function () {
            const chartId = $(this).attr('id').replace('toggle', '');
            $(this).prop('checked', configuracion[chartId]);
        });

        // Guarda la configuración
        $('#guardarConfiguracion').click(function () {
            const nuevaConfiguracion = {};
            $('#adminControls input[type="checkbox"]').each(function () {
                const chartId = $(this).attr('id').replace('toggle', '');
                nuevaConfiguracion[chartId] = $(this).is(':checked');
            });

            $.post('http://localhost:8080/api/dashboard/configuracion', JSON.stringify(nuevaConfiguracion), function (response) {
                if (response.success) {
                    mostrarMensaje('Configuración guardada correctamente.', 'ok');
                    setTimeout(() => {
                        location.reload();
                    }, 2500);
                } else {
                    mostrarMensaje('Error al guardar la configuración.');
                }
            }, 'json');
        });
    }, 'json');

    function cargarTotalUsuarios() {
        $.get('http://localhost:8080/api/dashboard/usuarios', function (data) {
            $('#totalUsuarios').text(data.total || 0);
        }, 'json');
    }

    function cargarEstadoMasFrecuente() {
        $.get('http://localhost:8080/api/dashboard/estados', function (data) {
            new Chart($('#estadoChart'), {
                type: 'doughnut',
                data: {
                    labels: data.labels,
                    datasets: [{
                        data: data.values,
                        backgroundColor: ['#7eab57', '#b2c98f', '#f7b267', '#f4845f', '#2196f3']
                    }]
                },
                options: {
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }, 'json');
    }

    function cargarPronombresMasUsados() {
        $.get('http://localhost:8080/api/dashboard/pronombres', function (data) {
            new Chart($('#pronombresChart'), {
                type: 'pie',
                data: {
                    labels: data.labels,
                    datasets: [{
                        data: data.values,
                        backgroundColor: ['#8aa35c', '#b2c98f', '#e6eec7', '#f7b267', '#f4845f']
                    }]
                },
                options: {
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }, 'json');
    }

    function cargarUsuariosMasActivos() {
        $.get('http://localhost:8080/api/dashboard/activos', function (data) {
            new Chart($('#activosChart'), {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Comentarios',
                        data: data.values,
                        backgroundColor: '#7eab57'
                    }]
                },
                options: {
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: { title: { display: true, text: 'Usuario' } },
                        y: { title: { display: true, text: 'Comentarios' }, beginAtZero: true }
                    }
                }
            });
        }, 'json');
    }

    function cargarActividadForo() {
        $.get('http://localhost:8080/api/dashboard/foro-actividad', function (data) {
            new Chart($('#foroActividadChart'), {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Comentarios',
                        data: data.values,
                        borderColor: '#7eab57',
                        backgroundColor: 'rgba(126, 171, 87, 0.2)',
                        fill: true
                    }]
                },
                options: {
                    plugins: {
                        legend: { position: 'top' }
                    },
                    scales: {
                        x: { title: { display: true, text: 'Día' } },
                        y: { title: { display: true, text: 'Comentarios' }, beginAtZero: true }
                    }
                }
            });
        }, 'json');
    }

    function cargarRolesUsuarios() {
        $.get('http://localhost:8080/api/dashboard/roles', function (data) {
            new Chart($('#rolesChart'), {
                type: 'pie',
                data: {
                    labels: data.labels,
                    datasets: [{
                        data: data.values,
                        backgroundColor: ['#7eab57', '#f7b267']
                    }]
                },
                options: {
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }, 'json');
    }

    // Crecimiento de la comunidad
    function cargarCrecimientoComunidad() {
        $.get('http://localhost:8080/api/dashboard/crecimiento', function (data) {
            new Chart($('#crecimientoChart'), {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Usuarios',
                        data: data.values,
                        borderColor: '#7eab57',
                        backgroundColor: 'rgba(126, 171, 87, 0.2)',
                        fill: true
                    }]
                },
                options: {
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: { enabled: true }
                    },
                    scales: {
                        x: { title: { display: true, text: 'Mes' } },
                        y: { title: { display: true, text: 'Usuarios' }, beginAtZero: true }
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeInOutBounce'
                    }
                }
            });
        }, 'json');
    }

    // Distribución de edades
    function cargarDistribucionEdades() {
        $.get('http://localhost:8080/api/dashboard/edades', function (data) {
            new Chart($('#edadesChart'), {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Usuarios',
                        data: data.values,
                        backgroundColor: ['#7eab57', '#b2c98f', '#f7b267', '#f4845f']
                    }]
                },
                options: {
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: { enabled: true }
                    },
                    scales: {
                        x: { title: { display: true, text: 'Rango de edad' } },
                        y: { title: { display: true, text: 'Usuarios' }, beginAtZero: true }
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeInOutBounce'
                    }
                }
            });
        }, 'json');
    }

    // Resultados por pregunta del quiz
    function cargarResultadosPorPregunta() {
        $.get('http://localhost:8080/api/dashboard/quiz-preguntas', function (data) {
            new Chart($('#quizPreguntasChart'), {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'Sí',
                            data: data.si,
                            backgroundColor: '#7eab57'
                        },
                        {
                            label: 'No',
                            data: data.no,
                            backgroundColor: '#f4845f'
                        }
                    ]
                },
                options: {
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: { enabled: true }
                    },
                    scales: {
                        x: { title: { display: true, text: 'Pregunta' } },
                        y: { title: { display: true, text: 'Respuestas' }, beginAtZero: true }
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeInOutBounce'
                    }
                }
            });
        }, 'json');
    }


    cargarCrecimientoComunidad();
    cargarDistribucionEdades();
    cargarResultadosPorPregunta();
    cargarTotalUsuarios();
    cargarEstadoMasFrecuente();
    cargarPronombresMasUsados();
    cargarUsuariosMasActivos();
    cargarActividadForo();
    cargarRolesUsuarios();
});