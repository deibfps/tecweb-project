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
        select.disabled = true;
        btn.textContent = 'Enviado';
        btn.disabled = true;
    } else {
        select.disabled = false;
        btn.textContent = 'Enviar';
        btn.disabled = false;
    }
}

function cargarGraficaEstados() {
    fetch('http://localhost:8080/api/estados/top')
        .then(res => res.json())
        .then(data => {
            const labels = data.map(e => e.nombre_estado);
            const values = data.map(e => parseInt(e.total));

            if (estadosChart) {
                estadosChart.data.labels = labels;
                estadosChart.data.datasets[0].data = values;
                estadosChart.update();
            } else {
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
                            legend: { position: 'bottom' }
                        }
                    }
                });
            }
        });
}

function mostrarResultadoQuiz(resultado) {
    const quizSection = document.querySelector('.quiz');
    quizSection.innerHTML = `<h3>Resultado del Quiz</h3><div class="quiz-result">${resultado}</div>`;
}

function calcularResultado(respuestas) {
    const totalSi = respuestas.filter(Boolean).length;
    if (totalSi >= 6) {
        return "Líder de la Ecoaldea: Podrías dirigir comunidades sostenibles sin Wi-Fi.";
    } else if (totalSi > 4) {
        return "Sobreviviente con estilo: Te adaptarías bien, puedes seguir aprendiendo.";
    } else {
        return "Climáticamente KO: Necesitas entrenar... o derretirte con el resto.";
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const id_usuario = localStorage.getItem('id_usuario');

    // Estado: bloquear si ya contestó
    if (id_usuario) {
        fetch(`http://localhost:8080/api/estado/${id_usuario}`)
            .then(res => res.json())
            .then(data => {
                if (data.exists) {
                    bloquearEstado(data.nombre_estado);
                }
            });
    }

    // Estado: enviar y actualizar gráfica
    const btnEstado = document.getElementById('enviarEstadoBtn');
    if (btnEstado) {
        btnEstado.addEventListener('click', function() {
            const estado = document.getElementById('estado').value;
            if (!estado) {
                alert('Selecciona tu estado');
                return;
            }
            if (!id_usuario) {
                alert('Debes iniciar sesión');
                window.location.href = '../Login/index.html';
                return;
            }

            fetch('http://localhost:8080/api/estado', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_usuario, nombre_estado: estado })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    bloquearEstado(estado);
                    setTimeout(cargarGraficaEstados, 500);
                } else {
                    alert(data.message || 'Error al guardar estado');
                }
            })
            .catch(() => alert('Error de conexión con el servidor'));
        });
    }

    // Gráfica de estados
    cargarGraficaEstados();

    // Quiz: mostrar resultado si ya contestó
    if (id_usuario) {
        fetch(`http://localhost:8080/api/quiz/${id_usuario}`)
            .then(res => res.json())
            .then(data => {
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
            });
    }

    // Quiz: enviar respuestas
    const btnQuiz = document.querySelector('.quiz-submit button');
    if (btnQuiz) {
        btnQuiz.addEventListener('click', function() {
            if (!id_usuario) {
                alert('Debes iniciar sesión');
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
                    alert('Responde todas las preguntas');
                    return;
                }
                respuestas.push(radios[0].checked ? 1 : 0);
            }

            fetch('http://localhost:8080/api/quiz', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_usuario, respuestas })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const resultado = calcularResultado(respuestas.map(v => !!v));
                    mostrarResultadoQuiz(resultado);
                } else {
                    alert(data.message || 'Error al guardar respuestas');
                }
            })
            .catch(() => alert('Error de conexión con el servidor'));
        });
    }
});