/**
 * Clase Meteorologia para consumir servicios web de Open-Meteo
 * y renderizar el clima actual y pronóstico a 7 días de forma simple.
 * 
 * Cumple con el paradigma de Orientación a Objetos en ECMAScript
 * y evita el uso de ids, clases o selectores de id/clase.
 * 
 * Autor: Eloy Rubio Suárez
 */
class Meteorologia {
  constructor() {
    this.lat = 28.1248; // Latitud de Las Palmas de Gran Canaria
    this.lon = -15.4300; // Longitud de Las Palmas de Gran Canaria
    this.apiUrl = `https://api.open-meteo.com/v1/forecast?latitude=${this.lat}&longitude=${this.lon}&current=temperature_2m,relative_humidity_2m,apparent_temperature,is_day,precipitation,rain,weather_code,wind_speed_10m&daily=weather_code,temperature_2m_max,temperature_2m_min,apparent_temperature_max,apparent_temperature_min,sunrise,sunset,precipitation_sum,wind_speed_10m_max&timezone=Atlantic/Canary`;

    // Tabla de interpretación de códigos WMO
    this.weatherCodes = {
      0: { desc: "Cielo despejado", icon: "☀️" },
      1: { desc: "Principalmente despejado", icon: "🌤️" },
      2: { desc: "Parcialmente nublado", icon: "⛅" },
      3: { desc: "Nublado", icon: "☁️" },
      45: { desc: "Niebla", icon: "🌫️" },
      48: { desc: "Niebla de escarcha", icon: "🌫️" },
      51: { desc: "Llovizna ligera", icon: "🌧️" },
      53: { desc: "Llovizna moderada", icon: "🌧️" },
      55: { desc: "Llovizna densa", icon: "🌧️" },
      56: { desc: "Llovizna helada ligera", icon: "🌧️" },
      57: { desc: "Llovizna helada densa", icon: "🌧️" },
      61: { desc: "Lluvia débil", icon: "🌧️" },
      63: { desc: "Lluvia moderada", icon: "🌧️" },
      65: { desc: "Lluvia fuerte", icon: "🌧️" },
      66: { desc: "Lluvia helada ligera", icon: "🌧️" },
      67: { desc: "Lluvia helada fuerte", icon: "🌧️" },
      71: { desc: "Nevada débil", icon: "❄️" },
      73: { desc: "Nevada moderada", icon: "❄️" },
      75: { desc: "Nevada fuerte", icon: "❄️" },
      77: { desc: "Granos de nieve", icon: "❄️" },
      80: { desc: "Lluvia intermitente débil", icon: "🌦️" },
      81: { desc: "Lluvia intermitente moderada", icon: "🌦️" },
      82: { desc: "Lluvia intermitente violenta", icon: "🌦️" },
      85: { desc: "Chubascos de nieve débiles", icon: "❄️" },
      86: { desc: "Chubascos de nieve fuertes", icon: "❄️" },
      95: { desc: "Tormenta eléctrica", icon: "⛈️" },
      96: { desc: "Tormenta con granizo débil", icon: "⛈️" },
      99: { desc: "Tormenta con granizo fuerte", icon: "⛈️" }
    };
  }

  getWeatherInfo(code) {
    return this.weatherCodes[code] || { desc: "Desconocido", icon: "❓" };
  }

  formatDate(dateStr) {
    const date = new Date(dateStr);
    const options = { weekday: 'long', day: 'numeric', month: 'short' };
    let formatted = date.toLocaleDateString('es-ES', options);
    return formatted.charAt(0).toUpperCase() + formatted.slice(1);
  }

  cargarDatos() {
    $.ajax({
      url: this.apiUrl,
      method: "GET",
      dataType: "json",
      success: (data) => {
        this.renderClimaActual(data.current);
        this.renderPronostico(data.daily);
      },
      error: (xhr, status, error) => {
        console.error("Error al obtener los datos de Open-Meteo:", error);
        $("main section:first-of-type").html("<h3>Información en tiempo real</h3><p>No se pudo cargar la información en este momento.</p>");
        $("main section:last-of-type").html("<h3>Previsión para los próximos 7 días</h3><p>No se pudo cargar la previsión en este momento.</p>");
      }
    });
  }

  renderClimaActual(current) {
    const weather = this.getWeatherInfo(current.weather_code);
    const html = `
      <article>
        <h4>Condiciones actuales</h4>
        <p>Estado: ${weather.desc} ${weather.icon}</p>
        <p>Temperatura: ${current.temperature_2m}°C</p>
        <p>Sensación térmica: ${current.apparent_temperature}°C</p>
        <p>Humedad relativa: ${current.relative_humidity_2m}%</p>
        <p>Viento: ${current.wind_speed_10m} km/h</p>
        <p>Precipitación: ${current.precipitation} mm</p>
      </article>
    `;
    $("main section:first-of-type").html("<h3>Información en tiempo real</h3>" + html);
  }

  renderPronostico(daily) {
    let listHtml = "<ul>";
    
    for (let i = 0; i < 7; i++) {
      const weather = this.getWeatherInfo(daily.weather_code[i]);
      const dateText = this.formatDate(daily.time[i]);
      const minTemp = daily.temperature_2m_min[i];
      const maxTemp = daily.temperature_2m_max[i];
      const windSpeed = daily.wind_speed_10m_max[i];
      const precip = daily.precipitation_sum[i];
      
      listHtml += `
        <li>
          <h4>${dateText}</h4>
          <p>Estado: ${weather.desc} ${weather.icon}</p>
          <p>Temperatura máxima: ${maxTemp}°C</p>
          <p>Temperatura mínima: ${minTemp}°C</p>
          <p>Viento máximo: ${windSpeed} km/h</p>
          <p>Precipitación: ${precip} mm</p>
        </li>
      `;
    }
    listHtml += "</ul>";
    
    $("main section:last-of-type").html("<h3>Previsión para los próximos 7 días</h3>" + listHtml);
  }
}

// Inicialización
$(document).ready(() => {
  const app = new Meteorologia();
  app.cargarDatos();
});
