<?php

namespace App\Service\Redis;

use Predis\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RedisConnectionService
{
    private Client $redis;
    private string $prefix;
    private int $defaultTtl;

    public function __construct(
        private readonly ParameterBagInterface $params
    ) {
        $this->redis = new Client($_ENV['REDIS_URL'] ?? 'redis://localhost:6379');
        $this->prefix = $_ENV['REDIS_CHAT_PREFIX'] ?? 'chat_';
        $this->defaultTtl = (int)($_ENV['REDIS_CHAT_TTL'] ?? 604800); // 7 days default
    }

    public function getClient(): Client
    {
        return $this->redis;
    }

    public function getKey(string $key): string
    {
        return $this->prefix . $key;
    }

    public function getDefaultTtl(): int
    {
        return $this->defaultTtl;
    }
}