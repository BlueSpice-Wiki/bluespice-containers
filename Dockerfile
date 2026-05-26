FROM python:3.12-alpine AS builder

# To be eventually replaced by clone of a build release
ARG REPO_BRANCH="main"
ENV REPO_URL="https://gitlab.hallowelt.com/ai/webservice-ai.git"
ADD "$REPO_URL#$REPO_BRANCH" /tmp/ai/
RUN date >> /tmp/ai/BUILDINFO
RUN find /tmp/ai -type d -name '.git' | xargs rm -rf {} \;

# Create virtual environment and install dependencies in builder stage
RUN python -m venv /opt/venv
ENV PATH="/opt/venv/bin:$PATH"

RUN apk update \
    && apk add --virtual build-deps gcc musl-dev \
    && apk add --no-cache mariadb-dev

RUN pip install --no-cache-dir -r /tmp/ai/requirements.txt

RUN apk del build-deps

FROM python:3.12-alpine

# Security: Install latest security updates
RUN apk update && apk upgrade

# Security: Create non-root user
ARG UID
ENV UID=1002
ARG USER
ENV USER=bluespice
ARG GID
ENV GID=$UID
ARG GROUPNAME
ENV GROUPNAME=$USER
RUN addgroup -g "$GID" "$GROUPNAME" \
 && adduser -u "$UID" -G "$GROUPNAME" -S -h /nonexistent -s /bin/sh "$USER"

WORKDIR /app

# Copy the virtual environment from builder with correct ownership
COPY --from=builder --chown=bluespice:bluespice /opt/venv /opt/venv
COPY --chown=bluespice:bluespice root-fs/* ./
COPY --from=builder --chown=bluespice:bluespice /tmp/ai ./ai

# Set PATH to use the virtual environment
ENV PATH="/opt/venv/bin:$PATH"

# Security: Run as non-root user
USER bluespice

CMD ["/app/bin/entrypoint"]
