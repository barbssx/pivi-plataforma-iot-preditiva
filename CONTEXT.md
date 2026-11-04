# CONTEXT.md - Industrial IoT Domain Knowledge Base

## 🎯 System Engineering Scope
This platform delivers real-time observability, edge computing optimization, and condition-based predictive maintenance to eradicate unplanned production downtime, operational hazards, and catastrophic failures in **induction electric motors**.

- **Target Hardware Asset:** Torno Revólver AMA TR4 (Mechanical machining and continuous cyclic cutting stress).
- **Deployment Location:** Steelbras Antenas Industry & Commerce LTDA (Matriz: Presidente Prudente - SP).

---

## 📊 Telemetry Specifications & Telemetry Thresholds

The ingestion engine processes incoming signals and dynamically classifies the asset state across three distinct operational regimes based on structural safety standards:

### 🌡️ Thermal Monitoring (Degrees Celsius)
- **NORMAL State:** $\le$ 60.00 °C  *(Optimal mechanical lubrication performance)*.
- **ATTENTION State:** > 60.00 °C and $\le$ 75.00 °C *(Indicative of abnormal friction or lubrication decay)*.
- **CRITICAL State:** > 75.00 °C *(Imminent bearing failure / Automatic shutdown alert required)*.

### 🌀 Mechanical Vibration Monitoring (Triaxial Acceleration)
Vibration analysis maps continuous high-frequency acceleration metrics across all three Cartesian axes (**X, Y, Z**) simultaneously:
- **NORMAL State:** $\le$ 1.50 m/s² *(Structural balance and alignment within safe tolerances)*.
- **ATTENTION State:** > 1.50 m/s² and $\le$ 2.50 m/s² *(Presence of structural looseness, unbalance, or axis misalignment)*.
- **CRITICAL State:** > 2.50 m/s² *(Severe mechanical anomaly. Structural integrity compromised)*.

### 💧 Auxiliary Variables
- **Fluid Level:** Binary discrete input mapping critical cooling/lubrication thresholds (`OK` vs. `FALHA`).
- **Axis Rotation Speed (RPM):** Used as context metadata to map mechanical stress levels relative to cutting speeds.

---

## 🌐 Hybrid Layer Architecture (Edge-to-Cloud)

1. **Edge Node Layer (ESP32):** Performs physical signal acquisition, noise filtering, and edge computing feature extraction (Calculating Mean, Max, and RMS values). This reduces total network packet overhead before cloud ingestion.
2. **Cloud/Ingestion Layer (Laravel REST API):** Handles data persistence, authentication validations, multi-tenant technician roles, and historic database logging.
3. **Reactive Dashboard Layer (Vue 3 + Inertia):** Automatically refreshes without full page reloads via rapid poll loops targeting the latest data states, rendering historical triaxial trendlines cleanly.
