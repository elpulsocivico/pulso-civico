# MANIFIESTO DE PULSO CÍVICO
**Principios Inviolables de Neutralidad, Reproducibilidad y Código Abierto**

---

## 1. Misión
Pulso Cívico es una plataforma cívica independiente desarrollada por la comunidad. Nuestro único fin es hacer accesibles, legibles y auditables los datos públicos de los representantes populares en México.

No somos un partido político, no representamos a ninguna facción y no recibimos financiamiento gubernamental ni partidista.

---

## 2. La Regla de Oro: Neutralidad Fáctica
1. **Cero Adjetivos y Cero Opiniones**: La plataforma no califica a ningún funcionario como «bueno», «malo», «corrupto» o «destacado». Los datos hablan por sí mismos a través de registros oficiales (asistencias, votaciones, trayectoria y comisiones).
2. **Sin Fuentes no hay Datos**: Todo dato presentado debe estar respaldado por un enlace directo a una fuente oficial del Estado mexicano (`.gob.mx`, congresos locales, INE, INEGI, SIL-SEGOB o Gaceta Parlamentaria).
3. **Prohibición de Datos Personales Sensibles**: Solo se recopila información inherente al ejercicio de la función pública (oficinas del recinto, teléfonos de conmutador oficial y correos institucionales). Quedan estrictamente prohibidos domicilios particulares, teléfonos privados o información familiar.

---

## 3. Principio del Bazar y Reproducibilidad Criptográfica
1. **Prohibido Subir Datasets Manuales**: Ningún archivo JSON puede ser editado a mano en el repositorio principal. Todo dato debe ser el resultado reproducible de un script extractor (*scraper*) registrado en `src/Services/`.
2. **Cualquiera Puede Verificar**: Cualquier persona puede clonar el repositorio, ejecutar el scraper en su máquina local y obtener exactamente los mismos datos directamente de los servidores de gobierno.
3. **Auditoría Automatizada**: El pipeline de integración y las herramientas de auditoría (`tools/gateway.php`) rechazan cualquier Pull Request cuyos registros carezcan de `fuente_url` oficial verificable o intenten inyectar fuentes no gubernamentales.

---

## 4. Estructura Tipo Hidra (Descentralización y Resiliencia)
1. **Código y Datos Abiertos**: Todo el código se distribuye bajo licencias abiertas y los datos son de dominio público.
2. **Forks y Espejos Bienvenidos**: Si un nodo o servidor es dado de baja, cualquier ciudadano u organización tiene el derecho y la capacidad técnica de levantar un espejo o fork independiente con la totalidad de los datos históricos.
3. **Meritocracia Técnica**: No existen comités de censura ni mandos burocráticos. Las mejoras se proponen mediante código funcional, trazabilidad y consenso técnico documentado en Pull Requests públicos.
