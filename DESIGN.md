---
name: Royal Vault
description: Sitio de cajas fuertes y cajas de seguridad en Yucatán, en el mundo visual de su hoja de marca (azul noche y plata).
colors:
  night: "#020b1d"
  navy-900: "#061430"
  navy-800: "#0b1d3f"
  navy-700: "#142a52"
  navy-600: "#1f3a6b"
  silver-50: "#f2f4f7"
  silver-100: "#e4e8ee"
  silver-200: "#d3d9e2"
  silver-300: "#c3cad5"
  silver-500: "#8a95a6"
  ink: "#0b1d3f"
  ink-2: "#44506a"
  rule-light: "#cfd6e0"
  glint: "#9cc0ff"
  whatsapp: "#25d366"
  whatsapp-hover: "#3ee07a"
typography:
  wordmark:
    fontFamily: "Cinzel, Trajan Pro, Times New Roman, serif"
    fontSize: "1.2rem"
    fontWeight: 600
    lineHeight: 1.1
    letterSpacing: "0.1em"
  display-hero:
    fontFamily: "Barlow Semi Condensed, Arial Narrow, sans-serif"
    fontSize: "clamp(2.4rem, min(5.4vw, 7.6vh), 4.6rem)"
    fontWeight: 700
    lineHeight: 1
    letterSpacing: "0.005em"
  display:
    fontFamily: "Barlow Semi Condensed, Arial Narrow, sans-serif"
    fontSize: "clamp(2rem, 4vw, 3.1rem)"
    fontWeight: 700
    lineHeight: 1.04
    letterSpacing: "0.005em"
  title:
    fontFamily: "Barlow Semi Condensed, Arial Narrow, sans-serif"
    fontSize: "1.6rem"
    fontWeight: 600
    lineHeight: 1.15
    letterSpacing: "0em"
  caption:
    fontFamily: "Jost, Futura, Century Gothic, sans-serif"
    fontSize: "0.8125rem"
    fontWeight: 500
    lineHeight: 1.4
    letterSpacing: "0.32em"
  body:
    fontFamily: "Jost, Futura, Century Gothic, sans-serif"
    fontSize: "1.0625rem"
    fontWeight: 400
    lineHeight: 1.6
    letterSpacing: "0em"
  button:
    fontFamily: "Jost, Futura, Century Gothic, sans-serif"
    fontSize: "0.9375rem"
    fontWeight: 500
    lineHeight: 1.1
    letterSpacing: "0.06em"
rounded:
  sm: "2px"
  md: "4px"
  full: "9999px"
spacing:
  gutter: "clamp(1rem, 4vw, 2.5rem)"
  section: "clamp(4.5rem, 9vw, 7.5rem)"
  stack-sm: "1rem"
  stack-md: "1.5rem"
  wrap: "1200px"
components:
  button-whatsapp:
    backgroundColor: "{colors.whatsapp}"
    textColor: "{colors.night}"
    typography: "{typography.button}"
    rounded: "{rounded.md}"
    height: "3rem"
    padding: "0 1.4rem"
  button-whatsapp-hover:
    backgroundColor: "{colors.whatsapp-hover}"
  button-navy:
    backgroundColor: "{colors.navy-800}"
    textColor: "{colors.silver-50}"
    typography: "{typography.button}"
    rounded: "{rounded.md}"
    height: "3rem"
  button-navy-hover:
    backgroundColor: "{colors.navy-600}"
  button-ghost-on-dark:
    textColor: "{colors.silver-50}"
    typography: "{typography.button}"
    rounded: "{rounded.md}"
    height: "3rem"
  button-outline-on-light:
    textColor: "{colors.navy-800}"
    typography: "{typography.button}"
    rounded: "{rounded.md}"
    height: "3rem"
  tag:
    backgroundColor: "{colors.silver-100}"
    textColor: "{colors.navy-800}"
    rounded: "{rounded.sm}"
    padding: "0.3rem 0.6rem"
  input:
    backgroundColor: "{colors.silver-50}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    height: "3rem"
---

# Design System: Royal Vault

## Overview

La página es la **hoja de marca de Royal Vault puesta en pantalla**: el emblema RV plateado, iluminado sobre azul noche; el nombre en capitales romanas (solo la palabra de marca); titulares en letra de rótulo técnico; leyendas espaciadas entre filetes; y la fila de cuatro pilares (Venta · Reparación · Mantenimiento · Aperturas). La sensación es de resguardo y seriedad: metal pulido, luz baja, nada de promociones estridentes. Fuente de verdad: los dos archivos del logotipo que entregó el cliente.

El sitio público (`app/views/landing.php` + `public/assets/css/site.css`) es una superficie de persuasión: el color trabaja a escala de página. El panel (`/admin`, Tabler) es una superficie operativa: toma solo la paleta y el monograma, con contención.

## Colors

Estrategia **comprometida**: los azules del logotipo ocupan regiones completas (barra superior, cabecera, portada, catálogo, pie) y se alternan con secciones de lectura en plata clara fría.

- **Azul noche `night` / `navy-900`**: fondo de la portada y del pie; la luz radial `navy-600` detrás del emblema imita el foco de la hoja de marca.
- **Marino `navy-800`**: catálogo, botones de acción que no son WhatsApp, texto `ink` sobre claro.
- **Plata `silver-50…500`**: tipografía y filetes sobre oscuro (`silver-300` para texto secundario, 11.9:1 sobre `night`); `silver-50` es el fondo claro (frío, nunca crema).
- **`glint`**: el destello azul del logotipo. Solo para el foco del teclado, el punto de "24/7" y las palomitas del catálogo.
- **`whatsapp`**: exclusivamente en acciones de WhatsApp, siempre con texto `night` (9.9:1). Nunca texto blanco sobre verde.

## Typography

- **Barlow Semi Condensed** (letra de rótulo técnico: directa, firme, fácil de leer en celular) para titulares y títulos. Titular de portada y títulos de sección en MAYÚSCULAS, peso 700; nombres de servicios y productos en mayúsculas y minúsculas, peso 600. Se eligió en lugar de Cinzel porque el dueño sintió las capitales romanas demasiado elegantes para un servicio técnico.
- **Cinzel** solo para la palabra "ROYAL VAULT" (cabecera, pie y panel), porque es la letra del logotipo.
- **Jost** (geométrica, como las leyendas del logotipo) para cuerpo, botones y leyendas en mayúsculas espaciadas (0.2–0.32em).
- Todas se sirven desde `public/assets/fonts/` (licencia OFL), nunca desde un CDN.
- La segunda línea del titular de portada va en `silver-300`: el énfasis es de valor, no de color ni degradado.

## Layout

- Contenedor `wrap` de 1200px con medianil fluido; secciones con ritmo `section`.
- **Portada centrada**, no partida: emblema → titular → leyenda entre filetes → texto → acciones → fila de pilares en la base del primer viewport (con un ajuste para pantallas de 720–800px de alto).
- **Servicios como registro con filetes** (fila: icono en círculo · título y descripción · botón), no como tarjetas iguales.
- **Catálogo como vitrina**: rejilla `auto-fill` de 300px mínimo; cada pieza en su placa oscura con luz radial.
- Nosotros + formulario en dos columnas; Ubicación como lista de datos (`dl`) con filetes + mapa.
- Celular (≤720px): pilares 2×2, barra fija inferior **Llamar | WhatsApp**, menú desplegable en la cabecera.

## Elevation & Depth

Profundidad por luz, no por cajas: luz radial detrás del emblema, `drop-shadow` suave bajo el emblema, sombra difusa y desplazada bajo el formulario (`0 24px 48px -32px`). Los bordes son filetes de 1px (`rule-dark` plata al 22% sobre oscuro, `rule-light` sobre claro).

## Shapes

Esquinas casi rectas (2–4px): metal y placas, no píldoras. Lo único redondo son los círculos de icono de servicios y redes, y el punto de "24/7". Iconos de línea Tabler (outline) en todo el sitio; la caja fuerte es un SVG propio con el mismo trazo (1.4–1.5px).

## Components

- **Botón WhatsApp**: verde, texto `night`, peso 600; tamaños sm/md/lg (2.5/3/3.5rem).
- **Botón marino**: acciones generales (enviar formulario, abrir mapa).
- **Botón fantasma sobre oscuro** y **contorno sobre claro**: acciones secundarias; el contorno se rellena de marino al pasar el cursor y la flecha avanza 3px.
- **Leyenda entre filetes** (`.ruled-caption`): texto en mayúsculas espaciadas flanqueado por dos filetes; va debajo de un titular, nunca encima como etiqueta.
- **Pilar**: icono + etiqueta en mayúsculas + pista con flecha; cada uno es un enlace funcional.
- **Etiqueta de producto** (`.tag`): plata rellena o contorno, mayúsculas espaciadas.
- **Campos**: fondo `silver-50`, borde `rule-light`, foco con borde `navy-600` y halo de 3px.
- **Movimiento**: un solo momento: un destello recorre el emblema una vez al cargar. Todo lo demás son transiciones de estado de 0.2–0.25s. `prefers-reduced-motion` desactiva todo.

## Do's and Don'ts

- **Do** usar el emblema real (`emblem.webp`, recortado del logotipo) como pieza principal hasta que existan fotos reales.
- **Do** reservar el verde para WhatsApp y el `glint` para foco y detalles.
- **Do** tematizar selección de texto, barra de desplazamiento y foco con la paleta.
- **Don't** poner etiquetas o "eyebrows" encima de los titulares.
- **Don't** usar texto con degradado, bordes de acento gruesos ni texturas que imiten metal cepillado con CSS.
- **Don't** volver al violeta de la maqueta original ni a fondos crema.
- **Don't** afirmar años, certificaciones, garantías o reseñas sin dato real (ver PRODUCT.md).
