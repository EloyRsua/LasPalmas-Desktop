#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
xml2svg.py
Genera archivos SVG de altimetría a partir de rutas.xml
Autor: Eloy Rubio Suárez
"""

import xml.etree.ElementTree as ET
import os

def xml_to_svg(xml_file):
    base_dir = os.path.dirname(os.path.abspath(xml_file))
    
    # Parsear el archivo XML
    tree = ET.parse(xml_file)
    root = tree.getroot()
    
    for idx, ruta in enumerate(root.findall('ruta'), 1):
        nombre_ruta = ruta.find('nombre').text.strip()
        altimetria_filename = ruta.find('altimetria').text.strip()
        
        # Parsear hitos/puntos y calcular distancias acumuladas
        hitos_data = []
        cum_dist = 0.0
        
        hitos = ruta.find('hitos')
        for child in list(hitos):
            if child.tag in ('hito', 'punto'):
                is_hito = child.tag == 'hito'
                nombre = child.find('nombre').text.strip() if is_hito else ""
                desc = child.find('descripcion').text.strip() if is_hito else ""
                
                coords = child.find('coordenadas')
                alt = float(coords.find('altitud').text.strip())
                
                dist_text = child.find('distancia').text.strip()
                dist = float(dist_text)
                cum_dist += dist
                
                hitos_data.append({
                    'nombre': nombre,
                    'descripcion': desc,
                    'altitud': alt,
                    'distancia_desde_previo': dist,
                    'distancia_acumulada': cum_dist,
                    'is_hito': is_hito
                })
            
        total_dist = cum_dist
        altitudes = [h['altitud'] for h in hitos_data]
        min_alt = min(altitudes)
        max_alt = max(altitudes)
        
        # Ajustar los rangos de la escala vertical
        alt_range = max_alt - min_alt
        if alt_range == 0:
            y_min_val = min_alt - 50
            y_max_val = min_alt + 50
        else:
            y_min_val = max(0, min_alt - alt_range * 0.15)
            y_max_val = max_alt + alt_range * 0.15
            
        # Dimensiones del SVG
        svg_width = 950
        svg_height = 650
        
        # Márgenes del gráfico
        pad_left = 90
        pad_right = 60
        pad_top = 60
        pad_bottom = 250  # Mayor margen inferior para las etiquetas de texto vertical (evita que se corten)
        
        plot_width = svg_width - pad_left - pad_right
        plot_height = svg_height - pad_top - pad_bottom
        
        baseline_y = pad_top + plot_height
        
        # Funciones de escalado
        def get_x(dist_acum):
            if total_dist == 0:
                return pad_left
            return pad_left + (dist_acum / total_dist) * plot_width
            
        def get_y(alt):
            val_range = y_max_val - y_min_val
            if val_range == 0:
                return baseline_y - plot_height / 2
            return baseline_y - ((alt - y_min_val) / val_range) * plot_height

        # Generar puntos para el perfil del terreno
        profile_points = []
        for h in hitos_data:
            px = get_x(h['distancia_acumulada'])
            py = get_y(h['altitud'])
            profile_points.append((px, py))
            
        # Para hacer la polilínea CERRADA, empezamos en la base izquierda, 
        # pasamos por todos los puntos de altitud, bajamos a la base derecha, 
        # y cerramos volviendo a la base izquierda.
        closed_points_str = f"{pad_left},{baseline_y} "
        closed_points_str += " ".join(f"{px:.2f},{py:.2f}" for px, py in profile_points)
        closed_points_str += f" {pad_left + plot_width:.2f},{baseline_y} {pad_left},{baseline_y}"
        
        # Generar elementos SVG de la cuadrícula
        grid_elements = []
        
        # Líneas de cuadrícula horizontal (altitud)
        num_y_ticks = 5
        y_ticks = [y_min_val + i * (y_max_val - y_min_val) / num_y_ticks for i in range(num_y_ticks + 1)]
        for val in y_ticks:
            gy = get_y(val)
            grid_elements.append(f'  <line x1="{pad_left}" y1="{gy:.2f}" x2="{pad_left + plot_width}" y2="{gy:.2f}" stroke="#e0e0e0" stroke-width="1" stroke-dasharray="4,4"/>')
            grid_elements.append(f'  <text x="{pad_left - 10}" y="{gy + 4:.2f}" font-family="sans-serif" font-size="11" text-anchor="end" fill="#555555">{int(val)} m</text>')
            
        # Líneas de cuadrícula vertical (distancia)
        num_x_ticks = 5
        x_ticks = [i * total_dist / num_x_ticks for i in range(num_x_ticks + 1)]
        for val in x_ticks:
            gx = get_x(val)
            grid_elements.append(f'  <line x1="{gx:.2f}" y1="{pad_top}" x2="{gx:.2f}" y2="{baseline_y}" stroke="#e0e0e0" stroke-width="1" stroke-dasharray="4,4"/>')
            grid_elements.append(f'  <text x="{gx:.2f}" y="{baseline_y + 20}" font-family="sans-serif" font-size="11" text-anchor="middle" fill="#555555">{val/1000:.2f} km</text>')

        # Marcadores de hitos y etiquetas
        markers_and_labels = []
        for h in hitos_data:
            px = get_x(h['distancia_acumulada'])
            py = get_y(h['altitud'])
            
            # Dibujar un pequeño círculo en la trayectoria para TODOS los puntos
            markers_and_labels.append(f'  <circle cx="{px:.2f}" cy="{py:.2f}" r="3" fill="#0055ff"/>')
            
            # Si es un hito principal, rotularlo con texto vertical
            # y dibujar una línea guía discontinua hasta la base
            if h['is_hito']:
                # Línea guía vertical
                markers_and_labels.append(f'  <line x1="{px:.2f}" y1="{py:.2f}" x2="{px:.2f}" y2="{baseline_y}" stroke="#ff6600" stroke-width="1" stroke-dasharray="2,2"/>')
                # Círculo especial de hito principal
                markers_and_labels.append(f'  <circle cx="{px:.2f}" cy="{py:.2f}" r="5" fill="#ff6600" stroke="#ffffff" stroke-width="1.5"/>')
                # Texto vertical (rotado -90 grados)
                markers_and_labels.append(f'  <text x="{px:.2f}" y="{baseline_y + 35}" transform="rotate(-90, {px:.2f}, {baseline_y + 35})" font-family="sans-serif" font-size="10" font-weight="bold" fill="#333333" text-anchor="end">{h["nombre"]}</text>')
                # Altitud en texto encima del punto
                markers_and_labels.append(f'  <text x="{px:.2f}" y="{py - 8:.2f}" font-family="sans-serif" font-size="9" fill="#ff6600" text-anchor="middle" font-weight="bold">{int(h["altitud"])}m</text>')

        grid_str = "\n".join(grid_elements)
        markers_str = "\n".join(markers_and_labels)
        
        svg_content = f"""<svg xmlns="http://www.w3.org/2000/svg" width="{svg_width}" height="{svg_height}" viewBox="0 0 {svg_width} {svg_height}">
  <!-- Fondo -->
  <rect width="100%" height="100%" fill="#fafafa"/>

  <!-- Título de la ruta -->
  <text x="{pad_left}" y="35" font-family="sans-serif" font-size="16" font-weight="bold" fill="#111111">{nombre_ruta}</text>

  <!-- Cuadrícula y Escalas -->
{grid_str}

  <!-- Ejes -->
  <line x1="{pad_left}" y1="{pad_top}" x2="{pad_left}" y2="{baseline_y}" stroke="#333333" stroke-width="2"/>
  <line x1="{pad_left}" y1="{baseline_y}" x2="{pad_left + plot_width}" y2="{baseline_y}" stroke="#333333" stroke-width="2"/>

  <!-- Título de Ejes -->
  <text x="{pad_left - 50}" y="45" font-family="sans-serif" font-size="12" font-weight="bold" fill="#333333" transform="rotate(-90, {pad_left - 50}, 45)" text-anchor="middle">Altitud (metros)</text>
  <text x="{pad_left + plot_width / 2}" y="{svg_height - 20}" font-family="sans-serif" font-size="12" font-weight="bold" fill="#333333" text-anchor="middle">Distancia Horizontal (kilómetros)</text>

  <!-- Polilínea Cerrada (Perfil de Altimetría del Terreno) -->
  <polygon points="{closed_points_str}" fill="rgba(0, 85, 255, 0.25)" stroke="#0055ff" stroke-width="3" />

  <!-- Hitos, Marcadores y Etiquetas -->
{markers_str}
</svg>
"""
        output_path = os.path.join(base_dir, altimetria_filename)
        with open(output_path, 'w', encoding='utf-8') as f:
            f.write(svg_content)
        print(f"SVG de altimetría generado en: {output_path}")

if __name__ == '__main__':
    # Buscar rutas.xml en la misma carpeta que el script
    script_dir = os.path.dirname(os.path.abspath(__file__))
    xml_path = os.path.join(script_dir, 'rutas.xml')
    if os.path.exists(xml_path):
        xml_to_svg(xml_path)
    else:
        xml_to_svg('rutas.xml')
