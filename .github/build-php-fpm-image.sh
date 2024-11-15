#!/bin/sh

DOCKER_PHP_FPM_REPOSITORY_TAG=$1

docker image build \
    --build-arg project_root=project-base \
    --build-arg www_data_uid=$(id -u) \
    --build-arg www_data_gid=$(id -g) \
    --tag ${DOCKER_PHP_FPM_REPOSITORY_TAG} \
    --target production \
    --no-cache \
    --compress \
    -f project-base/docker/php-fpm/Dockerfile \
    .
