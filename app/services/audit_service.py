import json
import os
import time
import urllib.parse
import urllib.request
from datetime import datetime, timezone
from pathlib import Path
from typing import Dict, Any, List, Optional
from app.core.config import settings

class AuditService:
    MAX_REPORTS_PER_MINUTE = 5
    BAN_DURATION_SECONDS = 86400  # 24 horas

    def __init__(self):
        self.storage_dir = settings.BASE_DIR / "storage" / "data" / "auditoria"
        self.reports_file = self.storage_dir / "reportes.json"
        self.bans_file = self.storage_dir / "bans.json"
        self.rate_limit_file = self.storage_dir / "rate_limits.json"
        self._ensure_storage()

    def _ensure_storage(self):
        self.storage_dir.mkdir(parents=True, exist_ok=True)
        if not self.reports_file.exists():
            self._write_json(self.reports_file, [])
        if not self.bans_file.exists():
            self._write_json(self.bans_file, {})
        if not self.rate_limit_file.exists():
            self._write_json(self.rate_limit_file, {})

    def _read_json(self, file_path: Path) -> Any:
        try:
            if not file_path.exists():
                return {} if "bans" in file_path.name or "rate" in file_path.name else []
            with open(file_path, "r", encoding="utf-8") as f:
                return json.load(f)
        except Exception:
            return {} if "bans" in file_path.name or "rate" in file_path.name else []

    def _write_json(self, file_path: Path, data: Any):
        try:
            with open(file_path, "w", encoding="utf-8") as f:
                json.dump(data, f, ensure_ascii=False, indent=2)
        except Exception as e:
            print(f"[AuditService] Error guardando {file_path}: {e}")

    def check_ban_status(self, ip: str) -> Dict[str, Any]:
        bans = self._read_json(self.bans_file)
        if not isinstance(bans, dict):
            bans = {}

        now = int(time.time())
        if ip in bans:
            ban_info = bans[ip]
            expires_at = int(ban_info.get("expires_at", 0))
            if expires_at > now:
                return {
                    "banned": True,
                    "expires_at": expires_at,
                    "reason": ban_info.get("reason", "Exceso de solicitudes automatizadas"),
                    "remaining_seconds": expires_at - now
                }
            # Ban expirado: limpiar
            del bans[ip]
            self._write_json(self.bans_file, bans)

        return {
            "banned": False,
            "expires_at": None,
            "reason": None,
            "remaining_seconds": 0
        }

    def ban_ip(self, ip: str, duration: int = BAN_DURATION_SECONDS, reason: str = "Exceso de reportes (>5 en 1 minuto)"):
        bans = self._read_json(self.bans_file)
        if not isinstance(bans, dict):
            bans = {}

        now = int(time.time())
        expires = now + duration
        bans[ip] = {
            "ip": ip,
            "banned_at": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
            "banned_timestamp": now,
            "expires_at": expires,
            "expires_date": datetime.fromtimestamp(expires).strftime("%Y-%m-%d %H:%M:%S"),
            "reason": reason
        }
        self._write_json(self.bans_file, bans)

    def evaluate_rate_limit(self, ip: str) -> Dict[str, Any]:
        # 1. Comprobar si ya está baneada
        status = self.check_ban_status(ip)
        if status["banned"]:
            return {
                "allowed": False,
                "banned": True,
                "remaining": 0,
                "expires_at": status["expires_at"],
                "error": "Tu dirección IP se encuentra suspendida temporalmente por 24 horas."
            }

        now = int(time.time())
        window_start = now - 60
        limits = self._read_json(self.rate_limit_file)
        if not isinstance(limits, dict):
            limits = {}

        timestamps = limits.get(ip, [])
        if not isinstance(timestamps, list):
            timestamps = []

        # Filtrar intentos en los últimos 60 segundos
        recent = [ts for ts in timestamps if isinstance(ts, (int, float)) and ts > window_start]

        if len(recent) >= self.MAX_REPORTS_PER_MINUTE:
            self.ban_ip(ip, self.BAN_DURATION_SECONDS, "Actividad no humana detectada (>5 reportes en menos de 60 segundos)")
            if ip in limits:
                del limits[ip]
            self._write_json(self.rate_limit_file, limits)
            return {
                "allowed": False,
                "banned": True,
                "remaining": 0,
                "expires_at": now + self.BAN_DURATION_SECONDS,
                "error": "Has superado el límite de 5 reportes por minuto. Tu IP ha sido suspendida por 24 horas."
            }

        recent.append(now)
        limits[ip] = recent
        self._write_json(self.rate_limit_file, limits)

        return {
            "allowed": True,
            "banned": False,
            "remaining": max(0, self.MAX_REPORTS_PER_MINUTE - len(recent))
        }

    def submit_report(self, payload: Dict[str, Any], ip: str, user_agent: str) -> Dict[str, Any]:
        # 1. Honeypot check
        honeypot = str(payload.get("website_url_check", "")).strip()
        if honeypot != "":
            self.ban_ip(ip, self.BAN_DURATION_SECONDS, "Bot detectado vía honeypot")
            return {
                "ok": False,
                "banned": True,
                "redirect": "/baneado"
            }

        # 2. Rate limit check
        rate_res = self.evaluate_rate_limit(ip)
        if not rate_res["allowed"]:
            return {
                "ok": False,
                "banned": True,
                "error": rate_res.get("error", "Solicitud bloqueada por límite de velocidad."),
                "redirect": "/baneado"
            }

        # 3. Validación
        categoria = str(payload.get("categoria", "")).strip()
        detalle = str(payload.get("detalle", "")).strip()
        if not categoria or len(detalle) < 5:
            return {
                "ok": False,
                "banned": False,
                "error": "Por favor selecciona la categoría de la anomalía y describe brevemente el hallazgo."
            }

        checkmarks = payload.get("checkmarks", [])
        if not isinstance(checkmarks, list):
            checkmarks = [str(checkmarks)]
        clean_checks = [str(c).strip() for c in checkmarks if c]

        fuente_url = str(payload.get("fuente_url", "")).strip()
        email = str(payload.get("contacto_email", "")).strip()

        filtros_raw = payload.get("filtros_activos") or payload.get("contexto_tecnico")
        filtros_activos = None
        if filtros_raw:
            if isinstance(filtros_raw, str):
                try:
                    filtros_activos = json.loads(filtros_raw)
                except Exception:
                    filtros_activos = filtros_raw
            else:
                filtros_activos = filtros_raw

        # Generar Folio
        suffix = os.urandom(2).hex().upper()
        ticket_id = f"REP-{datetime.now().strftime('%Y%m%d')}-{suffix}"

        record = {
            "ticket_id": ticket_id,
            "fecha_iso": datetime.now(timezone.utc).isoformat(),
            "fecha_humana": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
            "ip": ip,
            "user_agent": user_agent[:255],
            "origen": {
                "tipo": str(payload.get("origen_tipo", "politico")).strip(),
                "id": str(payload.get("origen_id", "")).strip(),
                "nombre": str(payload.get("origen_nombre", "")).strip(),
                "url": str(payload.get("origen_url", "")).strip(),
            },
            "categoria": categoria,
            "checkmarks": clean_checks,
            "detalle": detalle,
            "filtros_activos": filtros_activos,
            "fuente_url": fuente_url,
            "contacto_email": email,
            "estado": "pendiente",
            "notas_auditoria": []
        }

        # Guardar en JSON
        reports = self._read_json(self.reports_file)
        if not isinstance(reports, list):
            reports = []
        reports.append(record)
        self._write_json(self.reports_file, reports)

        # Despachar a Telegram (silenciado por defecto en desarrollo local para no saturar el grupo)
        telegram_enabled = os.getenv("TELEGRAM_NOTIFICATIONS_ENABLED", "false").lower() in ("true", "1", "yes")
        if telegram_enabled:
            self.dispatch_telegram(record)

        return {
            "ok": True,
            "ticket_id": ticket_id,
            "mensaje": f"Reporte recibido exitosamente. Folio asignado: {ticket_id}"
        }

    def dispatch_telegram(self, report: Dict[str, Any]) -> bool:
        token = os.getenv("TELEGRAM_BOT_TOKEN", "").strip()
        chat_id = os.getenv("TELEGRAM_CHAT_ID", "").strip()
        topic_id = os.getenv("TELEGRAM_TOPIC_ID", "").strip()

        if not token or not chat_id:
            # Fallback a lectura de .env si no cargó en env
            env_file = settings.BASE_DIR / ".env"
            if env_file.exists():
                try:
                    for line in env_file.read_text(encoding="utf-8").splitlines():
                        if line.startswith("TELEGRAM_BOT_TOKEN="):
                            token = line.split("=", 1)[1].strip()
                        elif line.startswith("TELEGRAM_CHAT_ID="):
                            chat_id = line.split("=", 1)[1].strip()
                        elif line.startswith("TELEGRAM_TOPIC_ID="):
                            topic_id = line.split("=", 1)[1].strip()
                except Exception:
                    pass

        if not token or not chat_id:
            return False

        ticket_id = report.get("ticket_id", "REP-0000")
        nombre = report.get("origen", {}).get("nombre", "Expediente General")
        origen_url = report.get("origen", {}).get("url", "")
        categoria = report.get("categoria", "anomalia").replace("_", " ").title()
        detalle = report.get("detalle", "")
        fuente_url = report.get("fuente_url", "")
        fecha = report.get("fecha_humana", datetime.now().strftime("%Y-%m-%d %H:%M:%S"))
        ip = report.get("ip", "0.0.0.0")

        text = (
            f"🚩 <b>NUEVO REPORTE DE ANOMALÍA</b>\n"
            f"<b>Folio:</b> <code>{ticket_id}</code>\n\n"
            f"👤 <b>Expediente:</b> {nombre}\n"
            f"🏷️ <b>Categoría:</b> {categoria}\n"
        )

        checks = report.get("checkmarks", [])
        if checks:
            text += f"📋 <b>Detalles:</b> <i>{', '.join(checks)}</i>\n"

        text += f"\n💬 <b>Descripción del ciudadano:</b>\n<i>\"{detalle}\"</i>\n"

        if fuente_url and fuente_url.startswith("http"):
            text += f"\n🔗 <b>Fuente aportada:</b> <a href=\"{fuente_url}\">Enlace Oficial de Respaldo</a>\n"

        text += f"\n🕒 <b>Fecha:</b> {fecha}\n"
        text += f"🌐 <b>IP / Auditoría:</b> <code>{ip}</code>"

        buttons = []
        if origen_url and origen_url.startswith("http") and "localhost" not in origen_url and "127.0.0.1" not in origen_url:
            buttons.append({"text": "🔍 Ver Ficha en Web", "url": origen_url})
        if fuente_url and fuente_url.startswith("http") and "localhost" not in fuente_url and "127.0.0.1" not in fuente_url:
            buttons.append({"text": "🔗 Fuente Oficial", "url": fuente_url})

        payload = {
            "chat_id": chat_id,
            "text": text,
            "parse_mode": "HTML"
        }
        if buttons:
            payload["reply_markup"] = json.dumps({"inline_keyboard": [buttons]})
        if topic_id:
            try:
                payload["message_thread_id"] = int(topic_id)
            except ValueError:
                pass

        try:
            url = f"https://api.telegram.org/bot{token}/sendMessage"
            data = urllib.parse.urlencode(payload).encode("utf-8")
            req = urllib.request.Request(url, data=data, method="POST")
            with urllib.request.urlopen(req, timeout=6) as response:
                res_body = response.read().decode("utf-8")
                res_json = json.loads(res_body)
                return bool(res_json.get("ok"))
        except Exception as e:
            print(f"[AuditService] Telegram dispatch error: {e}")
            return False

audit_service = AuditService()
