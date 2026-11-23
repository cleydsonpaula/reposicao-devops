#!/usr/bin/env bash
# Publica a aplicação num ambiente do Kubernetes.
# Uso: k8s/deploy.sh <dev|homologacao|pre-producao|producao> [etiqueta-da-imagem]
set -euo pipefail
AMB="${1:-}"; TAG="${2:-local}"
case "$AMB" in dev|homologacao|pre-producao|producao) ;; *) echo "uso: $0 <dev|homologacao|pre-producao|producao> [etiqueta]"; exit 1;; esac
cd "$(dirname "$0")/.."

# 1) o namespace do ambiente (cria se não existir)
kubectl create namespace "$AMB" --dry-run=client -o yaml | kubectl apply -f -

# 2) o script que cria as tabelas, guardado num ConfigMap
kubectl -n "$AMB" create configmap db-init --from-file=db/init.sql --dry-run=client -o yaml | kubectl apply -f -

# 3) as senhas do banco num Secret — sorteadas uma vez por ambiente e nunca gravadas no git
if ! kubectl -n "$AMB" get secret db >/dev/null 2>&1; then
  sorteia() { head -c 16 /dev/urandom | od -An -tx1 | tr -d ' \n'; }
  kubectl -n "$AMB" create secret generic db \
    --from-literal=root-password="$(sorteia)" --from-literal=user=simples --from-literal=password="$(sorteia)"
fi

# 4) banco + aplicação, com a etiqueta da imagem desta versão
kubectl kustomize "k8s/overlays/$AMB" | sed "s|reposicao-devops:local|reposicao-devops:$TAG|" | kubectl apply -f -

# 5) espera ficar pronto
kubectl -n "$AMB" rollout status deployment/mysql --timeout=180s
kubectl -n "$AMB" rollout status deployment/app --timeout=180s
