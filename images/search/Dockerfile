FROM opensearchproject/opensearch:2 AS base

ENV discovery.type=single-node
ENV DISABLE_INSTALL_DEMO_CONFIG=true
ENV DISABLE_SECURITY_PLUGIN=true

FROM base AS branch-amd64
FROM base AS branch-arm64
ENV _JAVA_OPTIONS=-XX:UseSVE=0
ARG TARGETARCH
FROM branch-${TARGETARCH} AS final

RUN /usr/share/opensearch/bin/opensearch-plugin install --batch ingest-attachment
