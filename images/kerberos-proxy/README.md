# docker-bluespice-kerberos-proxy

## How to release a new version

### Build a new version of the image
```sh
docker build -t bluespice/kerberosproxy:latest .
```

### Apply proper tags
HINT: We align the image tags with the version of BlueSpice that it is compatible with.

Example:
```sh
docker tag bluespice/kerberosproxy:latest bluespice/kerberosproxy:5
docker tag bluespice/kerberosproxy:latest bluespice/kerberosproxy:5.2
docker tag bluespice/kerberosproxy:latest bluespice/kerberosproxy:5.2.1
```

### Push the image to the registry
Example:
```sh
docker push bluespice/kerberosproxy:latest
docker push bluespice/kerberosproxy:5
docker push bluespice/kerberosproxy:5.2
docker push bluespice/kerberosproxy:5.2.1
```

## Testing
Install `trivy` and run `trivy image bluespice/kerberosproxy` to check for vulnerabilities.
