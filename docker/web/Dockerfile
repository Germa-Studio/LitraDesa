FROM nginx:alpine

# Copy custom nginx configuration
COPY docker/web/nginx.conf /etc/nginx/nginx.conf
COPY docker/web/conf.d/default.conf /etc/nginx/conf.d/default.conf

# Create necessary directories
RUN mkdir -p /var/www/html/public

# Set permissions
RUN chown -R nginx:nginx /var/www/html

EXPOSE 80 443

CMD ["nginx", "-g", "daemon off;"]

# Made with Bob
