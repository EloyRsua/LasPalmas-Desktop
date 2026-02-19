/**
 * Clase Carrusel: Gestiona la galería de imágenes adaptable y accesible
 * Eloy Rubio Suárez - UO298184
 */
class Carrusel {
    constructor() {
        // Definimos las rutas para cada tamaño de pantalla para cumplir con la adaptabilidad [cite: 95]
        this.imagenes = [
            {
                base: "multimedia/mapa_las_palmas.jpg",
                tablet: "multimedia/mapa_las_palmas_tablet.jpg",
                movil: "multimedia/mapa_las_palmas_movil.jpg",
                alt: "Mapa de situación de la provincia de Las Palmas"
            },
            {
                base: "multimedia/vegueta.jpg",
                tablet: "multimedia/vegueta_tablet.jpg",
                movil: "multimedia/vegueta_movil.jpg",
                alt: "Casco histórico de Vegueta en Las Palmas de Gran Canaria"
            },
            {
                base: "multimedia/canteras.jpg",
                tablet: "multimedia/canteras_tablet.jpg",
                movil: "multimedia/canteras_movil.jpg",
                alt: "Playa de Las Canteras en Las Palmas de Gran Canaria"
            },
            {
                base: "multimedia/dunas.jpg",
                tablet: "multimedia/dunas_tablet.jpg",
                movil: "multimedia/dunas_movil.jpg",
                alt: "Dunas de Maspalomas en Gran Canaria"
            },
            {
                base: "multimedia/roque_nublo.jpg",
                tablet: "multimedia/roque_nublo_tablet.jpg",
                movil: "multimedia/roque_nublo_movil.jpg",
                alt: "Roque Nublo, monumento natural de Gran Canaria"
            }
        ];
        this.indice = 0;
        this.init();
    }

    init() {
        const botones = $("main figure button");
        $(botones[0]).on("click", () => this.retroceder());
        $(botones[1]).on("click", () => this.avanzar());
    }

    actualizar() {
        const item = this.imagenes[this.indice];
        const picture = $("main figure picture");
        
        // Actualizamos las fuentes adaptables para móviles y tablets 
        picture.find("source:nth-of-type(1)").attr("srcset", item.movil);
        picture.find("source:nth-of-type(2)").attr("srcset", item.tablet);
        
        // Actualizamos la imagen base y el texto alternativo para accesibilidad 
        const img = picture.find("img");
        img.attr("src", item.base);
        img.attr("alt", item.alt);
        
        $("main figure figcaption").text(item.alt);
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