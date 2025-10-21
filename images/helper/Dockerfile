ARG BASE_IMAGE=alpine:3.20.3
FROM $BASE_IMAGE
ENV PATH="/app/bin:${PATH}"
RUN apk add bash \
	mongodb-tools \
	mysql-client \
	docker-cli \
	rsync \
	restic \
	supercronic \
	vim
COPY ./root-fs/app /app
