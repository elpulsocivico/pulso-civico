import time
from collections import defaultdict
from typing import Dict, List
from fastapi import Request
from starlette.middleware.base import BaseHTTPMiddleware
from starlette.responses import JSONResponse
from app.core.config import settings

class RateLimitMiddleware(BaseHTTPMiddleware):
    """
    Middleware protector de recursos:
    Limita la cantidad de peticiones concurrentes a los endpoints /api/
    por dirección IP para evitar scraping masivo, abusos y saturación de CPU/memoria.
    """
    def __init__(self, app, requests_limit: int = 60, window_seconds: int = 60):
        super().__init__(app)
        self.requests_limit = requests_limit
        self.window_seconds = window_seconds
        self.requests: Dict[str, List[float]] = defaultdict(list)
        self.last_cleanup = time.time()

    async def dispatch(self, request: Request, call_next):
        path = request.url.path

        # Aplicar el freno de mano únicamente a rutas bajo /api/
        if path.startswith("/api/"):
            now = time.time()

            # Mantenimiento de memoria cada 5 minutos
            if now - self.last_cleanup > 300:
                self._cleanup(now)
                self.last_cleanup = now

            # Detectar IP real del cliente (incluso detrás de proxies como Cloudflare o Nginx)
            forwarded = request.headers.get("x-forwarded-for")
            if forwarded:
                client_ip = forwarded.split(",")[0].strip()
            else:
                client_ip = request.client.host if request.client else "127.0.0.1"

            # Filtrar accesos dentro de la ventana de tiempo activa
            valid_timestamps = [t for t in self.requests[client_ip] if now - t < self.window_seconds]
            self.requests[client_ip] = valid_timestamps

            # Si excede el límite permitido por minuto, rechazar de inmediato
            if len(valid_timestamps) >= self.requests_limit:
                return JSONResponse(
                    status_code=429,
                    content={
                        "error": "Demasiadas solicitudes",
                        "detail": "Has alcanzado el límite de consultas permitidas por minuto. Por favor, espera unos segundos para evitar sobrecargar el servidor."
                    },
                    headers={"Retry-After": str(self.window_seconds)}
                )

            self.requests[client_ip].append(now)

        return await call_next(request)

    def _cleanup(self, now: float):
        """Elimina de la memoria IPs inactivas fuera de la ventana."""
        stale_ips = []
        for ip, timestamps in self.requests.items():
            valid = [t for t in timestamps if now - t < self.window_seconds]
            if not valid:
                stale_ips.append(ip)
            else:
                self.requests[ip] = valid
        for ip in stale_ips:
            del self.requests[ip]
