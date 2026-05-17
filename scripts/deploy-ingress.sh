#!/usr/bin/env sh

set -eu

PROJECT_ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
cd "$PROJECT_ROOT"

read_env() {
    if [ -f src/.env ]; then
        grep -E "^$1=" src/.env | tail -n 1 | cut -d '=' -f 2- | tr -d "\"'" || true
    fi
}

detect_node_ip() {
    if command -v ip >/dev/null 2>&1; then
        ip route get 1.1.1.1 2>/dev/null | awk '{
            for (i = 1; i <= NF; i++) {
                if ($i == "src") {
                    print $(i + 1)
                    exit
                }
            }
        }'
    fi
}

APP_DOMAIN=${APP_DOMAIN:-$(read_env APP_DOMAIN)}
LITRADESA_HOST=${LITRADESA_HOST:-$APP_DOMAIN}
LITRADESA_NAMESPACE=${LITRADESA_NAMESPACE:-litradesa}
LITRADESA_NODE_IP=${LITRADESA_NODE_IP:-$(detect_node_ip)}
LITRADESA_CLUSTER_ISSUER=${LITRADESA_CLUSTER_ISSUER:-germatech-letsencrypt}
LITRADESA_TLS_SECRET=${LITRADESA_TLS_SECRET:-litradesa-tls-secret}
WEB_HOST_PORT=${WEB_HOST_PORT:-18080}
REVERB_HOST_PORT=${REVERB_HOST_PORT:-18081}

if [ -z "$LITRADESA_HOST" ] || [ "$LITRADESA_HOST" = "litradesa.example.com" ]; then
    echo "Set APP_DOMAIN in src/.env or pass LITRADESA_HOST=litradesa.germadev.my.id."
    exit 1
fi

if [ -z "$LITRADESA_NODE_IP" ]; then
    echo "Could not detect node IP. Pass LITRADESA_NODE_IP=<k3s-node-ip>."
    exit 1
fi

if ! command -v kubectl >/dev/null 2>&1; then
    echo "kubectl is required to apply the LitraDesa ingress manifest."
    exit 1
fi

tmp_file=$(mktemp)
trap 'rm -f "$tmp_file"' EXIT

sed \
    -e "s#__NAMESPACE__#$LITRADESA_NAMESPACE#g" \
    -e "s#__HOST__#$LITRADESA_HOST#g" \
    -e "s#__NODE_IP__#$LITRADESA_NODE_IP#g" \
    -e "s#__CLUSTER_ISSUER__#$LITRADESA_CLUSTER_ISSUER#g" \
    -e "s#__TLS_SECRET__#$LITRADESA_TLS_SECRET#g" \
    -e "s#__WEB_HOST_PORT__#$WEB_HOST_PORT#g" \
    -e "s#__REVERB_HOST_PORT__#$REVERB_HOST_PORT#g" \
    k8s/ingress/litradesa-ingress.yaml.tpl > "$tmp_file"

kubectl apply -f "$tmp_file"

echo "Ingress applied for https://$LITRADESA_HOST"
echo "Backend endpoint: $LITRADESA_NODE_IP:$WEB_HOST_PORT"
echo "Reverb endpoint: $LITRADESA_NODE_IP:$REVERB_HOST_PORT"
