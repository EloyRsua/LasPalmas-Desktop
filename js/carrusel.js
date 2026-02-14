/**
 * Clase Carrusel: Gestiona la galería de imágenes de la provincia
 * Eloy Rubio Suárez - UO298184
 */
class Carrusel {
    constructor() {
        // Mínimo 5 imágenes locales incluyendo el mapa [cite: 497, 498]
        this.imagenes = [
            { src: "multimedia/mapa_las_palmas.jpg", alt: "Mapa de situación de la provincia" },
            { src: "multimedia/vegueta.jpg", alt: "Casco histórico de Vegueta en Las Palmas de Gran Canaria" },
            { src: "multimedia/canteras.jpg", alt: "Playa de Las Canteras" },
            { src: "multimedia/roque_nublo.jpg", alt: "Monumento Natural del Roque Nublo" },
            { src: "multimedia/dunas.jpg", alt: "Reserva Natural de las Dunas de Maspalomas" }
        ];
        this.indice = 0;
        this.init();
    }

    init() {
        const botones = $("main section:first-of-type button");
        
        $(botones[0]).on("click", () => this.retroceder());
        $(botones[1]).on("click", () => this.avanzar());
    }

    actualizar() {
        const img = $("main figure img");
        const cap = $("main figure figcaption");
        
        img.attr("src", this.imagenes[this.indice].src);
        img.attr("alt", this.imagenes[this.indice].alt);
        cap.text(this.imagenes[this.indice].alt);
    }

    avanzar() {
        this.indice = (this.indice + 1) % this.imagenes.length;
        this.actualizar();
    }

    retroceder() {
        this.indice = (this.indice - 1 + this.imagenes.length) % this.imagenes.length;
        this.actualizar();
    }
}

$(document).ready(() => {
    new Carrusel();
});