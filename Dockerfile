# Stage 1: Build Tailwind CSS
FROM debian:bullseye-slim AS build-stage
WORKDIR /app

# Install curl and ca-certificates
RUN apt-get update && apt-get install -y curl ca-certificates && rm -rf /var/lib/apt/lists/*

# Download standalone Tailwind CSS CLI
# Using a specific version to ensure stability
RUN curl -sLO https://github.com/tailwindlabs/tailwindcss/releases/download/v3.4.1/tailwindcss-linux-x64 \
    && chmod +x tailwindcss-linux-x64 \
    && mv tailwindcss-linux-x64 /usr/local/bin/tailwindcss

COPY public/ ./public/
# Run the standalone binary
RUN tailwindcss -i ./public/input.css -o ./public/output.css --minify

# Stage 2: Production PHP/Apache environment
FROM php:8.2-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Copy the rest of the application
COPY public/ /var/www/html/

# Copy the built CSS from the build stage
COPY --from=build-stage /app/public/output.css /var/www/html/output.css
