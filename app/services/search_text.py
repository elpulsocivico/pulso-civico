import unicodedata


def normalizar_busqueda(texto: str) -> str:
    """Compara texto sin distinguir mayúsculas ni marcas diacríticas."""
    return "".join(
        caracter for caracter in unicodedata.normalize("NFD", texto.casefold())
        if not unicodedata.combining(caracter)
    )
