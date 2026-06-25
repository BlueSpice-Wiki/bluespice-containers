FROM opensearchproject/opensearch:3.5.0

ENV discovery.type=single-node
ENV DISABLE_INSTALL_DEMO_CONFIG=true
ENV DISABLE_SECURITY_PLUGIN=true
USER root
COPY --chown=opensearch:opensearch --chmod=755 ./root-fs/app/bin /app/bin
RUN ln -sf /app/bin/removeROtag /usr/local/bin
USER opensearch
RUN /usr/share/opensearch/bin/opensearch-plugin install --batch ingest-attachment

