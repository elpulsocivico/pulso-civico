import time
from collections import OrderedDict
from typing import List
from fastapi import Request
from starlette.middleware.base import BaseHTTPMiddleware
from starlette.responses import JSONResponse
from app.core.config import settings


def get_client_ip(request: Request) -> str:
    """
    Obtiene la IP real del cliente validando si la conexión directa proviene
    de un proxy inverso de confianza (TRUSTED_PROXIES), evitando spoofing de X-Forwarded-For.
    """
    direct_ip = request.client.host if request.client else "127.0.0.1"
    if direct_ip in settings.TRUSTED_PROXIES:
        forwarded = request.headers.get("x-forwarded-for")
        if forwarded:
            return forwarded.split(",")[0].strip() or direct_ip
    return direct_ip


class RateLimitMiddleware(BaseHTTPMiddleware):
    """
    Middleware protector de recursos:
    Limita la cantidad de peticiones concurrentes a los endpoints /api/
    por dirección IP con caché LRU acotada para evitar fugas de memoria por IP spoofing.
    """
    MAX_TRACKED_IPS = 4096

    def __init__(self, app, requests_limit: int = 60, window_seconds: int = 60):
        super().__init__(app)
        self.requests_limit = requests_limit
        self.window_seconds = window_seconds
        self.requests: "OrderedDict[str, List[float]]" = OrderedDict()
        self.last_cleanup = time.time()

    async def dispatch(self, request: Request, call_next):
        path = request.url.path

        # Aplicar el freno de mano únicamente a rutas bajo /api/
        if path.startswith("/api/"):
            now = time.time()

            # Mantenimiento periódico de memoria
            if now - self.last_cleanup > 120 or len(self.requests) > self.MAX_TRACKED_IPS:
                self._cleanup(now)
                self.last_cleanup = now

            client_ip = get_client_ip(request)

            existing = self.requests.get(client_ip, [])
            valid_timestamps = [t for t in existing if now - t < self.window_seconds]

            # Si excede el límite permitido por minuto, rechazar de inmediato
            if len(valid_timestamps) >= self.requests_limit:
                self.requests[client_ip] = valid_timestamps
                self.requests.move_to_end(client_ip)
                return JSONResponse(
                    status_code=429,
                    content={
                        "error": "Demasiadas solicitudes",
                        "detail": "Has alcanzado el límite de consultas permitidas por minuto. Por favor, espera unos segundos para evitar sobrecargar el servidor."
                    },
                    headers={"Retry-After": str(self.window_seconds)}
                )

            valid_timestamps.append(now)
            self.requests[client_ip] = valid_timestamps
            self.requests.move_to_end(client_ip)
            while len(self.requests) > self.MAX_TRACKED_IPS:
                self.requests.popitem(last=False)

        return await call_next(request)

    def _cleanup(self, now: float):
        """Elimina de la memoria IPs inactivas fuera de la ventana."""
        stale_ips = []
        for ip, timestamps in list(self.requests.items()):
            valid = [t for t in timestamps if now - t < self.window_seconds]
            if not valid:
                stale_ips.append(ip)
            else:
                self.requests[ip] = valid
        for ip in stale_ips:
            self.requests.pop(ip, None)
        while len(self.requests) > self.MAX_TRACKED_IPS:
            self.requests.popitem(last=False)
