"""
=====================================================
DASHBOARD DE CRÉDITOS Y DEUDA IMPAGA - FARMACIA
Tipo: Power BI en Python con Streamlit
Autor: Claude Code
Fecha: 2025-12-18
=====================================================
"""

import streamlit as st
import pandas as pd
import plotly.express as px
import plotly.graph_objects as go
from datetime import datetime, timedelta
import numpy as np

# Configuración de la página
st.set_page_config(
    page_title="Farmacia - Créditos Dashboard",
    page_icon="💊",
    layout="wide",
    initial_sidebar_state="expanded"
)

# CSS personalizado estilo Power BI
st.markdown("""
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;600;700&display=swap');

    .main {
        background-color: #f2f2f2;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    header {visibility: hidden;}
    #MainMenu {visibility: hidden;}
    footer {visibility: hidden;}

    .powerbi-header {
        background-color: #ffffff;
        padding: 15px 20px;
        margin-bottom: 15px;
        border-bottom: 1px solid #e0e0e0;
    }

    .powerbi-header h1 {
        color: #252423;
        margin: 0;
        font-size: 1.5rem;
        font-weight: 600;
        font-family: 'Segoe UI', sans-serif;
    }

    .powerbi-card {
        background-color: #ffffff;
        padding: 18px 20px;
        border-radius: 2px;
        box-shadow: 0 1.6px 3.6px 0 rgba(0,0,0,0.132), 0 0.3px 0.9px 0 rgba(0,0,0,0.108);
        min-height: 100px;
        height: auto;
        position: relative;
        border-left: 3px solid;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .powerbi-card h3 {
        margin: 0 0 6px 0;
        font-size: 0.7rem;
        font-weight: 600;
        color: #605e5c;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        line-height: 1;
    }

    .powerbi-card h2 {
        margin: 0;
        font-size: 1.8rem;
        font-weight: 600;
        color: #252423;
        font-family: 'Segoe UI', sans-serif;
        line-height: 1.2;
    }

    .card-creditos { border-left-color: #118DFF; }
    .card-impaga { border-left-color: #D64550; }
    .card-vencido { border-left-color: #E66C37; }
    .card-cobrar { border-left-color: #6B007B; }
    .card-morosidad { border-left-color: #D9B300; }

    .stTabs [data-baseweb="tab-list"] {
        gap: 0;
        background-color: transparent;
        padding: 0;
        border-bottom: 1px solid #e0e0e0;
    }

    .stTabs [data-baseweb="tab"] {
        height: 44px;
        padding: 0 16px;
        background-color: transparent;
        border-radius: 0;
        font-weight: 400;
        font-size: 0.875rem;
        color: #605e5c;
        border-bottom: 2px solid transparent;
    }

    .stTabs [aria-selected="true"] {
        background-color: transparent;
        color: #252423;
        border-bottom: 2px solid #0078d4;
        font-weight: 600;
    }

    h3 {
        font-family: 'Segoe UI', sans-serif;
        font-weight: 600;
        color: #252423;
        font-size: 1rem;
    }

    .alert-red {
        background-color: #fff0f0;
        border-left: 4px solid #D64550;
        padding: 10px;
        margin: 10px 0;
    }

    .alert-yellow {
        background-color: #fffbf0;
        border-left: 4px solid #D9B300;
        padding: 10px;
        margin: 10px 0;
    }

    .alert-green {
        background-color: #f0fff0;
        border-left: 4px solid #28a745;
        padding: 10px;
        margin: 10px 0;
    }
    </style>
    """, unsafe_allow_html=True)


# =====================================================
# GENERACIÓN DE DATOS DE EJEMPLO
# =====================================================

@st.cache_data
def generate_sample_data():
    """Genera datos de ejemplo para el sistema de créditos"""

    np.random.seed(42)

    # Fechas
    today = datetime.now()
    start_date = today - timedelta(days=365)

    # Clientes Naturales (5)
    clientes_natural = [
        {'id': 1, 'nombre': 'Juan Pérez García', 'tipo': 'Natural', 'nit': '12345001', 'telefono': '555-1001', 'riesgo': 'Bajo'},
        {'id': 2, 'nombre': 'María López Sánchez', 'tipo': 'Natural', 'nit': '12345002', 'telefono': '555-1002', 'riesgo': 'Bajo'},
        {'id': 3, 'nombre': 'Carlos Rodríguez Méndez', 'tipo': 'Natural', 'nit': '12345003', 'telefono': '555-1003', 'riesgo': 'Medio'},
        {'id': 4, 'nombre': 'Ana Martínez Torres', 'tipo': 'Natural', 'nit': '12345004', 'telefono': '555-1004', 'riesgo': 'Bajo'},
        {'id': 5, 'nombre': 'Luis Gómez Ramírez', 'tipo': 'Natural', 'nit': '12345005', 'telefono': '555-1005', 'riesgo': 'Alto'}
    ]

    # Clientes Jurídicos (5)
    clientes_juridico = [
        {'id': 6, 'nombre': 'Farmacia Central S.A.', 'tipo': 'Jurídico', 'nit': '987654001', 'telefono': '555-2001', 'razon_social': 'Farmacia Central Sociedad Anónima', 'riesgo': 'Bajo'},
        {'id': 7, 'nombre': 'Distribuidora Médica Ltda.', 'tipo': 'Jurídico', 'nit': '987654002', 'telefono': '555-2002', 'razon_social': 'Distribuidora Médica Limitada', 'riesgo': 'Medio'},
        {'id': 8, 'nombre': 'Clínica San Rafael', 'tipo': 'Jurídico', 'nit': '987654003', 'telefono': '555-2003', 'razon_social': 'Clínica San Rafael S.A.', 'riesgo': 'Bajo'},
        {'id': 9, 'nombre': 'Hospital Metropolitano', 'tipo': 'Jurídico', 'nit': '987654004', 'telefono': '555-2004', 'razon_social': 'Hospital Metropolitano S.A.', 'riesgo': 'Bajo'},
        {'id': 10, 'nombre': 'Laboratorios Unidos S.A.', 'tipo': 'Jurídico', 'nit': '987654005', 'telefono': '555-2005', 'razon_social': 'Laboratorios Unidos Sociedad Anónima', 'riesgo': 'Medio'}
    ]

    df_clientes = pd.DataFrame(clientes_natural + clientes_juridico)

    # Créditos
    creditos_data = []
    for i in range(200):
        cliente = df_clientes.sample(1).iloc[0]
        monto_capital = np.random.uniform(500, 15000)
        tasa_interes = np.random.uniform(5, 18)
        fecha_desembolso = start_date + timedelta(days=np.random.randint(0, 365))
        plazo_meses = np.random.choice([3, 6, 12, 18, 24])

        creditos_data.append({
            'id_credito': i + 1,
            'id_cliente': cliente['id'],
            'nombre_cliente': cliente['nombre'],
            'tipo_cliente': cliente['tipo'],
            'monto_capital': monto_capital,
            'tasa_interes': tasa_interes,
            'fecha_desembolso': fecha_desembolso,
            'plazo_meses': plazo_meses,
            'estado': np.random.choice(['Activo', 'Cancelado', 'Moroso'], p=[0.6, 0.25, 0.15])
        })

    df_creditos = pd.DataFrame(creditos_data)

    # Cuotas
    cuotas_data = []
    cuota_id = 1

    for _, credito in df_creditos.iterrows():
        monto_cuota = credito['monto_capital'] / credito['plazo_meses']
        interes_mensual = (credito['monto_capital'] * credito['tasa_interes'] / 100) / 12

        for mes in range(credito['plazo_meses']):
            fecha_programada = credito['fecha_desembolso'] + timedelta(days=30 * (mes + 1))

            # Determinar si está pagada, vencida o pendiente
            if fecha_programada < today - timedelta(days=30):
                if credito['estado'] == 'Moroso' and np.random.random() > 0.3:
                    estado_cuota = 'Vencida'
                    fecha_pago = None
                    dias_mora = (today - fecha_programada).days
                else:
                    estado_cuota = 'Pagada'
                    fecha_pago = fecha_programada + timedelta(days=np.random.randint(-5, 10))
                    dias_mora = max(0, (fecha_pago - fecha_programada).days)
            elif fecha_programada < today:
                if np.random.random() > 0.7:
                    estado_cuota = 'Vencida'
                    fecha_pago = None
                    dias_mora = (today - fecha_programada).days
                else:
                    estado_cuota = 'Pagada'
                    fecha_pago = fecha_programada + timedelta(days=np.random.randint(-3, 5))
                    dias_mora = max(0, (fecha_pago - fecha_programada).days)
            else:
                estado_cuota = 'Pendiente'
                fecha_pago = None
                dias_mora = 0

            cuotas_data.append({
                'id_cuota': cuota_id,
                'id_credito': credito['id_credito'],
                'id_cliente': credito['id_cliente'],
                'nombre_cliente': credito['nombre_cliente'],
                'tipo_cliente': credito['tipo_cliente'],
                'numero_cuota': mes + 1,
                'monto_capital': monto_cuota,
                'interes': interes_mensual,
                'monto_total': monto_cuota + interes_mensual,
                'fecha_programada': fecha_programada,
                'fecha_pago': fecha_pago,
                'estado': estado_cuota,
                'dias_mora': dias_mora
            })
            cuota_id += 1

    df_cuotas = pd.DataFrame(cuotas_data)

    return df_clientes, df_creditos, df_cuotas


# =====================================================
# CARGAR DATOS
# =====================================================

with st.spinner('Cargando datos de créditos...'):
    df_clientes, df_creditos, df_cuotas = generate_sample_data()


# =====================================================
# SIDEBAR - FILTROS
# =====================================================

st.sidebar.header("🔍 FILTROS")

# Filtro de fechas
st.sidebar.subheader("Período de Análisis")
fecha_inicio = st.sidebar.date_input(
    "Desde",
    value=datetime.now() - timedelta(days=180),
    max_value=datetime.now()
)
fecha_fin = st.sidebar.date_input(
    "Hasta",
    value=datetime.now(),
    max_value=datetime.now()
)

# Filtro de tipo de cliente
st.sidebar.subheader("Tipo de Cliente")
tipo_cliente = st.sidebar.multiselect(
    "Seleccionar tipo",
    options=['Natural', 'Jurídico'],
    default=['Natural', 'Jurídico']
)

# Filtro de estado de crédito
st.sidebar.subheader("Estado de Crédito")
estado_credito = st.sidebar.multiselect(
    "Seleccionar estado",
    options=['Activo', 'Cancelado', 'Moroso'],
    default=['Activo', 'Moroso']
)

# Filtro de estado de cuota
st.sidebar.subheader("Estado de Cuotas")
estado_cuota = st.sidebar.multiselect(
    "Seleccionar estado",
    options=['Pendiente', 'Pagada', 'Vencida'],
    default=['Pendiente', 'Pagada', 'Vencida']
)


# =====================================================
# APLICAR FILTROS
# =====================================================

# Filtrar créditos
df_creditos_filtered = df_creditos[
    (df_creditos['tipo_cliente'].isin(tipo_cliente)) &
    (df_creditos['estado'].isin(estado_credito)) &
    (df_creditos['fecha_desembolso'] >= pd.to_datetime(fecha_inicio)) &
    (df_creditos['fecha_desembolso'] <= pd.to_datetime(fecha_fin))
].copy()

# Filtrar cuotas
df_cuotas_filtered = df_cuotas[
    (df_cuotas['tipo_cliente'].isin(tipo_cliente)) &
    (df_cuotas['estado'].isin(estado_cuota))
].copy()


# =====================================================
# HEADER DEL DASHBOARD
# =====================================================

st.markdown("""
    <div class="powerbi-header">
        <h1>💊 Farmacia - Sistema de Gestión de Créditos y Cobranzas</h1>
    </div>
""", unsafe_allow_html=True)


# =====================================================
# KPIs PRINCIPALES
# =====================================================

col1, col2, col3, col4, col5 = st.columns(5)

# Calcular métricas
total_creditos = df_creditos_filtered['monto_capital'].sum()
deuda_impaga = df_cuotas_filtered[df_cuotas_filtered['estado'] == 'Vencida']['monto_total'].sum()
cuotas_vencidas = len(df_cuotas_filtered[df_cuotas_filtered['estado'] == 'Vencida'])
por_cobrar = df_cuotas_filtered[df_cuotas_filtered['estado'] == 'Pendiente']['monto_total'].sum()
tasa_morosidad = (deuda_impaga / total_creditos * 100) if total_creditos > 0 else 0

with col1:
    st.markdown(f"""
        <div class="powerbi-card card-creditos">
            <h3>Créditos Otorgados</h3>
            <h2>${total_creditos:,.2f}</h2>
        </div>
    """, unsafe_allow_html=True)

with col2:
    st.markdown(f"""
        <div class="powerbi-card card-impaga">
            <h3>Deuda Impaga</h3>
            <h2>${deuda_impaga:,.2f}</h2>
        </div>
    """, unsafe_allow_html=True)

with col3:
    st.markdown(f"""
        <div class="powerbi-card card-vencido">
            <h3>Cuotas Vencidas</h3>
            <h2>{cuotas_vencidas:,}</h2>
        </div>
    """, unsafe_allow_html=True)

with col4:
    st.markdown(f"""
        <div class="powerbi-card card-cobrar">
            <h3>Por Cobrar</h3>
            <h2>${por_cobrar:,.2f}</h2>
        </div>
    """, unsafe_allow_html=True)

with col5:
    st.markdown(f"""
        <div class="powerbi-card card-morosidad">
            <h3>Tasa Morosidad</h3>
            <h2>{tasa_morosidad:.1f}%</h2>
        </div>
    """, unsafe_allow_html=True)

st.markdown("<br>", unsafe_allow_html=True)

# Alertas
if tasa_morosidad > 10:
    st.markdown(f"""
        <div class="alert-red">
            <strong>⚠️ ALERTA CRÍTICA:</strong> La tasa de morosidad es del {tasa_morosidad:.1f}%, superando el límite aceptable del 10%.
        </div>
    """, unsafe_allow_html=True)
elif tasa_morosidad > 5:
    st.markdown(f"""
        <div class="alert-yellow">
            <strong>⚡ ATENCIÓN:</strong> La tasa de morosidad es del {tasa_morosidad:.1f}%. Se recomienda monitorear de cerca.
        </div>
    """, unsafe_allow_html=True)


# =====================================================
# SISTEMA DE PESTAÑAS
# =====================================================

tab1, tab2, tab3, tab4 = st.tabs(["📊 Estado de Créditos", "💰 Análisis de Morosidad", "👥 Clientes", "📋 Detalle de Cuotas"])

# =====================================================
# TAB 1: ESTADO DE CRÉDITOS
# =====================================================

with tab1:
    col1, col2 = st.columns(2)

    with col1:
        st.subheader("Estado de Cuotas")

        estado_cuotas = df_cuotas_filtered.groupby('estado').agg({
            'monto_total': 'sum',
            'id_cuota': 'count'
        }).reset_index()
        estado_cuotas.columns = ['Estado', 'Monto', 'Cantidad']

        fig = px.pie(
            estado_cuotas,
            values='Monto',
            names='Estado',
            hole=0.4,
            color='Estado',
            color_discrete_map={
                'Pagada': '#28a745',
                'Pendiente': '#ffc107',
                'Vencida': '#dc3545'
            }
        )

        fig.update_traces(textposition='inside', textinfo='percent+label', textfont_size=12)
        fig.update_layout(
            height=350,
            plot_bgcolor='white',
            paper_bgcolor='white',
            font=dict(family='Segoe UI', size=11, color='#252423')
        )

        st.plotly_chart(fig, use_container_width=True)

    with col2:
        st.subheader("Créditos por Tipo de Cliente")

        creditos_tipo = df_creditos_filtered.groupby('tipo_cliente').agg({
            'monto_capital': 'sum'
        }).reset_index()

        fig = px.bar(
            creditos_tipo,
            x='tipo_cliente',
            y='monto_capital',
            text='monto_capital',
            color='tipo_cliente',
            color_discrete_sequence=['#118DFF', '#E66C37']
        )

        fig.update_traces(
            texttemplate='$%{text:,.0f}',
            textposition='inside',
            textfont=dict(size=12, color='white')
        )
        fig.update_layout(
            height=350,
            xaxis=dict(title="Tipo de Cliente", showgrid=False),
            yaxis=dict(title="Monto Total ($)", showgrid=True, gridcolor='#f0f0f0'),
            showlegend=False,
            plot_bgcolor='white',
            paper_bgcolor='white',
            font=dict(family='Segoe UI', size=11, color='#252423')
        )

        st.plotly_chart(fig, use_container_width=True)

    # Evolución de créditos
    st.subheader("Evolución de Créditos Otorgados")

    df_creditos_filtered['mes'] = df_creditos_filtered['fecha_desembolso'].dt.to_period('M').astype(str)
    evolucion = df_creditos_filtered.groupby('mes').agg({
        'monto_capital': 'sum',
        'id_credito': 'count'
    }).reset_index()
    evolucion.columns = ['Mes', 'Monto', 'Cantidad']

    fig = go.Figure()

    fig.add_trace(go.Scatter(
        x=evolucion['Mes'],
        y=evolucion['Monto'],
        name='Monto',
        fill='tozeroy',
        line=dict(color='#118DFF', width=2),
        fillcolor='rgba(17, 141, 255, 0.2)'
    ))

    fig.update_layout(
        height=350,
        xaxis=dict(title='Mes', showgrid=False),
        yaxis=dict(title='Monto ($)', showgrid=True, gridcolor='#f0f0f0'),
        hovermode='x unified',
        plot_bgcolor='white',
        paper_bgcolor='white',
        font=dict(family='Segoe UI', size=11, color='#252423')
    )

    st.plotly_chart(fig, use_container_width=True)


# =====================================================
# TAB 2: ANÁLISIS DE MOROSIDAD
# =====================================================

with tab2:
    col1, col2 = st.columns(2)

    with col1:
        st.subheader("Top 10 Clientes Morosos")

        morosos = df_cuotas_filtered[df_cuotas_filtered['estado'] == 'Vencida'].groupby('nombre_cliente').agg({
            'monto_total': 'sum',
            'id_cuota': 'count'
        }).reset_index().sort_values('monto_total', ascending=True).tail(10)
        morosos.columns = ['Cliente', 'Deuda', 'Cuotas']

        fig = px.bar(
            morosos,
            x='Deuda',
            y='Cliente',
            orientation='h',
            text='Deuda',
            color_discrete_sequence=['#D64550']
        )

        fig.update_traces(
            texttemplate='$%{text:,.0f}',
            textposition='inside',
            textfont=dict(size=10, color='white')
        )
        fig.update_layout(
            height=400,
            xaxis=dict(title="Deuda ($)", showgrid=True, gridcolor='#f0f0f0'),
            yaxis=dict(title=""),
            plot_bgcolor='white',
            paper_bgcolor='white',
            font=dict(family='Segoe UI', size=11, color='#252423')
        )

        st.plotly_chart(fig, use_container_width=True)

    with col2:
        st.subheader("Antigüedad de Deuda")

        vencidas = df_cuotas_filtered[df_cuotas_filtered['estado'] == 'Vencida'].copy()

        if len(vencidas) > 0:
            vencidas['rango_dias'] = pd.cut(
                vencidas['dias_mora'],
                bins=[0, 30, 60, 90, 180, 999],
                labels=['0-30 días', '31-60 días', '61-90 días', '91-180 días', '>180 días']
            )

            antiguedad = vencidas.groupby('rango_dias').agg({
                'monto_total': 'sum'
            }).reset_index()

            fig = px.bar(
                antiguedad,
                x='rango_dias',
                y='monto_total',
                text='monto_total',
                color='monto_total',
                color_continuous_scale=['#ffc107', '#E66C37', '#D64550']
            )

            fig.update_traces(
                texttemplate='$%{text:,.0f}',
                textposition='outside'
            )
            fig.update_layout(
                height=400,
                xaxis=dict(title="Días de Mora", showgrid=False),
                yaxis=dict(title="Monto ($)", showgrid=True, gridcolor='#f0f0f0'),
                showlegend=False,
                coloraxis_showscale=False,
                plot_bgcolor='white',
                paper_bgcolor='white',
                font=dict(family='Segoe UI', size=11, color='#252423')
            )

            st.plotly_chart(fig, use_container_width=True)
        else:
            st.info("No hay cuotas vencidas en el período seleccionado")

    # Proyección de cobros
    st.subheader("Proyección de Cobros Próximos 90 Días")

    hoy = datetime.now()
    proximos_90 = hoy + timedelta(days=90)

    proximas_cuotas = df_cuotas_filtered[
        (df_cuotas_filtered['estado'] == 'Pendiente') &
        (df_cuotas_filtered['fecha_programada'] <= proximos_90)
    ].copy()

    if len(proximas_cuotas) > 0:
        proximas_cuotas['semana'] = proximas_cuotas['fecha_programada'].dt.to_period('W').astype(str)
        proyeccion = proximas_cuotas.groupby('semana').agg({
            'monto_total': 'sum'
        }).reset_index()

        fig = go.Figure()

        fig.add_trace(go.Bar(
            x=proyeccion['semana'],
            y=proyeccion['monto_total'],
            name='Cobros Proyectados',
            marker_color='#6B007B'
        ))

        fig.update_layout(
            height=300,
            xaxis=dict(title='Semana', showgrid=False),
            yaxis=dict(title='Monto ($)', showgrid=True, gridcolor='#f0f0f0'),
            plot_bgcolor='white',
            paper_bgcolor='white',
            font=dict(family='Segoe UI', size=11, color='#252423')
        )

        st.plotly_chart(fig, use_container_width=True)
    else:
        st.info("No hay cobros proyectados para los próximos 90 días")


# =====================================================
# TAB 3: CLIENTES
# =====================================================

with tab3:
    st.subheader("Análisis de Clientes por Categoría")

    col1, col2 = st.columns(2)

    with col1:
        # Clientes con más créditos activos
        st.markdown("**Top Clientes Activos**")

        clientes_activos = df_creditos_filtered[df_creditos_filtered['estado'] == 'Activo'].groupby('nombre_cliente').agg({
            'id_credito': 'count',
            'monto_capital': 'sum'
        }).reset_index().sort_values('monto_capital', ascending=False).head(10)
        clientes_activos.columns = ['Cliente', 'Créditos', 'Monto Total']

        clientes_activos['Créditos'] = clientes_activos['Créditos'].astype(int)
        clientes_activos['Monto Total'] = clientes_activos['Monto Total'].apply(lambda x: f"${x:,.2f}")

        st.dataframe(clientes_activos, use_container_width=True, height=350)

    with col2:
        # Distribución de créditos por estado
        st.markdown("**Estado de Créditos**")

        estado_dist = df_creditos_filtered.groupby('estado').agg({
            'id_credito': 'count'
        }).reset_index()
        estado_dist.columns = ['Estado', 'Cantidad']

        fig = px.pie(
            estado_dist,
            values='Cantidad',
            names='Estado',
            hole=0.4,
            color='Estado',
            color_discrete_map={
                'Activo': '#118DFF',
                'Cancelado': '#28a745',
                'Moroso': '#D64550'
            }
        )

        fig.update_traces(textposition='inside', textinfo='percent+label')
        fig.update_layout(
            height=350,
            plot_bgcolor='white',
            paper_bgcolor='white',
            font=dict(family='Segoe UI', size=11, color='#252423')
        )

        st.plotly_chart(fig, use_container_width=True)


# =====================================================
# TAB 4: DETALLE DE CUOTAS
# =====================================================

with tab4:
    st.subheader("Detalle de Cuotas Vencidas")

    cuotas_vencidas_detalle = df_cuotas_filtered[
        df_cuotas_filtered['estado'] == 'Vencida'
    ][['nombre_cliente', 'tipo_cliente', 'numero_cuota', 'monto_total', 'fecha_programada', 'dias_mora']].copy()

    cuotas_vencidas_detalle = cuotas_vencidas_detalle.sort_values('dias_mora', ascending=False)

    # Formatear
    cuotas_vencidas_detalle['monto_total'] = cuotas_vencidas_detalle['monto_total'].apply(lambda x: f"${x:,.2f}")
    cuotas_vencidas_detalle['fecha_programada'] = cuotas_vencidas_detalle['fecha_programada'].dt.strftime('%Y-%m-%d')

    cuotas_vencidas_detalle.columns = ['Cliente', 'Tipo', 'Cuota #', 'Monto', 'Fecha Vencimiento', 'Días Mora']

    st.dataframe(cuotas_vencidas_detalle, use_container_width=True, height=400)

    # Resumen estadístico
    st.markdown("---")
    col1, col2, col3, col4 = st.columns(4)

    with col1:
        st.metric("Total Cuotas Vencidas", len(cuotas_vencidas_detalle))

    with col2:
        promedio_mora = df_cuotas_filtered[df_cuotas_filtered['estado'] == 'Vencida']['dias_mora'].mean()
        st.metric("Promedio Días Mora", f"{promedio_mora:.0f} días")

    with col3:
        max_mora = df_cuotas_filtered[df_cuotas_filtered['estado'] == 'Vencida']['dias_mora'].max()
        st.metric("Máxima Mora", f"{max_mora:.0f} días")

    with col4:
        clientes_morosos = df_cuotas_filtered[df_cuotas_filtered['estado'] == 'Vencida']['nombre_cliente'].nunique()
        st.metric("Clientes Morosos", clientes_morosos)


# =====================================================
# FOOTER
# =====================================================

st.markdown("---")
st.markdown("""
    <div style='text-align: center; color: gray;'>
        <p>💊 Farmacia - Sistema de Gestión de Créditos</p>
    </div>
""", unsafe_allow_html=True)
