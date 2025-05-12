#!/usr/bin/env bash
set -euo pipefail

# Si no somos root, relanzamos con sudo
if (( EUID != 0 )); then
    exec sudo bash "$0" "$@"
fi

# Configuración inicial
IFACE="wlan0"
WPA_CONF="/etc/wpa_supplicant/wpa_supplicant.conf"
HOSTAPD_CONF="/etc/hostapd/hostapd.conf"
DNSMASQ_CONF="/etc/dnsmasq.conf"

SSID_LOCAL="Livebox6-2520-24G"
PASS_LOCAL="2qMMo5exQ4Da"
SSID_HOTSPOT="MiaHotspot"
PASS_HOTSPOT="qwerty12"
SSID_CSHARP="C#"
PASS_CSHARP="AP123456"

# Opciones del menú
CLEAN=1; LOCAL=2; CSHARP=3; OTHER=4; CREATE_AP=5; CHECK=6; EXIT=0
ENTER_DB=7 # <-- Nueva opción para entrar a la base de datos

# Funciones auxiliares
pause() {
    read -rp $'Presiona ENTER para continuar...'
}

choose_iface(){
    echo -e "Interfaces disponibles:"
    ip -o link show | awk -F': ' '/^[0-9]+/ {print $2}'
    read -rp "Elige interfaz [${IFACE}]: " tmp
    IFACE=${tmp:-$IFACE}
}

show_ip(){
    local ip4 ip6
    ip4=$(ip -4 addr show "$IFACE" 2>/dev/null | awk '/inet /{print $2}')
    ip6=$(ip -6 addr show "$IFACE" 2>/dev/null | awk '/inet6 /{print $2}' | head -1)
    echo -e "IPv4: ${ip4:-<ninguna>}"
    echo -e "IPv6: ${ip6:-<ninguna>}"
}

# Modificación en show_menu para añadir la nueva opción
show_menu(){
    clear
    echo "┌─────────────────── WiFi Manager [$IFACE] ───────────────────┐"
    show_ip
    echo "├─────────────────────────────────────────────────────────────┤"
    echo "$CLEAN) Limpiar interfaz"
    echo "$LOCAL) Conectar a Wi-Fi local"
    echo "$CSHARP) Conectar a hotspot C#"
    echo "$OTHER) Conectar a otro SSID"
    echo "$CREATE_AP) Crear AP ($SSID_HOTSPOT)"
    echo "$CHECK) Comprobar estado web (netstat + lighttpd -t)"
    echo "$ENTER_DB) Entrar a la base de datos" # <-- Añadida la nueva opción
    echo "$EXIT) Salir"
    echo "└─────────────────────────────────────────────────────────────┘"
    printf "Opción: "
}

clean_all(){
    echo -e "Limpiando interfaz $IFACE..."
    pkill -f wpa_supplicant||true
    pkill -f hostapd      ||true
    pkill -f dnsmasq      ||true
    ip addr flush dev "$IFACE"
    ip link set "$IFACE" down
    ip link set "$IFACE" up
    echo -e "Interfaz limpia."
    pause
}

connect_ssid(){
    local ssid=$1 pass=$2
    echo -e "Conectando a '$ssid' en $IFACE..."
    cat >"$WPA_CONF" <<EOF
network={
    ssid="$ssid"
    psk="$pass"
}
EOF
    if wpa_supplicant -B -i "$IFACE" -c "$WPA_CONF"; then
        udhcpc -i "$IFACE"
        echo -e "Conectado a '$ssid'."
    else
        echo -e "Error conectando a '$ssid'."
    fi
    pause
}

connect_other(){
    read -rp "SSID: " ssid
    read -rsp "PSK: " pass; echo
    connect_ssid "$ssid" "$pass"
}

start_ap(){
    echo -e "Creando AP '$SSID_HOTSPOT'..."
    cat >"$DNSMASQ_CONF" <<EOF
interface=$IFACE
dhcp-range=192.168.50.10,192.168.50.100,12h
EOF
    cat >"$HOSTAPD_CONF" <<EOF
interface=$IFACE
driver=nl80211
ssid=$SSID_HOTSPOT
hw_mode=g
channel=6
wpa=2
wpa_passphrase=$PASS_HOTSPOT
EOF
    if dnsmasq && hostapd -B "$HOSTAPD_CONF"; then
        echo -e "'$SSID_HOTSPOT' creado."
    else
        echo -e "Error al levantar AP."
    fi
    pause
}

check_web(){
    echo -e "Conexiones abiertas:"
    netstat -tuln
    echo -e "Probando config lighttpd:"
    # Intenta acceder a una URL común, ajusta si 192.168.1.13 no es correcta
    curl -I http://192.168.1.13/ || true
    lighttpd -t -f /etc/lighttpd/lighttpd.conf || true
    # Ajusta los nombres de servicio si son diferentes en tu sistema
    rc-service lighttpd restart || true
    rc-service mariadb restart || true
    pause
}

# Nueva función para entrar a la base de datos
enter_database(){
    local db_user db_host db_name
    read -rp "Usuario de BD [root]: " tmp_user
    db_user=${tmp_user:-root}

    read -rsp "Contraseña de BD: " db_pass; echo

    read -rp "Host de BD [localhost]: " tmp_host
    db_host=${tmp_host:-localhost}

    read -rp "Nombre de la Base de Datos (opcional): " db_name

    echo -e "Conectando a la base de datos..."

    # Usamos 'mysql' para conectarnos. Si se proporciona nombre de BD, lo usamos.
    if [[ -n "$db_name" ]]; then
        mysql -h "$db_host" -u "$db_user" -p"$db_pass" "$db_name"
    else
        mysql -h "$db_host" -u "$db_user" -p"$db_pass"
    fi

    echo -e "Sesión de base de datos terminada."
    pause
}

# Modificación en el bucle while para manejar la nueva opción
while true; do
    show_menu
    read -r opt
    case $opt in
        $CLEAN)         clean_all;;
        $LOCAL)         clean_all; choose_iface; connect_ssid "$SSID_LOCAL" "$PASS_LOCAL";;
        $CSHARP)        clean_all; choose_iface; connect_ssid "$SSID_CSHARP" "$PASS_CSHARP";;
        $OTHER)         clean_all; choose_iface; connect_other;;
        $CREATE_AP)     choose_iface; start_ap;;
        $CHECK)         check_web;;
        $ENTER_DB)      enter_database;;
        $EXIT)          clear; exit 0;;
        *)              echo -e "Opción inválida."; sleep 1;;
    esac
done