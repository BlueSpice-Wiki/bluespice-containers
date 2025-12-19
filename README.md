## Build

```bash
docker build \
     --build-arg GITLAB_TOKEN=glpat-...token... \
     --build-arg GITLAB_USERNAME=bluespice-bot \
     -t docker.bluespice.com/bluespice-qa/chat:latest .
```

## Environment variables

### Minimal setup (all required):

```yml
CHAT_BRIDGE_TOKEN: some_random_string
CHAT_WIKI_ACCESS_TOKEN: some_random_string
CHAT_WIKI_BASE_URL: http://my.wiki
CHAT_WIKI_CHAT_TOKEN_SALT: some_random_string

# If connecting to Slack
CHAT_SLACK_APP_TOKEN: xapp-...
CHAT_SLACK_BOT_TOKEN: xoxb-...
CHAT_SLACK_BOT_NAME: my-bot-name

# If connecting to MS Teams
CHAT_MSTEAMS_BOT_CLIENT_SECRET: some_secret
CHAT_MSTEAMS_BOT_TENANT_ID: some_tenant_id
CHAT_MSTEAMS_BOT_APP_ID: some_app_id
CHAT_MSTEAMS_BOT_NAME: my-bot-name

# If connecting to AI service
CHAT_AI_CHAT_SERVICE_URL: https://my.ai.service
CHAT_AI_CHAT_SERVICE_API_KEY: some_api_key

# If connecting to Rocket.Chat
CHAT_ROCKETCHAT_WS_URL: wss://chat.example.com/websocket
CHAT_ROCKETCHAT_API_URL: https://chat.example.com/api/v1
CHAT_ROCKETCHAT_BOT_NAME: dev0.bot
CHAT_ROCKETCHAT_USERNAME: dev0.bot
CHAT_ROCKETCHAT_PASSWORD_SHA256: sha256_hash_of_password

```

### All available environment variables:

`CHAT_HUBOT_LOG_LEVEL` - log level for HUBOT (default: info)

`CHAT_LOG_LEVEL` - log level for chat service (default: info)

`CHAT_USE_HTTPS` - Whether to use HTTPS (default: false)

`CHAT_ALLOW_SSL_UNVERIFIED` - Whether to allow unverified SSL certificates (default: false)

Bridge - connection between all services

`CHAT_BRIDGE_PORT` - port to run bridge on (must be exposed, default: 3000)

`CHAT_BRIDGE_TOKEN` - Randomly generated token for the bridge, replace with your own and configure on wiki side

Wiki

`CHAT_WIKI_ACCESS_TOKEN` - Access token for wiki

`CHAT_WIKI_BASE_URL` - Base URL for wiki, e.g. http://my.wiki

`CHAT_WIKI_ARTICLE_ENDPOINT` - Article endpoint (default: `WIKI_BASE_URL/wiki`)

`CHAT_WIKI_REST_ENDPOINT` - REST API endpoint (default: `WIKI_BASE_URL/w/rest.php`)

`CHAT_WIKI_ACTION_ENDPOINT` - Action API endpoint (default: `WIKI_BASE_URL/w/api.php`)

`CHAT_WIKI_CHAT_PORT` - Port where wiki chat service is running (default: 3002)

`CHAT_WIKI_CHAT_TOKEN_SALT` - Token salt for signing chat JWT tokens, must be the same as configured on wiki side


Slack (configure to enable connection to Slack)

`CHAT_SLACK_APP_TOKEN` - App token obtained from Slack (starts with `xapp-`)

`CHAT_SLACK_BOT_TOKEN` - Bot token obtained from Slack (starts with `xoxb-`)

`CHAT_SLACK_BOT_NAME` - Bot name, usually the same as the app name


MS Teams (configure to enable connection to MS Teams)

`CHAT_MSTEAMS_BOT_CLIENT_SECRET` - Bot client secret obtained from Azure portal

`CHAT_MSTEAMS_BOT_TENANT_ID` - Bot tenant ID obtained from Azure portal

`CHAT_MSTEAMS_BOT_APP_ID` - Bot app ID obtained from Azure portal

`CHAT_MSTEAMS_WEB_PORT` - Publicly available port to run Teams connector on (must be exposed, default: 8090)

`CHAT_MSTEAMS_BOT_NAME` - Bot name, usually the same as the app name

`CHAT_MSTEAMS_BOT_SERVICE_URL` - Service URL, pattern: https://smba.trafficmanager.net/{region}/{TEAMS_BOT_TENANT_ID} (default https://smba.trafficmanager.net/emea/{$TEAMS_BOT_TENANT_ID})


AI service (configure to enable connection to AI service)

`CHAT_AI_CHAT_SERVICE_URL` - Full URL to AI chat service, eg. https://my.ai.service

`CHAT_AI_CHAT_SERVICE_API_KEY` - API key for AI chat service


Rocket.Chat (configure to enable connection to Rocket.Chat)

`CHAT_ROCKETCHAT_WS_URL` - WebSocket URL for Rocket.Chat, e.g. wss://chat.example.com/websocket

`CHAT_ROCKETCHAT_API_URL` - API URL for Rocket.Chat, e.g. https://chat.example.com/api/v1

`CHAT_ROCKETCHAT_BOT_NAME` - Bot name, as written in "mention" messages, eg `"@dev0.bot"`

`CHAT_ROCKETCHAT_USERNAME` - Bot username, eg `dev0.bot`

`CHAT_ROCKETCHAT_PASSWORD_SHA256` - SHA256 hash of bot password

`CHAT_ROCKETCHAT_SUBSCRIBE_CHANNELS` - Comma-separated list of channels to subscribe to, eg. `general,random` (default: `''`)