# Smart CRM - PHP API (service "api" in docker-compose.yml)
FROM php:8.3-cli-alpine

# PDO MySQL driver (curl, mbstring are already built into the official image)
RUN docker-php-ext-install pdo_mysql

# Security (checklist item 5): the official image has no php.ini, so PHP would print
# warnings into HTTP responses and announce its version in X-Powered-By.
RUN { echo 'display_errors=Off'; echo 'log_errors=On'; echo 'error_log=/dev/stderr'; echo 'expose_php=Off'; } \
    > "$PHP_INI_DIR/conf.d/zz-smartcrm.ini"

WORKDIR /app

# Application code, migration/seed scripts and the test suites
# (tests run inside this container: docker compose exec api php tests/run_all.php)
COPY src ./src
COPY database ./database
COPY tests ./tests

# Startup script: migrate -> seed -> start the PHP server.
# sed strips Windows line endings in case the repository was checked out with CRLF.
COPY docker/api-entrypoint.sh /usr/local/bin/api-entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/api-entrypoint \
    && chmod 755 /usr/local/bin/api-entrypoint

ENV SESSION_SAVE_PATH=/tmp/smartcrm-sessions \
    PHP_CLI_SERVER_WORKERS=4

# Do not run as root
USER www-data

EXPOSE 8000
ENTRYPOINT ["api-entrypoint"]
