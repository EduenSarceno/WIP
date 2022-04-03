'''
S-DES
Es un algoritmo de encriptación simple, no seguro. Posee una estructura
y propiedades similares a DES pero con parámetros mucho más reducidos.
'''

from array import array

#--- PERMUTACIONES --
#Cada valor en el arreglo indica cómo permutar,
#Nota: las posiciones empiezan en 1 y se enumeran del bit más significativo al
#menos significativo (MSB).
#Ejemplo: kP4 = [2, 4, 3, 1]
#  Es equivalente a realizar la permutación
#      k1 -> k2
#      k2 -> k4
#      k3 -> k3
#      k4 -> k1
#  Dado el numero x = (k1, k2, k3, k4), entonces, p4(x) = (k2, k4, k3, k1)
_kP10 = [3, 5, 2, 7, 4, 10, 1, 9, 8, 6]
_kP8 = [6, 3, 7, 4, 8, 5, 10, 9]
_kP4 = [2, 4, 3, 1]
'Permutación inicial'
_kIP = [2, 6, 3, 1, 4, 8, 5, 7]
'Expansión/Permutación'
_kEP = [4, 1, 2, 3, 2, 3, 4, 1]
#--- END PERMUTACIONES ---

#-- MATRICES --
#Las mátrices son utilizadas para transformar una cadena de A -> B, ambas de 4bits.
#Internamente, S-DES extiende la cadena A a una cadena A' de 8bits.
#Los primeros 4bits de A' se utilizan para alimentar S0
#Los ultimos 4bits de A' se utilizan para alimentar S1
# Por ultimo, se unen las respuestas de S0 y S1 para generar la cadena B
_kS0 = [
    [1, 0, 3, 2],
    [3, 2, 1, 0],
    [0, 2, 1, 3],
    [0, 1, 3, 2]
]
_kS1 = [
    [0, 1, 2, 3],
    [2, 0, 1, 3],
    [3, 0, 1, 2],
    [2, 1, 0, 3]
]
#-- END MATRICES --

#Si bien, podríamos implementar todo el algoritmo utilizando esta función, lo mejor
#es usarla únicamente para pre-computar los distintos valores
def _perm(arr, nin, nout, val):
    '''Permuta un valor, segun las reglas impuestas
    Parameters:
        arr (list): reglas a aplicar
        nin (int): número de bits en la entrada
        nout (int): número de bits en la salida
        val (int): número a permutar
    '''
    trimMask = (1 << nout) - 1 #para recortar la salida
    ret = 0
    #Recorremos las reglas a aplicar
    for i in range(0, len(arr)):
        n = arr[i] #K(i+1) -> Kn
        bitMask = 1 << (nin - n) #para extraer el n-bit
        bit = (val & bitMask) >> (nin - n) #n-bit
        ret |= bit << (nout - (i + 1))
    return ret & trimMask

def _CLShift(n, b, val):
    '''Circular Left Shift'''
    trimMask = (1 << b) - 1
    for i in range(0, n):
        bitMask = 1 << (b - 1)
        msb = val & bitMask
        val <<= 1
        if (msb):
            val |= 1
    return val & trimMask

def _genP10Table():
    '''Genera los distintos valores para la tabla tP10'''
    global _tP10
    ar = [0] * 1024
    for i in range(0, 1024):
        ar[i] = _perm(_kP10, 10, 10, i)
    _tP10 = array('H', ar)

def _genP8Table():
    '''Genera los distintos valores para la tabla tP8'''
    global _tP8
    ar = [0] * 1024
    for i in range(0, 1024):
        ar[i] = _perm(_kP8, 10, 8, i)
    _tP8 = array('B', ar)

def _genP4Table():
    '''Genera los distintos valores para la tabla tP4'''
    global _tP4
    ar = [0] * 16
    for i in range(0, 16):
        ar[i] = _perm(_kP4, 4, 4, i)
    _tP4 = array('B', ar)

def _genEPTable():
    '''Genera los distintos valores para la tabla tEP'''
    global _tEP
    ar = [0] * 16
    for i in range(0, 16):
        ar[i] = _perm(_kEP, 4, 8, i)
    _tEP = array('B', ar)

def _genS0Table():
    '''Genera los distintos valores para la tabla tS0'''
    global _tS0
    ar = [0] * 16
    for i in range(0, 16):
        row = ((i & 0x8) >> 2) | (i & 0x1)
        col = (i & 0x6) >> 1
        ar[i] = _kS0[row][col]
    _tS0 = array('B', ar)

def _genS1Table():
    '''Genera los distintos valores para la tabla tS1'''
    global _tS1
    ar = [0] * 16
    for i in range(0, 16):
        row = ((i & 0x8) >> 2) | (i & 0x1)
        col = (i >> 1) & 0x3
        ar[i] = _kS1[row][col]
    _tS1 = array('B', ar)

def _genIPTable():
    '''Genera los distintos valores para la tabla tIP'''
    global _tIP
    ar = [0] * 256
    for i in range(0, 256):
        ar[i] = _perm(_kIP, 8, 8, i)
    _tIP = array('B', ar)

def _genINVPTable():
    '''Genera los distintos valores para la tabla tINVP'''
    global _tINVP
    kINV = [0] * 8
    for i in range(0, 8):
        kINV[_kIP[i] - 1] = i + 1
    ar = [0] * 256
    for i in range(0, 256):
        ar[i] = _perm(kINV, 8, 8, i)
    _tINVP = array('B', ar)

def init():
        '''Inicializa el modulo S-DES'''
        _genP10Table()
        _genP8Table()
        _genP4Table()
        _genEPTable()
        _genIPTable()
        _genINVPTable()
        _genS0Table()
        _genS1Table()

def _genSubkeysFor(key):
    '''Genera las subclaves de una clave
    Parameters:
        key (int): clave de 10-bit
    Return:
        list: subclaves de 8-bit
    '''
    subKeys = [0] * 2
    key = key & 0x3FF #truncamos a 10-bit
    r = _tP10[key]
    L = (r >> 5); R = r & 0x1F
    for i in range(0, 2):
        L = _CLShift(i + 1, 5, L)
        R = _CLShift(i + 1, 5, R)
        subKeys[i] = _tP8[ (L << 5) | R ]
    return subKeys

def _F(L, R, sk):
    #EP
    E = _tEP[R]
    #XOR Subkey
    E ^= sk
    #S0,S1
    S0 = _tS0[E >> 4]; S1 = _tS1[E & 0xF]
    #P4
    P4 = _tP4[(S0 << 2) | S1]
    #XOR LEFT
    P4 ^= L
    #COMBINE
    return (P4 << 4) | R


class Cipher:
    def __init__(self, key):
        '''Parametros:
            key (int): clave de 10-bit
        '''
        self._key = key
        self._subKeys = _genSubkeysFor(key)

    def encrypt(self, byte):
        return self._sdes(byte, encrypt=True)

    def decrypt(self, byte):
        return self._sdes(byte, encrypt=False)

    def _sdes(self, byte, encrypt):
        '''Encripta/Desencripta un byte utilizando S-DES'''
        #IP
        ret = _tIP[byte]
        L = ret >> 4; R = ret & 0xF
        #f1
        sk = 0 if encrypt else 1
        ret = _F(L, R, self._subKeys[sk])
        #SWAP
        L = ret & 0xF; R = ret >> 4
        #f2
        sk = 1 if encrypt else 0
        ret = _F(L, R, self._subKeys[sk])
        #INVP
        ret = _tINVP[ret]
        return ret
