// Si el usuario NO ha iniciado sesión, redirige al login
if (!localStorage.getItem('usuarioLogueado')) {
    window.location.href = '../Login/index.html';
}

let estadosChart;

function bloquearEstado(nombre_estado) {
    const select = document.getElementById('estado');
    const btn = document.getElementById('enviarEstadoBtn');
    if (nombre_estado) {
        select.value = nombre_estado;
        select.disabled = true;6
        btn.textContent = 'Enviado';
        btn.disabled = true;
    } else {
        select.disabled = false;
        btn.textContent = 'Enviar';
        btn.disabled = false;
    }
}

function cargarGraficaEstados() {
    $.get('http://localhost:8080/api/estados/top', function(data) {
        const labels = data.map(e => e.nombre_estado);
        const values = data.map(e => parseInt(e.total));

        if (estadosChart) {
            estadosChart.data.labels = labels;
            estadosChart.data.datasets[0].data = values;
            estadosChart.update();
        } else {
            const colorHueso = getComputedStyle(document.documentElement).getPropertyValue('--hueso').trim() || '#f8f8f0';

            const ctx = document.getElementById('estadosChart').getContext('2d');
            estadosChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: [
                            '#4CAF50', '#FFC107', '#2196F3', '#FF5722', '#9C27B0'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: 
                        {
                            labels: {
                                color: colorHueso
                            }
                        }
                    },
                    // Para los labels de tooltips:
                    plugins: {
                        legend: {
                            position:'right',
                            labels: {
                                color: colorHueso
                            }
                        },
                        tooltip: {
                            bodyColor: colorHueso,
                            titleColor: colorHueso
                        }
                    }
                }
            });
        }
    }, 'json');
}

function mostrarResultadoQuiz(resultado) {
    const quizSection = document.querySelector('.quiz');
    quizSection.innerHTML = `<h3>Resultado del Quiz</h3><div class="quiz-result">${resultado}</div>`;
}

function calcularResultado(respuestas) {
    const totalSi = respuestas.filter(Boolean).length;
    if (totalSi >= 6) {
        return `<h2 style="font-family:var(--especial);margin-bottom:0.5em;">Líder de la Ecoaldea</h2>
                Podrías dirigir comunidades sostenibles sin Wi-Fi, le sabes al reciclaje y siempre <br>
                tienes una planta en la mano, cargas termo y bolsita reusable aesthetic, gracias a ti <br>
                el mundo es un lugar mejor.`;
    } else if (totalSi > 4) {
        return `<h2 style="font-family:var(--especial);margin-bottom:0.5em;">Sobreviviente con estilo</h2>
                Te adaptarías bien y sí la armas, te encanta presumir que eres ecofriedly pero tu y yo <br>
                sabemos que todavia te falta, pero vas en buen camino. Te gustan las plantas y los animales <br>
                serías un sobreviviente muy solidario y good vibes.`;
    } else {
        return `<h2 style="font-family:var(--especial);margin-bottom:0.5em;">Climáticamente KO</h2>
                Ya ni le muevas hijo, cuando no le sabes, no le sabes <br>
                Deberias de empezar a tomar acción si no quieres estar a 40 grados en diciembre <br>
                y no, no es normal. Te falta mucho para ser un ecoaldeano, pero no te preocupes <br>
                siempre puedes empezar a aprender, no es tarde.`;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const id_usuario = localStorage.getItem('id_usuario');

    // Estado: bloquear si ya contestó
    if (id_usuario) {
        $.get(`http://localhost:8080/api/estado/${id_usuario}`, function(data) {
            if (data.exists) {
                bloquearEstado(data.nombre_estado);
            }
        }, 'json');
    }

    // Estado: enviar y actualizar gráfica
    const btnEstado = document.getElementById('enviarEstadoBtn');
    if (btnEstado) {
        btnEstado.addEventListener('click', function() {
            const estado = document.getElementById('estado').value;
            if (!estado) {
                mostrarMensaje('Selecciona tu estado');
                return;
            }
            if (!id_usuario) {
                mostrarMensaje('Debes iniciar sesión');
                window.location.href = '../Login/index.html';
                return;
            }

            $.ajax({
                url: 'http://localhost:8080/api/estado',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ id_usuario, nombre_estado: estado }),
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        bloquearEstado(estado);
                        setTimeout(cargarGraficaEstados, 500);
                    } else {
                        mostrarMensaje(data.message || 'Error al guardar estado');
                    }
                },
                error: function() {
                    mostrarMensaje('Error de conexión con el servidor');
                }
            });
        });
    }

    // Gráfica de estados
    cargarGraficaEstados();

    // Quiz: mostrar resultado si ya contestó
    if (id_usuario) {
        $.get(`http://localhost:8080/api/quiz/${id_usuario}`, function(data) {
            if (data.exists) {
                const respuestas = [
                    !!data.respuestas.pregunta_1,
                    !!data.respuestas.pregunta_2,
                    !!data.respuestas.pregunta_3,
                    !!data.respuestas.pregunta_4,
                    !!data.respuestas.pregunta_5,
                    !!data.respuestas.pregunta_6,
                    !!data.respuestas.pregunta_7,
                    !!data.respuestas.pregunta_8
                ];
                const resultado = calcularResultado(respuestas);
                mostrarResultadoQuiz(resultado);
            }
        }, 'json');
    }

    // Quiz: enviar respuestas
    const btnQuiz = document.querySelector('.quiz-submit button');
    if (btnQuiz) {
        btnQuiz.addEventListener('click', function() {
            if (!id_usuario) {
                mostrarMensaje('Debes iniciar sesión');
                window.location.href = '../Login/index.html';
                return;
            }
            const respuestas = [];
            for (let i = 1; i <= 8; i++) {
                const radios = document.getElementsByName('q' + i);
                let valor = null;
                for (const radio of radios) {
                    if (radio.checked) valor = radio.nextSibling.textContent.trim() === "Sí" ? 1 : 0;
                }
                if (valor === null) {
                    mostrarMensaje('Responde todas las preguntas');
                    return;
                }
                respuestas.push(radios[0].checked ? 1 : 0);
            }

            $.ajax({
                url: 'http://localhost:8080/api/quiz',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ id_usuario, respuestas }),
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        const resultado = calcularResultado(respuestas.map(v => !!v));
                        mostrarResultadoQuiz(resultado);
                    } else {
                        mostrarMensaje(data.message || 'Error al guardar respuestas');
                    }
                },
                error: function() {
                    mostrarMensaje('Error de conexión con el servidor');
                }
            });
        });
    }
});