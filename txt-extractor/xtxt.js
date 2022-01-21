"use strict"

/* Lazyload */
var prgName
var fs;

/* Default values */
var outName = "out.txt"
var reStr = "^.*$"
var reFlags = "gm"
var encoding = "utf8"

/* Global variables */
var command
var regex

var args = process.argv.slice(2)
while (args[0] && args[0].startsWith('-')) {
    let opt = args.shift()
    switch (opt) {
    case '-o':
        outName = args.shift()
        break
    case '-e':
        reStr = args.shift()
        break
    case '-f':
        reFlags = args.shift()
        break
    case '-encoding':
        encoding = args.shift()
        break
    case '-help':
        printUsageAndExit()
    default:
        console.error('Opcion desconocida: ' + opt);
        process.exit(-1)
    }
}

command = args.shift()
if (!command) {
    printUsageAndExit()
}

fs = require('fs')
regex = new RegExp(reStr, reFlags)

if (command === 'e' && args.length == 1) {
    let input
    let matches, out

    try {
        input = fs.readFileSync(args[0], encoding)
    } catch (error) {
        console.error(error.message)
        process.exit(-1)
    }

    out = ''
    matches = input.matchAll(regex)
    for (let match of matches) {
        out += match[0] + '\n'
    }

    try {
        fs.writeFileSync(outName, out, encoding)
    } catch (error) {
        console.error(error.message)
        process.exit(-1)
    }
} else if (command === 'r' && args.length == 2) {
    let src, orig
    let i, out

    try {
        orig = fs.readFileSync(args[0], encoding)
        src = fs.readFileSync(args[1], encoding)
    } catch (error) {
        console.error(error.message)
        process.exit(-1)
    }

    i = 0
    src = src.split(/\n/)
    out = orig.replace(regex, function replacer (match) {
        return src[i++]
    })

    try {
        fs.writeFileSync(outName, out, encoding)
    } catch (error) {
        console.error(error.message)
        process.exit(-1)
    }
} else {
    printUsageAndExit()
}


function printUsageAndExit() {
    setProgramName();
    console.error(`Modo de uso: ${prgName} [opciones] [e|r] argumentos...
Comandos:
    e                 extraer
    r                 re-insertar
Opciones:
    -o                nombre del archivo generado, por defecto "out.txt"
    -e                expresion regular, por defecto "^.*$"
    -f                flags de la expresion regular, por defecto "gm"
    -encoding         el conjunto de caracteres usado en los archivos, por defecto "utf8"
    -help             imprime este mensaje de ayuda

Ejemplos:
1. Extraer los textos en scene_01.scene y guardarlos en scene_01.txt
    ${prgName} -o "scene_01.txt" e "scene_01.scene"

2. Re-insertar los textos de scene_01.txt en scene_01.scene y guardar el resultado
   en scene_01-translated.scene
    ${prgName} -o "scene_01-translated.scene" "scene_01.scene" "secene_01.txt"
`)
    process.exit(-1)
}

function setProgramName() {
    var path
    var node, script

    if (!!prgName) {
        return prgName;
    }

    path = require('path')
    node = path.parse(process.argv[0]).base
    script = path.parse(process.argv[1]).base
    prgName = `${node} ${script}`
}
