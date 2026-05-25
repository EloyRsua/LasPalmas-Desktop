/**
 * Clases para la carga de archivos locales (XML, SVG, KML)
 * y su visualización en la página de rutas de forma dinámica.
 * 
 * Cumple con el paradigma de Orientación a Objetos en ECMAScript
 * y evita el uso de ids, clases o selectores de id/clase,
 * a excepción de los indicados en el fragmento de código HTML.
 * 
 * Permite la carga completamente independiente de los tres tipos de archivos.
 * 
 * Autor: Eloy Rubio Suárez
 */

class CargadorRuta {
  constructor() {}

  /**
   * Lee el archivo XML de rutas subido por el usuario usando la API FileReader.
   * Parsea la información completa de cada ruta y la inyecta de forma independiente.
   * @param {HTMLInputElement} input - Elemento input de tipo file.
   */
  leerArchivoXML(input) {
    console.log("leerArchivoXML: Archivo seleccionado:", input.files ? input.files[0] : null);
    const file = input.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = (e) => {
        try {
          const xmlText = e.target.result;
          console.log("leerArchivoXML: Archivo leído correctamente");
          const xml = $.parseXML(xmlText);
          console.log("leerArchivoXML: XML parseado correctamente");
          this.renderRutas(xml);
        } catch (err) {
          console.error("Error al procesar el archivo XML:", err);
          alert("Error al procesar el archivo XML. Revisa la consola del navegador para más detalles.");
        }
      };
      reader.onerror = (err) => {
        console.error("Error al leer el archivo XML:", err);
      };
      reader.readAsText(file);
    }
  }

  /**
   * Procesa las rutas y las inyecta en el main de la página en una sección independiente.
   * @param {XMLDocument} xml - Documento XML parseado.
   */
  renderRutas(xml) {
    // Buscamos o creamos la sección independiente para la información de rutas
    let displaySection = $("main section").filter(function() {
      return $(this).children("h2").text() === "Información de las rutas cargadas";
    });
    if (displaySection.length === 0) {
      $("main").append("<section><h2>Información de las rutas cargadas</h2></section>");
      displaySection = $("main section").last();
    } else {
      // Limpiamos contenido previo si se vuelve a subir
      displaySection.html("<h2>Información de las rutas cargadas</h2>");
    }

    $(xml).find("ruta").each((index, ruta) => {
      const nombre = $(ruta).children("nombre").text().trim();
      const tipo = $(ruta).children("tipo").text().trim();
      const medio = $(ruta).children("medio").text().trim();
      const fecha = $(ruta).children("fecha_inicio").text().trim();
      const hora = $(ruta).children("hora_inicio").text().trim();
      const duracion = $(ruta).children("duracion").text().trim();
      const agencia = $(ruta).children("agencia").text().trim();
      const descripcion = $(ruta).children("descripcion").text().trim();
      const personas = $(ruta).children("personas_adecuadas").text().trim();
      const lugarInicio = $(ruta).children("lugar_inicio").text().trim();
      const direccionInicio = $(ruta).children("direccion_inicio").text().trim();
      const lonInicio = $(ruta).children("coordenadas_inicio").find("longitud").text().trim();
      const latInicio = $(ruta).children("coordenadas_inicio").find("latitud").text().trim();
      const altInicio = $(ruta).children("coordenadas_inicio").find("altitud").text().trim();
      const recomendacion = $(ruta).children("recomendacion").text().trim();

      // Procesar referencias
      let referenciasHtml = "<ul>";
      $(ruta).children("referencias").find("referencia").each(function() {
        const ref = $(this).text().trim();
        referenciasHtml += `<li><a href="${ref}" target="_blank">${ref}</a></li>`;
      });
      referenciasHtml += "</ul>";

      // Obtener la imagen de la ruta a partir del primer hito
      const fotoRuta = $(ruta).children("hitos").children("hito").first().children("fotos").children("foto").first().text().trim();
      const fotoRutaHtml = fotoRuta ? `<img src="${fotoRuta}" alt="Imagen de la ruta ${nombre}" />` : "";

      // Procesar hitos
      let hitosHtml = "<ol>";
      $(ruta).children("hitos").children("hito").each(function() {
        const hitoNombre = $(this).children("nombre").text().trim();
        const hitoDesc = $(this).children("descripcion").text().trim();
        const hitoLon = $(this).children("coordenadas").children("longitud").text().trim();
        const hitoLat = $(this).children("coordenadas").children("latitud").text().trim();
        const hitoAlt = $(this).children("coordenadas").children("altitud").text().trim();
        const hitoDist = $(this).children("distancia").text().trim();
        const hitoDistUnidad = $(this).children("distancia").attr("unidades") || "metros";

        let hitoFotosHtml = "";
        $(this).children("fotos").children("foto").each(function() {
          const fotoPath = $(this).text().trim();
          hitoFotosHtml += `<img src="${fotoPath}" alt="Imagen del hito ${hitoNombre}" />`;
        });

        hitosHtml += `
          <li>
            <h4>${hitoNombre}</h4>
            <p>${hitoDesc}</p>
            <p>Coordenadas: Lat: ${hitoLat}, Lon: ${hitoLon}, Alt: ${hitoAlt}m</p>
            <p>Distancia desde el punto anterior: ${hitoDist} ${hitoDistUnidad}</p>
            ${hitoFotosHtml}
          </li>
        `;
      });
      hitosHtml += "</ol>";

      // Estructura de artículo para cada ruta
      const rutaHtml = `
        <article>
          <h3>Ruta: ${nombre}</h3>
          ${fotoRutaHtml}
          <ul>
            <li>Datos generales de la ruta:
              <ul>
                <li>Tipo: ${tipo}</li>
                <li>Medio de transporte: ${medio}</li>
                <li>Duración: ${duracion}</li>
                <li>Recomendación: ${recomendacion}/10</li>
              </ul>
            </li>
            <li>Planificación y punto de inicio:
              <ul>
                ${fecha ? `<li>Fecha de inicio: ${fecha}</li>` : ''}
                ${hora ? `<li>Hora de inicio: ${hora}</li>` : ''}
                <li>Lugar de inicio: ${lugarInicio} (${direccionInicio})</li>
                <li>Coordenadas de inicio:
                  <ul>
                    <li>Latitud: ${latInicio}</li>
                    <li>Longitud: ${lonInicio}</li>
                    <li>Altitud: ${altInicio}m</li>
                  </ul>
                </li>
              </ul>
            </li>
            <li>Información adicional:
              <ul>
                <li>Agencia gestora: ${agencia}</li>
                <li>Descripción: ${descripcion}</li>
                <li>Adecuada para: ${personas}</li>
              </ul>
            </li>
          </ul>
          
          <h4>Referencias y enlaces externos</h4>
          ${referenciasHtml}
          
          <h4>Hitos del recorrido</h4>
          ${hitosHtml}
        </article>
      `;
      displaySection.append(rutaHtml);
    });
  }
}

class CargadorSVG {
  constructor() {}

  /**
   * Lee los archivos SVG subidos por el usuario y los renderiza de forma independiente
   * en una nueva sección dedicada del DOM.
   * @param {HTMLInputElement} input - Elemento input de tipo file con soporte múltiple.
   */
  leerArchivoSVG(input) {
    console.log("leerArchivoSVG: Archivos seleccionados:", input.files);
    const files = input.files;
    if (files.length > 0) {
      // Buscar o crear la sección independiente para perfiles de altimetría
      let svgSection = $("main section").filter(function() {
        return $(this).children("h2").text() === "Perfiles de altimetría cargados";
      });
      if (svgSection.length === 0) {
        $("main").append("<section><h2>Perfiles de altimetría cargados</h2></section>");
        svgSection = $("main section").last();
      } else {
        svgSection.html("<h2>Perfiles de altimetría cargados</h2>");
      }

      for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const reader = new FileReader();
        reader.onload = (e) => {
          try {
            const svgContent = e.target.result;
            console.log(`leerArchivoSVG: Renderizando altimetría para ${file.name}`);
            
            // Extraer el título del propio archivo SVG
            const svgDoc = $.parseXML(svgContent);
            const routeTitle = $(svgDoc).find("text.titulo").text().trim() || $(svgDoc).find("text").first().text().trim() || file.name;
            
            const articleHtml = `
              <article>
                <h3>Perfil de altimetría: ${routeTitle}</h3>
                ${svgContent}
              </article>
            `;
            svgSection.append(articleHtml);
          } catch (err) {
            console.error("Error al procesar el SVG:", err);
          }
        };
        reader.readAsText(file);
      }
    }
  }
}

class CargadorKML {
  constructor() {}

  /**
   * Lee los archivos KML subidos por el usuario y los renderiza de forma independiente
   * en una nueva sección dedicada del DOM, cargando los mapas dinámicos mediante Leaflet.
   * @param {HTMLInputElement} input - Elemento input de tipo file con soporte múltiple.
   */
  leerArchivoKML(input) {
    console.log("leerArchivoKML: Archivos seleccionados:", input.files);
    const files = input.files;
    if (files.length > 0) {
      // Buscar o crear la sección independiente para mapas y trazados
      let kmlSection = $("main section").filter(function() {
        return $(this).children("h2").text() === "Mapas y trazados de las rutas cargados";
      });
      if (kmlSection.length === 0) {
        $("main").append("<section><h2>Mapas y trazados de las rutas cargados</h2></section>");
        kmlSection = $("main section").last();
      } else {
        kmlSection.html("<h2>Mapas y trazados de las rutas cargados</h2>");
      }

      for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const reader = new FileReader();
        reader.onload = (e) => {
          try {
            const kmlText = e.target.result;
            console.log(`leerArchivoKML: Renderizando mapa para ${file.name}`);

            // Parsear el KML cargado
            const kmlData = $.parseXML(kmlText);
            
            // Extraer el título del propio archivo KML
            const routeTitle = $(kmlData).find("Document > name").text().trim() || $(kmlData).find("name").first().text().trim() || file.name;

            kmlSection.append(`<h3>Mapa dinámico: ${routeTitle}</h3>`);
            const mapFigure = $(`<figure></figure>`);
            kmlSection.append(mapFigure);
            
            const mapDiv = mapFigure[0];
            if (mapDiv) {
              // Inicializar mapa de Leaflet
              const map = L.map(mapDiv).setView([28.0, -15.5], 10);

              // Recalcular tamaño de Leaflet para asegurar que dibuje la línea y marcadores
              setTimeout(() => {
                map.invalidateSize();
              }, 200);



              // Cargar capa de OpenStreetMap
              L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
              }).addTo(map);

              // 1. Dibujar el trayecto lineal (LineString)
              const coordinatesElements = kmlData.getElementsByTagName("coordinates");
              let coordinatesText = "";
              for (let i = 0; i < coordinatesElements.length; i++) {
                const parent = coordinatesElements[i].parentNode;
                if (parent && (parent.nodeName === "LineString" || parent.localName === "LineString")) {
                  coordinatesText = coordinatesElements[i].textContent.trim();
                  break;
                }
              }

              if (coordinatesText) {
                const coords = coordinatesText.split(/\s+/).map(line => {
                  const parts = line.split(",");
                  return [parseFloat(parts[1]), parseFloat(parts[0])]; // [lat, lon]
                }).filter(c => !isNaN(c[0]) && !isNaN(c[1]));

                if (coords.length > 0) {
                  const polyline = L.polyline(coords, {color: '#004685', weight: 4}).addTo(map);
                  map.fitBounds(polyline.getBounds());
                }
              }

              // 2. Colocar marcadores en los hitos (Placemarks con Point)
              const placemarks = kmlData.getElementsByTagName("Placemark");
              for (let i = 0; i < placemarks.length; i++) {
                const pm = placemarks[i];
                const nameNode = pm.getElementsByTagName("name")[0];
                const name = nameNode ? nameNode.textContent.trim() : "";
                if (/punto\s+de\s+paso/i.test(name)) {
                  continue;
                }

                const descNode = pm.getElementsByTagName("description")[0];
                const desc = descNode ? descNode.textContent.trim() : "";
                
                const pointElements = pm.getElementsByTagName("Point");
                if (pointElements.length > 0) {
                  const coordNode = pointElements[0].getElementsByTagName("coordinates")[0];
                  if (coordNode) {
                    const coords = coordNode.textContent.trim().split(",");
                    const lat = parseFloat(coords[1]);
                    const lon = parseFloat(coords[0]);
                    if (!isNaN(lat) && !isNaN(lon)) {
                      L.marker([lat, lon]).addTo(map).bindPopup(`${name}<br>${desc}`);
                    }
                  }
                }
              }
            }
          } catch (err) {
            console.error("Error al procesar el KML:", err);
          }
        };
        reader.readAsText(file);
      }
    }
  }
}

// Variables globales instanciadas para acceder desde los manejadores onchange del HTML
const cargadorRuta = new CargadorRuta();
const cargadorSVG = new CargadorSVG();
const cargadorKML = new CargadorKML();

window.cargadorRuta = cargadorRuta;
window.cargadorSVG = cargadorSVG;
window.cargadorKML = cargadorKML;

console.log("rutas.js: CargadorRuta, CargadorSVG y CargadorKML instanciados y expuestos en el ámbito global.");
