# CollabPads Backend

This code is a PHP based re-implemenation of the original ["rebaser" code](https://github.com/wikimedia/VisualEditor/tree/master/rebaser) in VisualEditor (see also https://www.mediawiki.org/wiki/VisualEditor/Real-time_collaboration). It also features a permission check upon connection to the server, which is not present in the original code.

## Container image
For convenience, a Dockerfile is provided to build the container image. It can be used to easily deploy the backend code in a containerized environment.

### ENV vars
The container exposes a number of [ENV variables](./config.docker.php):

| ENV Variable                                    | Default Value     | Description                                                      |
|-------------------------------------------------|-------------------|------------------------------------------------------------------|
| `COLLABPADS_BACKEND_PORT`                       | `80`              | Port on which the backend server listens.                        |
| `COLLABPADS_BACKEND_WIKI_BASEURL`               | ``          | Base URL of the connected wiki instance.                         |
| `COLLABPADS_BACKEND_MONGO_DB_HOST`              | ``          | Hostname or IP address of the MongoDB server.                    |
| `COLLABPADS_BACKEND_MONGO_DB_PORT`              | `27017`           | Port for MongoDB connection.                                     |
| `COLLABPADS_BACKEND_MONGO_DB_NAME`              | `collabpads`      | Name of the MongoDB database to use.                             |
| `COLLABPADS_BACKEND_MONGO_DB_USER`              | ``         | Username for MongoDB authentication.                             |
| `COLLABPADS_BACKEND_MONGO_DB_PASSWORD`          | ``         | Password for MongoDB authentication.                             |
| `COLLABPADS_BACKEND_MONGO_DB_DEFAULT_AUTH_DB`   | `admin`           | Default authentication database for MongoDB.                     |
| `COLLABPADS_BACKEND_LOG_LEVEL`                  | `warn`            | Log level (e.g., debug, info, warn, error).                      |

### Volumes
The container does not require any volumes to be mounted, as all configuration is done via ENV variables.

### Dependencies
All data is stored in a MongoDB database, which needs to be accessible from the container. Currently up to MongoDB 8 is supported.

See also [docker-compose.json](./docker-compose.json) for an example.

### Building the container image
To build the container image, run the following command in this directory:

```bash
docker build -t hallowelt/collabpads-backend:1.0 .
```