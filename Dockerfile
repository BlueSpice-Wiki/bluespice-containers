FROM opensearchproject/opensearch:2

ENV discovery.type=single-node
ENV DISABLE_INSTALL_DEMO_CONFIG=true
ENV DISABLE_SECURITY_PLUGIN=true
USER root
COPY --chown=opensearch:opensearch --chmod=755 ./root-fs/app/bin /app/bin
RUN ln -sf /app/bin/removeROtag /usr/local/bin
RUN /usr/share/opensearch/bin/opensearch-plugin install --batch ingest-attachment
RUN /usr/share/opensearch/bin/opensearch-plugin remove opensearch-security
# Support Arbitrary User IDs. See https://github.com/opensearch-project/opensearch-build/issues/3625
RUN chown -R 1000:0 /usr/share/opensearch /app/bin \
    && chmod -R g=u /usr/share/opensearch /app/bin \
    && chmod g+s /usr/share/opensearch
USER 1000

