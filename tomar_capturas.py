"""
Script para tomar capturas de pantalla del dashboard
"""
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from webdriver_manager.chrome import ChromeDriverManager
import time
import os

# Configurar Chrome en modo headless
chrome_options = Options()
chrome_options.add_argument('--headless=new')
chrome_options.add_argument('--window-size=1920,1200')
chrome_options.add_argument('--disable-gpu')
chrome_options.add_argument('--no-sandbox')
chrome_options.add_argument('--disable-dev-shm-usage')

# Crear carpeta para screenshots
os.makedirs('screenshots', exist_ok=True)

# Iniciar navegador con webdriver-manager
service = Service(ChromeDriverManager().install())
driver = webdriver.Chrome(service=service, options=chrome_options)

try:
    # Ir al dashboard
    driver.get('http://localhost:8501')

    # Esperar a que cargue
    time.sleep(10)

    # Captura 1: Vista principal con KPIs
    driver.save_screenshot('screenshots/01_kpis_principales.png')
    print("[OK] Captura 1: KPIs principales")

    # Scroll para ver más contenido
    driver.execute_script("window.scrollTo(0, 500)")
    time.sleep(2)
    driver.save_screenshot('screenshots/02_estado_creditos.png')
    print("[OK] Captura 2: Estado de creditos")

    # Click en tab 2
    tabs = driver.find_elements(By.CSS_SELECTOR, '[data-baseweb="tab"]')
    if len(tabs) > 1:
        tabs[1].click()
        time.sleep(3)
        driver.save_screenshot('screenshots/03_analisis_morosidad.png')
        print("[OK] Captura 3: Analisis de morosidad")

    # Click en tab 3
    if len(tabs) > 2:
        tabs[2].click()
        time.sleep(3)
        driver.save_screenshot('screenshots/04_clientes.png')
        print("[OK] Captura 4: Clientes")

    # Click en tab 4
    if len(tabs) > 3:
        tabs[3].click()
        time.sleep(3)
        driver.save_screenshot('screenshots/05_detalle_cuotas.png')
        print("[OK] Captura 5: Detalle de cuotas")

    print("\n[SUCCESS] Todas las capturas tomadas exitosamente!")
    print("Revisa la carpeta 'screenshots/'")

except Exception as e:
    print(f"[ERROR] {e}")
    print("Asegúrate de que:")
    print("1. El dashboard esté corriendo en http://localhost:8501")
    print("2. Chrome esté instalado")
    print("3. ChromeDriver esté instalado (pip install selenium)")

finally:
    driver.quit()
