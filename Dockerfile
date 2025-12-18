# Stage 1: Build Tailwind CSS
FROM node:18-slim AS build-stage
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY public/ ./public/
RUN ./node_modules/.bin/tailwindcss -i ./public/input.css -o ./public/output.css --minify

# Stage 2: Production PHP/Apache environment
FROM php:8.2-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Copy the rest of the application
COPY public/ /var/www/html/

# Copy the built CSS from the build stage
COPY --from=build-stage /app/public/output.css /var/www/html/output.css
