#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
xml2kml.py
Genera archivos KML de planimetría a partir de rutas.xml
Autor: Eloy Rubio Suárez
"""

import xml.etree.ElementTree as ET
import os

def xml_to_kml(xml_file):
    base_dir = os.path.dirname(os.path.abspath(xml_file))
    
    # Parsear el archivo XML
    tree = ET.parse(xml_file)
    root = tree.getroot()
    
    for ruta in root.findall('ruta'):
        nombre = ruta.find('nombre').text.strip()
        planimetria_filename = ruta.find('planimetria').text.strip()
        
        coordinates_list = []
        hitos_placemarks = []
        
        hitos = ruta.find('hitos')
        for child in list(hitos):
            if child.tag in ('hito', 'punto'):
                is_hito = child.tag == 'hito'
                coords = child.find('coordenadas')
                lon = coords.find('longitud').text.strip()
                lat = coords.find('latitud').text.strip()
                alt = coords.find('altitud').text.strip()
                
                coordinates_list.append(f"{lon},{lat},{alt}")
                
                if is_hito:
                    hito_nombre = child.find('nombre').text.strip()
                    hito_desc = child.find('descripcion').text.strip()
                    
                    # Crear un Placemark para cada hito
                    placemark = f"""    <Placemark>
      <name>{hito_nombre}</name>
      <description>{hito_desc} (Altitud: {alt}m)</description>
      <Point>
        <coordinates>{lon},{lat},{alt}</coordinates>
      </Point>
    </Placemark>"""
                    hitos_placemarks.append(placemark)
            
        coordinates_str = "\n          ".join(coordinates_list)
        hitos_str = "\n".join(hitos_placemarks)
        
        kml_content = f"""<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Document>
    <name>{nombre}</name>
    <description>Planimetría generada automáticamente a partir de rutas.xml</description>
    <Style id="ruta_linea">
      <LineStyle>
        <color>ff0000ff</color> <!-- Rojo opaco -->
        <width>4</width>
      </LineStyle>
    </Style>
    <Placemark>
      <name>Línea de Trayecto</name>
      <styleUrl>#ruta_linea</styleUrl>
      <LineString>
        <tessellate>1</tessellate>
        <coordinates>
          {coordinates_str}
        </coordinates>
      </LineString>
    </Placemark>
{hitos_str}
  </Document>
</kml>
"""
        output_path = os.path.join(base_dir, planimetria_filename)
        with open(output_path, 'w', encoding='utf-8') as f:
            f.write(kml_content)
        print(f"KML generado exitosamente en: {output_path}")

if __name__ == '__main__':
    # Buscar rutas.xml en la misma carpeta que el script
    script_dir = os.path.dirname(os.path.abspath(__file__))
    xml_path = os.path.join(script_dir, 'rutas.xml')
    if os.path.exists(xml_path):
        xml_to_kml(xml_path)
    else:
        xml_to_kml('rutas.xml')
