FROM python:3.12-slim

# Evitar buffers y escribir logs directamente
ENV PYTHONUNBUFFERED=1 \
    PYTHONDONTWRITEBYTECODE=1

# Instalar tor y dependencias de sistema mínimas
RUN apt-get update && apt-get install -y --no-install-recommends \
    tor \
    curl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Instalar requerimientos de Python
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copiar aplicación y recursos necesarios
COPY app/ ./app/
COPY storage/ ./storage/
COPY assets/ ./assets/

# Exponer el puerto de la aplicación FastAPI
EXPOSE 8000

CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8000"]
