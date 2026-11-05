import requests
import time
import random
from datetime import datetime, timezone

url = "http://localhost:8000/api/sensor-readings"

print("Iniciando simulador do nó IoT...")

seq = 1
uptime = 1000
configs = ["Alta A", "Alta B", "Baixa A", "Baixa C", "AMBIGUA"]

while True:
    temp = round(random.uniform(32.0, 48.0), 2)
    rpm = round(random.uniform(240.0, 1800.0), 2)

    payload = {
        "device_id": "ESP32_MOTO_01",
        "sequence_number": seq,
        "uptime_ms": uptime,
        "recorded_at": datetime.now(timezone.utc).isoformat(),
        "temperature": temp,
        "rpm": rpm,
        "rpm_status": "MEDINDO",
        "rpm_configuration": random.choice(configs),
        "rpm_total_pulses": seq * 75,
        "nivel_fluido": "OK",
        "accelerometers": [
            {
                "id": 1,
                "channel": 0,
                "x_g": round(random.uniform(0.08, 0.25), 3),
                "y_g": round(random.uniform(0.09, 0.28), 3),
                "z_g": round(random.uniform(0.95, 1.05), 3),
                "rms_g": round(random.uniform(0.98, 1.08), 3),
            },
            {
                "id": 2,
                "channel": 1,
                "x_g": round(random.uniform(0.07, 0.22), 3),
                "y_g": round(random.uniform(0.08, 0.24), 3),
                "z_g": round(random.uniform(0.95, 1.05), 3),
                "rms_g": round(random.uniform(0.97, 1.06), 3),
            },
            {
                "id": 3,
                "channel": 2,
                "x_g": round(random.uniform(0.06, 0.20), 3),
                "y_g": round(random.uniform(0.07, 0.22), 3),
                "z_g": round(random.uniform(0.95, 1.05), 3),
                "rms_g": round(random.uniform(0.96, 1.05), 3),
            },
            {
                "id": 4,
                "channel": 3,
                "x_g": round(random.uniform(0.08, 0.25), 3),
                "y_g": round(random.uniform(0.09, 0.26), 3),
                "z_g": round(random.uniform(0.95, 1.05), 3),
                "rms_g": round(random.uniform(0.98, 1.07), 3),
            },
        ],
    }

    try:
        response = requests.post(url, json=payload, timeout=5)
        print(f"Envio seq={seq}: Temp={temp}°C | RPM={rpm} | Status Code={response.status_code}")
    except Exception as e:
        print(f"Erro na requisição: {e}")

    seq += 1
    uptime += 5000
    time.sleep(5)
