S-DES
=======
Implementación trivial de S-DES.

### Historia
Tibe#8502 necesitaba implementar S-DES en python para
un proyecto de la Universidad, estuve tentado a decirle que no, ya que odio python, pero el proyecto era lo suficientemente
interesante como para rehusarme (?

## Modo de uso
Bueno, el proyecto se divide en 2, la biblioteca
`sdes.py` que se encarga de la lógica del algoritmo, y el script `app` que se encarga de la lógica de la aplicación.
Actualmente la aplicación sencillamente recibe un archivo
y una clave para posteriormente encriptar/desencriptar

## Ejemplos
### Encriptar imagen
encriptar `_prueba.jpg_` con la clave de _10-bit_ `_0001110000_`
y guardar el resultado en `prueba.jpg.enc`

```
    >app --out prueba.jpg.enc --enc prueba.jpg 0001110000
```
### Desencriptar imagen
desencriptar `prueba.jpg.enc` con la clave de _10-bit_ `0001110000`
y guardar el resultado en `prueba(2).jpg`

```
    >app --out "prueba(2).jpg" --dec prueba.jpg.enc 0001110000
```
