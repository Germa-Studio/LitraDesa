FROM node:20-alpine

WORKDIR /var/www/html

# Install dependencies for node-gyp
RUN apk add --no-cache python3 make g++

# Copy package files
COPY src/package*.json ./

# Install dependencies
RUN npm ci

# Expose Vite dev server port
EXPOSE 5173

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]

# Made with Bob
