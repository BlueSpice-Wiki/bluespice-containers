FROM opensearchproject/opensearch:2 AS base

ENV discovery.type=single-node
ENV DISABLE_INSTALL_DEMO_CONFIG=true
ENV DISABLE_SECURITY_PLUGIN=true
USER root
COPY --chown=opensearch:opensearch --chmod=755 ./root-fs/app/bin /app/bin
RUN ln -sf /app/bin/removeROtag /usr/local/bin \
 && /usr/share/opensearch/bin/opensearch-plugin install \
      --batch ingest-attachment \
 && /usr/share/opensearch/bin/opensearch-plugin remove \
      opensearch-security \
 && mkdir -p \
      /usr/share/opensearch/data \
      /usr/share/opensearch/logs \
      /usr/share/opensearch/tmp \
 && chgrp -R 0 \
      /usr/share/opensearch/data \
      /usr/share/opensearch/logs \
      /usr/share/opensearch/tmp \
 && chmod -R g=u \
      /usr/share/opensearch/data \
      /usr/share/opensearch/logs \
      /usr/share/opensearch/tmp

USER 1000
