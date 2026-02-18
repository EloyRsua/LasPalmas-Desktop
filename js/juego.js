/**
 * Clase Juego: Gestiona la lógica de un cuestionario interactivo de 10 preguntas.
 * Desarrollado en ECMAScript puro siguiendo el paradigma OO.
 * Autor: Eloy Rubio Suárez
 */
class Juego {
    constructor() {
        this.preguntas = [
            {
                enunciado: "¿Qué ingrediente es la base del Gofio según el glosario de Gastronomía?",
                opciones: ["Pescado salado", "Papas arrugadas", "Cereales tostados", "Mojo picón", "Miel de palma"],
                correcta: 2
            },
            {
                enunciado: "¿Cuál es el color característico del plato típico 'Sancocho Canario'?",
                opciones: ["Verde intenso", "Rojo picante", "Blanco o amarillento", "Negro", "Azul"],
                correcta: 2
            },
            {
                enunciado: "¿Cuántas fotos de paisajes y lugares puedes ver en la galería de la página de Inicio?",
                opciones: ["Solo una foto", "Al menos tres", "Un mínimo de cinco", "Exactamente diez", "Ninguna"],
                correcta: 2
            },
            {
                enunciado: "¿Qué tipo de mojo se elabora a base de pimienta palmera?",
                opciones: ["Mojo Verde", "Mojo Rojo (Picón)", "Alioli", "Mojo de Cilantro", "Mojo de Perejil"],
                correcta: 1
            },
            {
                enunciado: "¿En qué sección del menú se puede consultar la previsión del tiempo para 7 días?",
                opciones: ["Gastronomía", "Rutas", "Inicio", "Ayuda", "Meteorología"],
                correcta: 4
            },
            {
                enunciado: "¿Cuál de estos productos cuenta con D.O.P. en la zona de Guía?",
                opciones: ["El Gofio", "El Queso de Flor", "El Bienmesabe", "El Mojo Rojo", "Las Papas"],
                correcta: 1
            },
            {
                enunciado: "En la sección de Rutas, ¿qué tipo de gráfico se usa para ver si el camino tiene muchas subidas o bajadas?",
                opciones: ["Un vídeo musical", "Una foto fija", "Un gráfico de líneas (SVG)", "Una grabación de voz", "Un documento de texto"],
                correcta: 2
            },
            {
                enunciado: "Si desea contratar una actividad en la sección de Reservas, ¿qué le pide el sistema para poder confirmar?",
                opciones: ["No pide nada", "Pide ser ingeniero", "Pide estar registrado como usuario", "Solo pide mirar el reloj", "Pide jugar al test"],
                correcta: 2
            },
            {
                enunciado: "¿Qué nombre aparece en el pie de página como el responsable de la creación de este sitio web?",
                opciones: ["El Administrador", "Eloy Rubio Suárez", "Central de Reservas", "Google", "Atención al Cliente"],
                correcta: 1
            },
            {
                enunciado: "¿Qué apartado del menú contiene la guía para ayudar al usuario a manejar el sitio web?",
                opciones: ["Inicio", "Rutas", "Reservas", "Meteorología", "Ayuda"],
                correcta: 4
            }
        ];

        this.aciertos = 0;
        // Referencias a elementos del DOM 
        this.formulario = document.querySelector('main form');
        this.seccionResultado = document.querySelector('main section section p');
        
        this.inicializar();
    }

    /**
     * Construye dinámicamente el cuestionario en el documento HTML.
     */
    inicializar() {
        this.preguntas.forEach((pregunta, i) => {
            const article = document.createElement('article');
            const h3 = document.createElement('h3');
            h3.textContent = `Pregunta ${i + 1}: ${pregunta.enunciado}`;
            article.appendChild(h3);

            pregunta.opciones.forEach((opcion, j) => {
                const parrafo = document.createElement('p');
                const label = document.createElement('label');
                const input = document.createElement('input');
                
                input.type = 'radio';
                input.name = `p${i}`;
                input.value = j;
                // Es obligatorio que el jugador responda todas las preguntas 
                input.required = true; 

                label.appendChild(input);
                label.appendChild(document.createTextNode(` ${opcion}`));
                parrafo.appendChild(label);
                article.appendChild(parrafo);
            });
            this.formulario.appendChild(article);
        });

        // Botón de finalización del juego
        const boton = document.createElement('button');
        boton.type = 'submit';
        boton.textContent = 'Finalizar Juego';
        this.formulario.appendChild(boton);

        // Gestión del evento de envío [cite: 175]
        this.formulario.onsubmit = (e) => {
            e.preventDefault();
            this.calcularPuntuacion();
        };
    }

    /**
     * Calcula la puntuación final (1 punto por acierto)[cite: 178].
     */
    calcularPuntuacion() {
        this.aciertos = 0;
        this.preguntas.forEach((pregunta, i) => {
            const radioMarcado = this.formulario.querySelector(`input[name="p${i}"]:checked`);
            if (radioMarcado && parseInt(radioMarcado.value) === pregunta.correcta) {
                this.aciertos++;
            }
        });

        this.mostrarResultado();
    }

    /**
     * Muestra la puntuación final de 0 a 10 al jugador en el documento.
     */
    mostrarResultado() {
        this.seccionResultado.textContent = `Puntuación final: ${this.aciertos} / 10`;
    }
}

// Iniciar la lógica cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    new Juego();
});