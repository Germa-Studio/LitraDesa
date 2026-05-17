apiVersion: v1
kind: Namespace
metadata:
  name: __NAMESPACE__
---
apiVersion: v1
kind: Service
metadata:
  name: litradesa-web
  namespace: __NAMESPACE__
spec:
  type: ClusterIP
  ports:
    - name: http
      port: 80
      protocol: TCP
      targetPort: __WEB_HOST_PORT__
---
apiVersion: discovery.k8s.io/v1
kind: EndpointSlice
metadata:
  name: litradesa-web-1
  namespace: __NAMESPACE__
  labels:
    kubernetes.io/service-name: litradesa-web
addressType: IPv4
ports:
  - name: http
    protocol: TCP
    port: __WEB_HOST_PORT__
endpoints:
  - addresses:
      - __NODE_IP__
    conditions:
      ready: true
---
apiVersion: v1
kind: Service
metadata:
  name: litradesa-reverb
  namespace: __NAMESPACE__
spec:
  type: ClusterIP
  ports:
    - name: http
      port: 80
      protocol: TCP
      targetPort: __REVERB_HOST_PORT__
---
apiVersion: discovery.k8s.io/v1
kind: EndpointSlice
metadata:
  name: litradesa-reverb-1
  namespace: __NAMESPACE__
  labels:
    kubernetes.io/service-name: litradesa-reverb
addressType: IPv4
ports:
  - name: http
    protocol: TCP
    port: __REVERB_HOST_PORT__
endpoints:
  - addresses:
      - __NODE_IP__
    conditions:
      ready: true
---
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: litradesa
  namespace: __NAMESPACE__
  annotations:
    cert-manager.io/cluster-issuer: __CLUSTER_ISSUER__
    nginx.ingress.kubernetes.io/proxy-body-size: "100m"
    nginx.ingress.kubernetes.io/proxy-read-timeout: "3600"
    nginx.ingress.kubernetes.io/proxy-send-timeout: "3600"
    nginx.ingress.kubernetes.io/ssl-redirect: "true"
spec:
  ingressClassName: nginx
  tls:
    - hosts:
        - __HOST__
      secretName: __TLS_SECRET__
  rules:
    - host: __HOST__
      http:
        paths:
          - path: /app
            pathType: Prefix
            backend:
              service:
                name: litradesa-reverb
                port:
                  name: http
          - path: /apps
            pathType: Prefix
            backend:
              service:
                name: litradesa-reverb
                port:
                  name: http
          - path: /
            pathType: Prefix
            backend:
              service:
                name: litradesa-web
                port:
                  name: http
