FROM caddy:2-alpine

# Copy Caddyfile configuration
COPY docker/prod/caddy/Caddyfile /etc/caddy/Caddyfile

# Create log directory
RUN mkdir -p /var/log/caddy && \
    chown -R caddy:caddy /var/log/caddy

# Use non-root user
USER caddy

# Health check configuration
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD [ "wget", "-q", "-O", "-", "http://localhost/health" ]

# Default command
CMD ["caddy", "run", "--config", "/etc/caddy/Caddyfile"]