<?php

namespace App\Service;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\SerializerInterface;

class MercurePublisher
{
    private HubInterface $hub;
    private SerializerInterface $serializer;

    public function __construct(
        HubInterface $hub,
        SerializerInterface $serializer
    ) {
        $this->hub = $hub;
        $this->serializer = $serializer;
    }

    /**
     * Publish an update to Mercure hub
     *
     * @param string $topic The topic (target)
     * @param mixed $data The data to publish
     * @param array $targets The targets who can receive private updates (NOT USED)
     * @param bool $private Whether the update is private
     * @return string The ID of the update
     */
    public function publish(string $topic, $data, array $targets = [], bool $private = false): string
    {
        // Serialize data to JSON if it's not already a string
        $json = is_string($data) ? $data : $this->serializer->serialize($data, 'json');

        // This is the correct way to create an Update with your version of the package
        // Note: The targets parameter is ignored, as it's not part of the constructor
        $update = new Update(
            $topic,      // Topic (can be string or array of strings)
            $json,       // Data (JSON string)
            $private,    // Private flag
            null,        // ID (optional)
            null,        // Type (optional) - NOT $targets!
            null         // Retry (optional)
        );

        // Publish the update
        return $this->hub->publish($update);
    }
}