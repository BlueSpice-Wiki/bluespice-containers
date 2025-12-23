FROM python:3.12-slim AS builder

# To be eventually replaced by clone of a build release
ARG REPO_BRANCH="1.0.x"
ENV REPO_URL="https://gitlab.hallowelt.com/ai/webservice-ai.git"
ADD "$REPO_URL#$REPO_BRANCH" /tmp/ai/
RUN date >> /tmp/ai/BUILDINFO
RUN find /tmp/ai -type d -name '.git' | xargs rm -rf {} \;

FROM python:3.12-slim

WORKDIR /app
COPY root-fs/* ./
COPY --from=builder /tmp/ai ./ai
# Have to run in this step, as it installes to the system, not the local build dir
RUN cd ai && pip install --no-cache-dir -r requirements.txt

CMD ["/app/bin/entrypoint"]