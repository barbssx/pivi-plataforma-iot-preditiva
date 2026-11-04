import requests
import time
import random

url = "http://localhost:8000/api/sensor-readings"

print("Iniciando simulação do nó IoT de Vibração e Temperatura...")

while True:
    payload = {
        "device_id": "ESP32_MOTO_01",
        "temperature": round(random.uniform(35.0, 80.0), 2),
        "vibration_x": round(random.uniform(0.1, 3.0), 3),
        "vibration_y": round(random.uniform(0.1, 3.0), 3),
        "vibration_z": round(random.uniform(0.1, 3.0), 3)
    }

    try:
        response = requests.post(url, json=payload)
        print(f"Dados enviados: Temp={payload['temperature']}°C | Status Code: {response.status_code}")
    except Exception as e:
        print(f"Erro na requisição: {e}")

    time.sleep(3)
