# Imagen base Java 21; se eliminan fuentes apt de Yarn rotas (GPG) que hacen fallar
# `apt-get update` al instalar features como docker-outside-of-docker en Codespaces.
FROM mcr.microsoft.com/devcontainers/java:1-21-bookworm

USER root
RUN rm -f /etc/apt/sources.list.d/yarn.list \
    /etc/apt/sources.list.d/yarn-stable.list \
    2>/dev/null || true
USER vscode
