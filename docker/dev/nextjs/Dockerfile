FROM node:20-alpine

WORKDIR /app

# No need to copy the files since we're mounting the volume
# The error happens because Docker can't find the files at build time

# Install dependencies needed
RUN apk add --no-cache curl

# Use a simple command to start the application
CMD ["sh", "-c", "yarn install && yarn dev"]