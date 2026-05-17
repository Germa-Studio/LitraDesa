# Tencent Lighthouse k3s Deployment

This guide runs LitraDesa with Docker Compose on the Lighthouse VM, while the shared k3s `nginx-ingress` and `cert-manager` stack handles public TLS.

The expected platform stack is the production setup from `workload-sre`:

- `ingressClassName: nginx`
- `cert-manager` ClusterIssuer: `germatech-letsencrypt`
- nginx-ingress binds host ports `80` and `443`
- domain zone: `*.germadev.my.id`

LitraDesa requests its own host certificate in the `litradesa` namespace. Do not reuse the wildcard secret from the `cert-manager` namespace directly, because Kubernetes TLS secrets are namespace-scoped.

## 1. DNS

Point the application domain to the Tencent Lighthouse public IP:

```text
litradesa.germadev.my.id  A  <LIGHTHOUSE_PUBLIC_IP>
```

Open public ports `80` and `443` only for nginx-ingress. LitraDesa itself is exposed on backend ports `18080` and `18081`.

## 2. Environment

Set production URLs in `src/.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://litradesa.germadev.my.id
APP_DOMAIN=litradesa.germadev.my.id
TRUSTED_PROXIES=*

REVERB_HOST=litradesa.germadev.my.id
REVERB_PORT=443
REVERB_SCHEME=https
VITE_REVERB_HOST=litradesa.germadev.my.id
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

## 3. Deploy App Behind Ingress

Run Compose in ingress mode. This starts `app`, `web`, `reverb`, `db`, and `redis`, but stops/skips Caddy so it does not compete with nginx-ingress for ports `80` and `443`.

```bash
DEPLOY_EDGE=ingress ./scripts/deploy-production.sh
```

By default:

- Laravel web is published on `0.0.0.0:18080`
- Laravel Reverb is published on `0.0.0.0:18081`

For a tighter bind, use the node/private IP that k3s can reach:

```bash
export LITRADESA_NODE_IP=<K3S_NODE_PRIVATE_IP>
export WEB_HOST_BIND=$LITRADESA_NODE_IP
export REVERB_HOST_BIND=$LITRADESA_NODE_IP
DEPLOY_EDGE=ingress ./scripts/deploy-production.sh
```

## 4. Apply Kubernetes Ingress

Apply the LitraDesa Service, EndpointSlice, and Ingress resources:

```bash
./scripts/deploy-ingress.sh
```

The script reads `APP_DOMAIN` from `src/.env` and auto-detects the node IP. Override when needed:

```bash
LITRADESA_HOST=litradesa.germadev.my.id \
LITRADESA_NODE_IP=<K3S_NODE_PRIVATE_IP> \
./scripts/deploy-ingress.sh
```

The generated ingress routes:

- `/` to `litradesa-web`
- `/app` and `/apps` to `litradesa-reverb`

TLS is requested through:

```yaml
cert-manager.io/cluster-issuer: germatech-letsencrypt
```

## 5. Verify

Check backend ports:

```bash
curl -I http://127.0.0.1:18080
curl -I http://127.0.0.1:18081
```

Check Kubernetes resources:

```bash
kubectl -n litradesa get ingress,svc,endpointslice
kubectl -n litradesa get certificate,order,challenge,secret
kubectl -n litradesa describe ingress litradesa
```

Check public HTTPS:

```bash
curl -I https://litradesa.germadev.my.id
```

If certificate issuance is pending, inspect the ACME challenge:

```bash
kubectl -n litradesa describe challenge
kubectl -n cert-manager logs deploy/cert-manager
```

Common causes are DNS not pointing to the Lighthouse public IP, Tencent firewall blocking port `80`, or another process still binding port `80` outside nginx-ingress.
