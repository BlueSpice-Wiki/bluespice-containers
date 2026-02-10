## Build

Assuming you have a valid access token for https://gitlab.hallowelt.com/ai/webservice-ai.git stored in `~/gitlab-token.txt`, you can run

```bash
GIT_AUTH_TOKEN=$(cat ~/gitlab-token.txt) \
docker build \
	--secret id=GIT_AUTH_TOKEN \
	-t bluespice/ai:latest .
```

## Environment Variables

> [!WARNING]
> The choice of LLM provider is chosen by the given env variables: `AI__LLM_PROVIDER` `AI__EMBEDDER_PROVIDER`.
>
> Currently the provider options are `ollama`, `openai`, `ionos`, `azure`
>
> Changing the configured `embedder` provider requires full re-index, as embeddings are provider-specific.

### Minimal set of variables, these **are required**

> [!WARNING]
> Variabled for specific providers (AZURE, IONOS, OLLAMA...) are only requied if that provider is used.
>
> I.e. If you are not using OLLAMA it's not necessary to set AI__OLLAMA* variables

For more details, see [Environment Variables](#available-environment-variables) below

```dotenv
AI__SECURITY__STATIC_API_TOKEN=<random_token>

AI__MYSQL__HOST=localhost
AI__MYSQL__USER=ai-service
AI__MYSQL__PASSWORD=mysecretpass
AI__MYSQL__DB_NAME=ai-service

AI__NEO4J__USERNAME=neo4j
AI__NEO4J__PASSWORD=mysecretpass
AI__NEO4J__URL="neo4j://localhost:7687"

AI__AZURE__OPENAI_API_KEY=<azure_openai_key>
AI__AZURE__OPENAI_ENDPOINT=<azure_endpoint>
AI__IONOS__API_KEY=<ionos_api_key>
AI__OPENAI__API_KEY=<openai_api_key>
AI__OLLAMA__URL=<ollama_url>

AI__LLM_PROVIDER="your choice"
AI__EMBEDDER_PROVIDER="your choice"
```

### Ingestion directory
This application uses externally provided data as a base for its answers. You must mount the directory to ingest data from to `/watch`
inside the container.

### Available Environment Variables

**Highlighted** variables are required

| Variable Name                           | Description                                                                                                                                            | Default                                   |
|:----------------------------------------|:-------------------------------------------------------------------------------------------------------------------------------------------------------|:------------------------------------------|
| AI__ENVIRONMENT                         | production, development, or 'mock' for dummy responses                                                                                                 | production                                |
| AI__LOGGING__LEVEL                      | DEBUG, INFO, WARNING, ERROR, or CRITICAL                                                                                                               | ERROR                                     |
| AI__LOGGING__LOGGER_NAME                | Specifies the name of the logger instance                                                                                                              | webservice-ai.logger                      |
| AI__FASTAPI__HOST                       | Self explanatory                                                                                                                                       | 0.0.0.0 or localhost                      |
| AI__FASTAPI__PORT                       | Self explanatory                                                                                                                                       | 8000                                      |
| **AI__SECURITY__STATIC_API_TOKEN**      | Token used for API authentication. Run `openssl rand -hex 32` or similar                                                                               |                                           |
| AI__LLM__SIMILARITY_SEARCH_RETURN_COUNT | Number of documents to return from a (vector) similarity search                                                                                        | 5                                         |
| AI__LLM__SIMILARITY_SEARCH_FETCH_COUNT  | Number of documents to fetch from a (vector) similarity search                                                                                         | 20                                        |
| AI__LLM__CHUNK_SIZE                     | Size of text chunk used for indexing                                                                                                                   | 1024                                      |
| AI__LLM__CHUNK_OVERLAP                  | Chunk size 'overflow' limit                                                                                                                            | 32                                        |
| AI__LLM__TEMPERATURE                    | Randomness/fluctuation of LLM in responses                                                                                                             | 0.0                                       |
| AI__LLM__MAX_TOKENS                     | Maximum number of tokens in LLM responses                                                                                                              | 1024                                      |
| AI__LLM__FREQUENCY_PENALTY              | Penalty for token frequency to reduce repetition                                                                                                       | 0.25                                      |
| AI__LLM__MINIMIZE_LLM_USAGE             | Less tokens used => less context => less quality                                                                                                       | False                                     |
| AI__LLM__MAX_INPUT_TOKENS               | Max token count model supports for input (query + prompts + history + context)                                                                         | 32000                                     |
| AI__LLM__USE_SUMMARIZED_HISTORY         | Whether to summarize chat history instead of adding actual messages (True for less token usage)                                                        | True                                      |
| AI__LLM__JUDGE_PARAPHRASES              | Whether to make another LLM call after rephrasing to find best fitting ones                                                                            | False                                     |
| AI__LLM__ENABLE_TIMESPAN_FILTERING      | Whether to extract required timespan from user's query and filter only for documents created/modified during that period ("this year", "last week"...) | True                                      |
| **AI__MYSQL__HOST**                     | MySQL Database host                                                                                                                                    |                                           |
| AI__MYSQL__PORT                         | MySQL Database port                                                                                                                                    | 3306                                      |
| **AI__MYSQL__USER**                     | MySQL Database user name                                                                                                                               |                                           |
| **AI__MYSQL__PASSWORD**                 | MySQL Database user password                                                                                                                           |                                           |
| AI__MYSQL__POOL_SIZE                    | MySQL Database connection pool size                                                                                                                    | 10                                        |
| AI__MYSQL__DB_NAME                      | Database name                                                                                                                                          | ai-service                                |
| AI__MYSQL__TABLE_PREFIX                 | Prefix for AI service table names                                                                                                                      | ai_service_                               |
| **AI__NEO4J__USERNAME**                 | Neo4J Database user name                                                                                                                               |                                           |
| **AI__NEO4J__PASSWORD**                 | Neo4J Database user password                                                                                                                           |                                           |
| **AI__NEO4J__URL**                      | Neo4J Database host                                                                                                                                    |                                           |
| AI__NEO4J__HTTP_PORT                    | Neo4J Database HTTP protocol port                                                                                                                      | 7474                                      |
| AI__NEO4J__BOLT_PORT                    | Neo4J Database BOLT protocol port                                                                                                                      | 7687                                      |
| AI__NEO4J__DB_NAME                      | Neo4J Database name                                                                                                                                    | neo4j                                     |
| AI__NEO4J__VECTOR_INDEX_SEARCH_TYPE     | Type of similarity search algorithm ('vector', 'fulltext', or both 'hybrid')                                                                           | hybrid                                    |
| AI__NEO4J__KEYWORD_INDEX_NAME           | Name of fulltext index for SectionChunk and PageChunk fields                                                                                           | wiki_fulltext_index                       |
| AI__NEO4J__SECTION_VECTOR_INDEX_NAME    | Name of vector index for SectionChunks                                                                                                                 | wiki_fulltext_index_section_chunk         |
| AI__NEO4J__PAGE_VECTOR_INDEX_NAME       | Name of vector index for PageChunks                                                                                                                    | wiki_vector_index_page_chunk              |
| AI__AZURE__REGION                       | Azure API region                                                                                                                                       | westeurope                                |
| **_AI__AZURE__OPENAI_API_KEY_**         | Azure OpenAI API key                                                                                                                                   |                                           |
| **AI__AZURE__OPENAI_ENDPOINT**          | Azure OpenAI API endpoint                                                                                                                              |                                           |
| AI__AZURE__LLM__MODEL_NAME              | Azure LLM Model Name                                                                                                                                   | gpt-4o                                    |
| AI__AZURE__LLM__API_VERSION             | Azure LLM API version                                                                                                                                  | 2025-03-01-preview                        |
| AI__AZURE__EMBEDDING__ENDPOINT		  | Azure embeddings model endpoint, only needed if the embeddings model is in a separate resource group/deployment										   | 										   |
| AI__AZURE__EMBEDDING__API_KEY			  | Azure embeddings API key, only needed if the embeddings model is in a separate resource group/deployment											   |                                           |
| AI__AZURE__EMBEDDING__MODEL_NAME        | Azure embeddings model name                                                                                                                            | text-embedding-3-small                    |
| AI__AZURE__EMBEDDING__MODEL_VERSION     | Azure embeddings model version                                                                                                                         | 1                                         |
| AI__AZURE__EMBEDDING__API_VERSION       | Azure embeddings API version                                                                                                                           | 2023-05-15                                |
| AI__BOOST_WEIGHT__RECENCY               | Field weight for recency/last changed date                                                                                                             | 0.02                                      |
| AI__BOOST_WEIGHT__RELEVANCE             | Field weight for relevance                                                                                                                             | 0.05                                      |
| AI__BOOST_WEIGHT__POPULARITY            | Field weight for popularity (popular: very visited and often linked)                                                                                   | 0.05                                      |
| AI__BOOST_WEIGHT__RELATED               | How much to boost if pages in results set are linked                                                                                                   | 0.10                                      |
| AI__IONOS__API_OPENAI_BASE_URL          | Base URL for all OpenAI inference calls                                                                                                                | https://openai.inference.de-txl.ionos.com |
| AI__IONOS__API_INFERENCE_BASE_URL       | Base URL for all other inference calls                                                                                                                 | https://inference.de-txl.ionos.com
| AI__IONOS__MODEL                        | Ionos LLM model name                                                                                                                                   | openai/gpt-oss-120b
| AI__IONOS__EMBEDDING_MODEL              | Ionos embeddings model name                                                                                                                            | BAAI/bge-m3
| **_AI__IONOS__API_KEY_**                | Ionos API key                                                                                                                                          |                                           |
| **_AI__OPENAI__API_KEY_**               | API key for OpenAI Api                                                                                                                                 |                                           |
| AI__OPENAI__BASE_URL                    | OpenAI API location                                                                                                                                    | https://api.openai.com/v1                 |
| AI__OPENAI__MODEL                       | Model to use for LLM calls in OpenAI provider env                                                                                                      | gpt40                                     |
| AI__OPENAI__EMBEDDING_MODEL             | Model to user for embeddings in OpenAI provider env                                                                                                    | text-embedding-3-small                    |
| **_AI__OLLAMA__URL_**                   | Location of Ollama server (EXPERIMENTAL)                                                                                                               |                                           |
| AI__OLLAMA__MODEL                       | Model to use for LLM calls in OLLAMA provider env (EXPERIMENTAL)                                                                                       | llama3.3                                  |
| AI__OLLAMA__EMBEDDING_MODEL             | Model to user for embeddings in OLLAMA provider env (EXPERIMENTAL)                                                                                     | nomic-embed-text                          |
| **AI__LLM_PROVIDER**                    | Provider choice for RAGs' LLM, this is the provider whose AI actually produces the final response                                                      |                                           |
| **AI__EMBEDDER_PROVIDER**               | Provider choice for RAGs' embedding provider, this provider processes embeddings and vector searches                                                   |                                           |
| **AI__GOOGLE__API_KEY**                 | API key for Google AI API (EXPERIMENTAL)
| AI__GOOGLE__MODEL                       | Model to use for LLM calls in Google AI (EXPERIMENTAL)
| AI__GOOGLE__EMBEDDING_MODEL             | Embedding model for document storage and retrieval in google AI (EXPERIMENTAL)
