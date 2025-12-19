# 💊 Sistema de Gestión de Créditos - Farmacia

Dashboard interactivo estilo Power BI para gestión de créditos y cobranzas de farmacia, desarrollado con Python y Streamlit.

![Dashboard Preview](screenshots/01_kpis_principales.png)

## 📊 Características

- **5 KPIs Principales**:
  - Créditos Otorgados
  - Deuda Impaga
  - Cuotas Vencidas
  - Por Cobrar
  - Tasa de Morosidad con alertas automáticas

- **4 Módulos de Análisis**:
  1. 📈 Estado de Créditos
  2. 💰 Análisis de Morosidad
  3. 👥 Gestión de Clientes
  4. 📋 Detalle de Cuotas

- **Filtros Dinámicos**:
  - Rango de fechas personalizado
  - Tipo de cliente (Natural/Jurídico)
  - Estado de crédito (Activo/Cancelado/Moroso)
  - Estado de cuotas (Pendiente/Pagada/Vencida)

## 🖼️ Capturas de Pantalla

### KPIs y Alertas
![KPIs Principales](screenshots/01_kpis_principales.png)
*Vista principal con KPIs y sistema de alertas automáticas*

### Estado de Créditos
![Estado de Créditos](screenshots/02_estado_creditos.png)
*Distribución de cuotas, créditos por tipo y evolución mensual*

### Análisis de Morosidad
![Análisis de Morosidad](screenshots/03_analisis_morosidad.png)
*Top clientes morosos, antigüedad de deuda y proyección de cobros*

### Gestión de Clientes
![Clientes](screenshots/04_clientes.png)
*Ranking de clientes activos y distribución por estado*

### Detalle de Cuotas
![Detalle de Cuotas](screenshots/05_detalle_cuotas.png)
*Listado completo de cuotas vencidas con estadísticas*

## 🚀 Instalación Local

### Requisitos
- Python 3.8 o superior
- pip

### Pasos

```bash
# Clonar repositorio
git clone https://github.com/AndresJL2003/AndresJL.git
cd AndresJL

# Instalar dependencias
pip install -r requirements.txt

# Ejecutar dashboard
streamlit run farmacia_creditos_dashboard.py
```

El dashboard se abrirá automáticamente en `http://localhost:8501`

## 💾 Base de Datos SQL Server

El proyecto incluye un script SQL completo (`farmacia_creditos_database.sql`) con:

| Componente | Descripción |
|------------|-------------|
| **Tablas** | Clientes, Creditos, Cuotas, Pagos |
| **Vistas** | Resúmenes de créditos, deuda impaga, cuotas próximas |
| **SPs** | Crear créditos, registrar pagos, actualizar estados |
| **Funciones** | Calcular tasa de morosidad |
| **Datos** | 10 clientes de ejemplo (5 naturales + 5 jurídicos) |

### Ejecutar SQL Script

1. Abrir SQL Server Management Studio (SSMS)
2. Abrir archivo `farmacia_creditos_database.sql`
3. Ejecutar script completo (F5)

## 🎨 Diseño

Dashboard diseñado con principios de Power BI:
- Fuente: **Segoe UI**
- Tarjetas minimalistas con bordes de colores
- Paleta oficial de Power BI: `#118DFF`, `#E66C37`, `#6B007B`, `#D9B300`, `#E044A7`
- Sombras sutiles y espaciado consistente
- Gráficos interactivos con Plotly

## 🔧 Tecnologías

| Tecnología | Uso |
|-----------|-----|
| **Python 3.14** | Lenguaje principal |
| **Streamlit 1.40** | Framework web |
| **Pandas 2.2** | Análisis de datos |
| **Plotly 5.24** | Visualizaciones |
| **NumPy 2.1** | Computación numérica |
| **SQL Server** | Base de datos |

## 📈 Funcionalidades Detalladas

### Tab 1: Estado de Créditos
- Gráfico circular de distribución de cuotas (Pagada/Pendiente/Vencida)
- Gráfico de barras de créditos por tipo de cliente
- Gráfico de área con evolución mensual de créditos otorgados

### Tab 2: Análisis de Morosidad
- Top 10 clientes morosos con monto de deuda
- Análisis de antigüedad de deuda por rangos:
  - 0-30 días
  - 31-60 días
  - 61-90 días
  - 91-180 días
  - Más de 180 días
- Proyección de cobros próximos 90 días por semana

### Tab 3: Clientes
- Tabla de top 10 clientes activos con total de créditos y monto
- Gráfico circular de distribución de créditos por estado

### Tab 4: Detalle de Cuotas
- Tabla completa de cuotas vencidas ordenadas por días de mora
- Métricas resumidas:
  - Total de cuotas vencidas
  - Promedio de días de mora
  - Máximo de días de mora
  - Número de clientes morosos

## 📊 Datos de Ejemplo

### Clientes Naturales (5)
1. Juan Pérez García (Riesgo Bajo)
2. María López Sánchez (Riesgo Bajo)
3. Carlos Rodríguez Méndez (Riesgo Medio)
4. Ana Martínez Torres (Riesgo Bajo)
5. Luis Gómez Ramírez (Riesgo Alto)

### Clientes Jurídicos (5)
1. Farmacia Central S.A. (Riesgo Bajo)
2. Distribuidora Médica Ltda. (Riesgo Medio)
3. Clínica San Rafael (Riesgo Bajo)
4. Hospital Metropolitano (Riesgo Bajo)
5. Laboratorios Unidos S.A. (Riesgo Medio)

## 🚨 Sistema de Alertas

El dashboard incluye alertas automáticas basadas en la tasa de morosidad:

- 🔴 **Alerta Crítica**: Morosidad > 10%
- 🟡 **Advertencia**: Morosidad > 5%
- 🟢 **Normal**: Morosidad ≤ 5%

## 📁 Estructura del Proyecto

```
AndresJL/
│
├── farmacia_creditos_dashboard.py    # Dashboard principal
├── farmacia_creditos_database.sql    # Script SQL Server
├── requirements.txt                  # Dependencias Python
├── README.md                         # Documentación
├── tomar_capturas.py                 # Script para screenshots
└── screenshots/                      # Capturas de pantalla
    ├── 01_kpis_principales.png
    ├── 02_estado_creditos.png
    ├── 03_analisis_morosidad.png
    ├── 04_clientes.png
    └── 05_detalle_cuotas.png
```

## 📝 Licencia

Proyecto de código abierto bajo licencia MIT.

## 👨‍💻 Autor

Dashboard creado con Claude Code | 2025

---

⭐ **Si este proyecto te fue útil, considera darle una estrella en GitHub!**

🔗 **Repositorio**: [github.com/AndresJL2003/AndresJL](https://github.com/AndresJL2003/AndresJL)
