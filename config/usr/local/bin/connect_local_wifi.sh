#!/bin/bash
# Script para conectar a la red WiFi local al inicio del sistema
# sudo rc-update add connect_local_wifi default
# Configuración de la red a la que conectar
IFACE="wlan0"
SSID="Livebox6-2520-24G"
PASS="2qMMo5exQ4Da"
WPA_CONF="/etc/wpa_supplicant/wpa_supplicant-${IFACE}.conf" # Usar un archivo por interfaz es buena práctica
LOG_FILE="/var/log/connect_local_wifi.log" # Archivo para registrar la salida

# Asegurarse de que el directorio de logs existe
mkdir -p $(dirname "$LOG_FILE")

# Redirigir la salida a un archivo de log
exec > >(tee -a "$LOG_FILE") 2>&1

echo "--- $(date) ---"
echo "Iniciando conexión a '$SSID' en $IFACE"

# Limpiar cualquier proceso wpa_supplicant existente para esta interfaz
pkill -f "wpa_supplicant -i ${IFACE}" || true
sleep 1 # Dar tiempo para que el proceso termine

# Crear el archivo de configuración de wpa_supplicant
cat >"$WPA_CONF" <<EOF
ctrl_interface=/var/run/wpa_supplicant
ctrl_interface_group=0
update_config=1

network={
    ssid="$SSID"
    psk="$PASS"
    # Puedes añadir otras opciones si son necesarias para tu red
    # key_mgmt=WPA-PSK
    # proto=RSN WPA
    # pairwise=CCMP TKIP
    # group=CCMP TKIP
    # priority=10
}
EOF
echo "Archivo de configuración WPA creado: $WPA_CONF"

# Asegurarse de que el archivo de configuración tiene permisos restrictivos
chmod 600 "$WPA_CONF"

# Intentar conectar con wpa_supplicant en segundo plano
echo "Ejecutando wpa_supplicant en segundo plano..."
if wpa_supplicant -B -i "$IFACE" -c "$WPA_CONF"; then
    echo "wpa_supplicant iniciado con éxito."
    sleep 5 # Esperar un poco a que la conexión se establezca

    # Intentar obtener una dirección IP con udhcpc (o dhclient si usas otro)
    echo "Intentando obtener dirección IP con udhcpc..."
    if udhcpc -i "$IFACE"; then
        echo "Conexión a '$SSID' establecida y dirección IP obtenida."
    else
        echo "wpa_supplicant se conectó, pero falló al obtener dirección IP con udhcpc."
    fi
else
    echo "Error al iniciar wpa_supplicant o conectar a '$SSID'."
fi

echo "--- Fin de la ejecución ---"

exit 0