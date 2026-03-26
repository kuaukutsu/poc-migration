FROM ghcr.io/kuaukutsu/claude:php8.3 AS build

# Arguments
ARG UID=10001
ARG WORKDIR="/src"

# Dependencies
COPY --from=composer:latest --link /usr/bin/composer /usr/bin/composer
# user
RUN <<EOF
    adduser -u ${UID} -G www-data -s /bin/sh -D developer www-data
EOF
# workdir
RUN <<EOF
    mkdir -p ${WORKDIR}
    chown -R developer:www-data ${WORKDIR}
EOF
# composer
RUN <<EOF
    mkdir /var/.composer
    chown developer:www-data /var/.composer
EOF
ENV COMPOSER_CACHE_DIR=/var/.composer

FROM build AS cli

USER $UID
WORKDIR $WORKDIR

