FROM python:3.12-slim AS builder

# To be eventually replaced by clone of a build release
RUN apt-get update && apt-get install -y git && rm -rf /var/lib/apt/lists/*

ENV REPO_BRANCH="main"
ARG GITLAB_TOKEN
ARG GITLAB_USERNAME="bluespice-bot"
ENV REPO_URL="https://${GITLAB_USERNAME}:${GITLAB_TOKEN}@gitlab.hallowelt.com/ai/webservice-ai.git"
RUN git clone --depth=1 -b ${REPO_BRANCH} "$REPO_URL" /tmp/ai
RUN date >> /tmp/ai/BUILDINFO
RUN find /tmp/ai -type d -name '.git' | xargs rm -rf {} \;
RUN apt-get purge -y git && apt-get autoremove -y && rm -rf /var/lib/apt/lists/*

FROM python:3.12-slim

WORKDIR /app
COPY --from=builder /tmp/ai ./
# Have to run in this step, as it installes to the system, not the local build dir
RUN pip install --no-cache-dir -r requirements.txt

CMD [ "python3", "-m", "app.main" ]