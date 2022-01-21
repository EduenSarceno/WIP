# Descripción
A la hora de traducir un programa es común encontrar el texto a traducir rodeado de partes que no interesan
(código, etiquetas, etc)

xtxt extrae únicamente el texto que le interesa al traductor. Además, xtxt permite re-insertar los textos
extraídos (probablemente ya traducidos) de nuevo al contenedor.

# Historia
Greg#5427 me pide ocasionalmente crearle programas para extraer texto y re-insertar texto, y cómunmente es
el mismo proceso

Para extraer:

  - Abrir el archivo contenedor (ej. some_scene01.ext)
  - Extraer los textos usando una expresión regular
  - Escribir los textos previamente extraídos a un archivo (ej some_scene01.txt)

Para re-insertar:

  - Abrir el archivo contenedor (ej. some_scene01.ext)
  - Abrir los textos previamente extraídos (ej: some_scene01.txt)
  - Reemplazar las coincidencias de la expresión regular: la n-ésima coincidencia, debe reemplazarse con
    la n-ésima línea del .txt
  - Guardar el nuevo texto a un archivo (ej some_scene01-translated.txt)

# Modo de uso
Para ver el modo de uso ejecutar:

```
$ node xtxt.js -help
```
