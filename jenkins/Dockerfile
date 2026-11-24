# Jenkins com as ferramentas que o pipeline usa: Docker (cliente), kubectl e kind.
FROM jenkins/jenkins:lts-jdk21

USER root
# cliente do Docker: o Jenkins constrói as imagens no Docker do computador (pelo socket montado)
COPY --from=docker:27-cli /usr/local/bin/docker /usr/local/bin/docker
# kubectl (publicar no Kubernetes) e kind (carregar a imagem no cluster)
RUN curl -fsSLo /usr/local/bin/kubectl https://dl.k8s.io/release/v1.37.2/bin/linux/amd64/kubectl \
 && curl -fsSLo /usr/local/bin/kind https://kind.sigs.k8s.io/dl/v0.33.0/kind-linux-amd64 \
 && chmod +x /usr/local/bin/kubectl /usr/local/bin/kind
USER jenkins

# plugins já instalados na imagem
RUN jenkins-plugin-cli --plugins \
    workflow-aggregator workflow-multibranch git pipeline-stage-view \
    timestamper ws-cleanup credentials-binding
