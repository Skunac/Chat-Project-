FROM node:20-alpine

WORKDIR /app

# Install dependencies needed for development
RUN apk add --no-cache curl

# Set environment variables
ENV HOST=0.0.0.0
ENV PORT=3000
ENV NODE_ENV=development

# Command to run on container start
CMD ["sh", "-c", "yarn install && yarn dev"]