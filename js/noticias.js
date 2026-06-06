/**
 * Clase Noticias: Consume servicios web de TheNewsApi para Las Palmas
 * Eloy Rubio Suárez - UO298184
 */
class Noticias {
    constructor() {
        this.busqueda = "Las Palmas";
        this.url = "https://api.thenewsapi.com/v1/news/all";
        this.apikey = "4quOWBliNnQvnKB7WMHrxqktCJFrQ6mttaVt9y17";
        this.contenedor = $("main > section:last-of-type");
        this.buscar();
    }

    buscar() {
        const urlCompleta = `${this.url}?api_token=${this.apikey}&search=${this.busqueda}&language=es&limit=3`;
        $.ajax({
            url: urlCompleta,
            method: "GET",
            dataType: "json",
            success: (datos) => this.procesarInformacion(datos),
            error: (xhr, status, error) => {
                this.contenedor.find("p").text("Error al cargar las noticias: " + error);
            }
        });
    }

    procesarInformacion(datos) {
        this.contenedor.find("p").remove();
        if (!datos.data || datos.data.length === 0) return;

        datos.data.forEach(noticia => {
            const article = $("<article></article>");
            const titular = $("<h3></h3>").text(noticia.title);
            article.append(titular);

            if (noticia.description) {
                let textoLimpio = noticia.description.trim();
                if (textoLimpio.startsWith("/")) {
                    textoLimpio = textoLimpio.substring(1).trim();
                }
                
                if (textoLimpio !== "") {
                    const entradilla = $("<p></p>").text(textoLimpio);
                    article.append(entradilla);
                }
            }

            if (noticia.url) {
                const enlace = $("<p></p>");
                const link = $("<a></a>")
                    .attr("href", noticia.url)
                    .attr("target", "_blank")
                    .attr("rel", "noopener noreferrer")
                    .text("Leer noticia completa en " + (noticia.source || "la fuente"));
                enlace.append(link);
                article.append(enlace);
            }
            this.contenedor.append(article);
        });
    }
}

$(document).ready(() => { new Noticias(); });