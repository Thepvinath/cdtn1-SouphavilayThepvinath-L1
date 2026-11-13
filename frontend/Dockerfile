# Smart CRM - React frontend (service "web" in docker-compose.yml)

# 1. Build the static files with the locked dependency versions
FROM node:22-alpine AS build
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# 2. Serve them with nginx; /api is proxied to the api service
FROM nginx:1.27-alpine
# nginx.conf.template -> /etc/nginx/conf.d/default.conf at start-up (envsubst, built into the image).
# PORT and API_UPSTREAM can be overridden by the hosting platform (TD-17); defaults = docker compose.
# NGINX_ENTRYPOINT_LOCAL_RESOLVERS makes the image export NGINX_LOCAL_RESOLVERS for the template.
ENV PORT=80 \
    API_UPSTREAM=api:8000 \
    NGINX_ENTRYPOINT_LOCAL_RESOLVERS=1
COPY nginx.conf.template /etc/nginx/templates/default.conf.template
COPY --from=build /app/dist /usr/share/nginx/html
EXPOSE 80
