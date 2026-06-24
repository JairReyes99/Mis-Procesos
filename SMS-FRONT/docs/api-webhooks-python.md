# SMS Intelix — API Webhooks para servidor Python

Documento técnico para el desarrollador del servidor de envío SMS.  
Versión: 2026-05-28

---

## Autenticación

Todos los endpoints usan **Bearer token** en el header `Authorization`.

```
Authorization: Bearer secret_sms_intelix_2026
```

> El token está definido en `.env` como `CAMPAIGN_WEBHOOK_SECRET`.  
> En producción se reemplazará por uno seguro.

---

## Base URL

| Ambiente    | URL base                        |
|-------------|---------------------------------|
| Desarrollo  | `http://sms-intelix.test`       |
| Producción  | *(pendiente de definir)*        |

Todos los endpoints están bajo `/api/`.

---

## Endpoint 1 — Progreso de campaña

### `POST /api/campaign/{id}/progress`

Notifica el avance del envío de una campaña masiva. Python debe llamar este endpoint **periódicamente** durante el envío y una vez más al finalizar con `"completed": true`.

#### Parámetros de ruta

| Parámetro | Tipo   | Descripción                        |
|-----------|--------|------------------------------------|
| `id`      | entero | ID de la campaña (`campaigns.id`)  |

#### Cuerpo JSON

```json
{
    "sent":      1500,
    "failed":    20,
    "completed": false
}
```

| Campo       | Tipo    | Requerido | Descripción                                                             |
|-------------|---------|-----------|-------------------------------------------------------------------------|
| `sent`      | entero  | sí        | Total acumulado de mensajes enviados exitosamente hasta este momento    |
| `failed`    | entero  | sí        | Total acumulado de mensajes fallidos hasta este momento                 |
| `completed` | booleano| no        | `true` cuando el envío terminó por completo. Default: `false`           |

> **Importante:** `sent` y `failed` son **acumulados totales**, no deltas.  
> Ejemplo: si en la llamada anterior enviaste 1000 y ahora enviaste 500 más, manda `"sent": 1500`.

#### Comportamiento del servidor

- Calcula el delta respecto a la llamada anterior y actualiza `campaign_recipients` (marca enviados/fallidos).
- Si `completed = true`:
  - Cambia `campaign_status` a `4` (Completada).
  - Cobra el costo al saldo de la empresa.
  - Notifica al usuario en tiempo real vía WebSocket.
- Si `completed = false`:
  - Actualiza contadores y notifica la barra de progreso en tiempo real.

#### Respuesta exitosa

```json
{ "status": "ok" }
```

#### Errores

| HTTP | Descripción                                      |
|------|--------------------------------------------------|
| 401  | Token inválido o ausente                         |
| 404  | Campaña no encontrada                            |
| 422  | Campos faltantes o con valor inválido            |

#### Ejemplo Python

```python
import requests

BASE_URL = "http://sms-intelix.test"
TOKEN    = "secret_sms_intelix_2026"
HEADERS  = {"Authorization": f"Bearer {TOKEN}"}

def report_progress(campaign_id: int, sent: int, failed: int, completed: bool = False):
    resp = requests.post(
        f"{BASE_URL}/api/campaign/{campaign_id}/progress",
        headers=HEADERS,
        json={
            "sent":      sent,
            "failed":    failed,
            "completed": completed,
        },
        timeout=10,
    )
    resp.raise_for_status()
    return resp.json()

# Durante el envío (cada N mensajes o cada X segundos)
report_progress(campaign_id=5, sent=2000,  failed=30)
report_progress(campaign_id=5, sent=4000,  failed=55)

# Al terminar
report_progress(campaign_id=5, sent=40000, failed=210, completed=True)
```

#### Frecuencia recomendada de llamadas

- Cada **2 000 mensajes procesados**, o bien
- Cada **10–15 segundos** si el volumen es menor.
- Llamada final obligatoria con `"completed": true` al terminar.

---

## Endpoint 2 — Resultado de SMS de prueba

### `POST /api/sms/test/{id}/result`

Notifica el resultado de un SMS de prueba individual enviado desde el panel de la empresa. Python debe llamar este endpoint **una sola vez** cuando el SMS fue procesado (enviado o fallido).

#### Parámetros de ruta

| Parámetro | Tipo   | Descripción                              |
|-----------|--------|------------------------------------------|
| `id`      | entero | ID del registro (`test_sms_sends.id`)    |

#### Cuerpo JSON — envío exitoso

```json
{
    "status": 1
}
```

#### Cuerpo JSON — envío fallido

```json
{
    "status": 2,
    "error_message": "Número inválido o fuera de servicio"
}
```

| Campo           | Tipo   | Requerido | Descripción                                    |
|-----------------|--------|-----------|------------------------------------------------|
| `status`        | entero | sí        | `1` = enviado correctamente, `2` = fallido     |
| `error_message` | string | no        | Razón del fallo. Máximo 500 caracteres         |

#### Comportamiento del servidor

- Actualiza `test_sms_sends.status` y `test_sms_sends.error_message`.
- Notifica al usuario en tiempo real vía WebSocket: la UI muestra "✓ SMS enviado" o "✗ Error".

#### Respuesta exitosa

```json
{ "status": "ok" }
```

#### Errores

| HTTP | Descripción                                      |
|------|--------------------------------------------------|
| 401  | Token inválido o ausente                         |
| 404  | Registro de prueba no encontrado                 |
| 422  | `status` faltante o valor distinto de 1 o 2      |

#### Ejemplo Python

```python
def report_test_sms(test_sms_id: int, success: bool, error_msg: str = None):
    payload = {"status": 1 if success else 2}
    if error_msg:
        payload["error_message"] = error_msg

    resp = requests.post(
        f"{BASE_URL}/api/sms/test/{test_sms_id}",
        headers=HEADERS,
        json=payload,
        timeout=10,
    )
    resp.raise_for_status()
    return resp.json()

# Éxito
report_test_sms(test_sms_id=3, success=True)

# Fallo
report_test_sms(test_sms_id=3, success=False, error_msg="Operadora rechazó el número")
```

---

## Estructura de tablas relevantes

### `campaigns`

| Columna            | Tipo              | Descripción                                                |
|--------------------|-------------------|------------------------------------------------------------|
| `id`               | bigint            | ID de la campaña — usar en la URL del webhook              |
| `uuid`             | char(36)          | UUID único de la campaña                                   |
| `company_id`       | bigint            | Empresa dueña                                              |
| `name`             | varchar(255)      | Nombre de la campaña                                       |
| `send_type_id`     | bigint            | Tipo de envío (ver tabla `campaign_send_types`)            |
| `scheduled_at`     | datetime nullable | Fecha/hora programada (null = envío inmediato)             |
| `no_send_rules`    | text (JSON)       | Restricciones horarias: `[{"from":"21:00","to":"07:00"}]`  |
| `total_recipients` | int               | Total de destinatarios cargados                            |
| `sent_count`       | int               | Enviados acumulados (actualizado por el webhook)           |
| `failed_count`     | int               | Fallidos acumulados (actualizado por el webhook)           |
| `campaign_status`  | tinyint           | Estado actual (ver catálogo abajo)                         |
| `completed_at`     | datetime nullable | Fecha de finalización (lo pone el servidor al completar)   |
| `charged_at`       | datetime nullable | Fecha del cobro definitivo (lo pone el servidor)           |
| `charged_cost`     | decimal(10,4)     | Costo total cobrado                                        |

**Estados de campaña (`campaign_status`):**

| ID | Nombre      | Descripción                                      |
|----|-------------|--------------------------------------------------|
| 1  | Borrador    | Creada pero no enviada                           |
| 2  | Programada  | En cola, esperando su `scheduled_at`             |
| 3  | Procesando  | Python está enviando actualmente                 |
| 4  | Completada  | Envío terminado (lo cambia el webhook al completar) |
| 5  | Pausada     | Envío pausado manualmente                        |
| 6  | Cancelada   | Campaña cancelada                                |

---

### `campaign_recipients`

| Columna         | Tipo              | Descripción                                                       |
|-----------------|-------------------|-------------------------------------------------------------------|
| `id`            | bigint            | ID del destinatario                                               |
| `campaign_id`   | bigint            | FK a `campaigns.id`                                               |
| `phone`         | varchar(20)       | Teléfono con código de país. Ej: `+5215512345678`                 |
| `message`       | text              | Mensaje ya personalizado listo para enviar                        |
| `segments`      | tinyint           | Número de segmentos SMS (calculado al subir el archivo)           |
| `encoding`      | varchar(7)        | `GSM7` o `Unicode`                                                |
| `send_status`   | tinyint           | Estado del envío (ver catálogo abajo)                             |
| `sent_at`       | datetime nullable | Fecha de envío exitoso (lo actualiza el webhook)                  |
| `error_message` | varchar(500)      | Descripción del error si `send_status = 3`                        |
| `cost`          | decimal(10,4)     | Costo de este mensaje (lo calcula el servidor en el webhook)      |

**Estados de destinatario (`send_status`):**

| ID | Nombre    | Descripción                                     |
|----|-----------|-------------------------------------------------|
| 1  | Pendiente | Aún no procesado — Python debe enviar estos     |
| 2  | Enviado   | Entregado exitosamente                          |
| 3  | Error     | Falló el envío                                  |
| 4  | Bloqueado | Excluido por regla horaria (`no_send_rules`)    |

> **Python solo necesita leer** los registros con `send_status = 1`.  
> El servidor actualiza los estados 2 y 3 automáticamente al recibir el webhook de progreso.

---

### `test_sms_sends`

| Columna         | Tipo              | Descripción                                        |
|-----------------|-------------------|----------------------------------------------------|
| `id`            | bigint            | ID del envío de prueba — usar en la URL del webhook|
| `company_id`    | bigint nullable   | Empresa que solicitó la prueba                     |
| `user_id`       | bigint            | Usuario que solicitó la prueba                     |
| `country_code`  | varchar(10)       | Código de país. Ej: `+52`                          |
| `phone`         | varchar(20)       | Número sin código de país. Ej: `5512345678`        |
| `message`       | text              | Mensaje a enviar                                   |
| `status`        | tinyint           | `0`=pendiente, `1`=enviado, `2`=fallido            |
| `error_message` | varchar(500)      | Razón del fallo (opcional)                         |

> El teléfono completo para enviar es `country_code + phone`.  
> Ej: `country_code = "+52"`, `phone = "5512345678"` → marcar `+525512345678`.

---

## Flujo completo de una campaña

```
[Panel web]                    [Servidor Laravel]              [Servidor Python]
     |                                |                               |
     |-- Crea campaña --------------->|                               |
     |   (status=1 Borrador)          |                               |
     |                                |                               |
     |-- Confirma envío ------------->|                               |
     |   (status cambia a 2/3)        |                               |
     |                                |-- Campaña disponible -------->|
     |                                |   (status=2 o 3)              |
     |                                |   campaign_recipients         |
     |                                |   con send_status=1           |
     |                                |                               |-- Lee recipients con status=1
     |                                |                               |-- Envía SMS uno a uno
     |                                |<-- POST /progress (parcial) --|
     |<-- WebSocket progreso ---------|   sent=2000, failed=30        |
     |   (barra de progreso)          |                               |
     |                                |<-- POST /progress (parcial) --|
     |<-- WebSocket progreso ---------|   sent=10000, failed=120      |
     |                                |                               |
     |                                |<-- POST /progress (final) ----|
     |                                |   sent=40000, failed=210      |
     |                                |   completed=true              |
     |                                |-- Cobra créditos              |
     |<-- WebSocket completado --------|-- Actualiza saldo            |
     |   (resultado + nuevo saldo)    |                               |
```

## Flujo de SMS de prueba

```
[Panel web]                    [Servidor Laravel]              [Servidor Python]
     |                                |                               |
     |-- Enviar SMS de prueba ------->|                               |
     |                                |-- Registra en DB ------------>|
     |                                |   test_sms_sends.id = 42      |
     |<-- "En cola de envío…" --------|   status=0                    |
     |   (spinner en UI)              |                               |-- Lee test_sms_sends status=0
     |                                |                               |-- Envía el SMS
     |                                |<-- POST /sms/test/42/result --|
     |                                |   {"status": 1}               |
     |<-- WebSocket resultado --------|                               |
     |   "✓ SMS enviado"              |                               |
```

---

## Notas de implementación para Python

1. **Polling de campañas pendientes:** consultar `campaigns` donde `campaign_status IN (2, 3)` para encontrar campañas a procesar.

2. **Leer destinatarios:** hacer `SELECT * FROM campaign_recipients WHERE campaign_id = ? AND send_status = 1` para obtener los mensajes a enviar. El campo `message` ya tiene el texto final personalizado.

3. **Reglas horarias (`no_send_rules`):** el campo `campaigns.no_send_rules` contiene un JSON como `[{"from": "21:00", "to": "07:00"}]`. Si el envío cae en ese rango, el destinatario debe marcarse como `send_status = 4` (Bloqueado) directamente en la BD, **sin** contarlo como enviado ni fallido en el webhook.

4. **Polling de SMS de prueba:** consultar `test_sms_sends` donde `status = 0`. El número destino completo es `country_code + phone`.

5. **Reintentos del webhook:** si la llamada al webhook falla (timeout, 5xx), reintentar con backoff exponencial. No marcar el mensaje como fallido solo porque el webhook no respondió.

6. **Timeout recomendado:** 10 segundos por llamada al webhook.
