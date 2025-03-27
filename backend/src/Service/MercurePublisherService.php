<?php

namespace App\Service;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\SerializerInterface;

class MercurePublisherService
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
     * @param array $targets The targets who can receive private updates
     * @param bool $private Whether the update is private
     * @return string The ID of the update
     */
    public function publish(string $topic, $data, array $targets = [], bool $private = false): string
    {
        // Serialize data to JSON if it's not already a string
        $json = is_string($data) ? $data : $this->serializer->serialize($data, 'json');


        $update = new Update(
            $topic,
            $json,
            $private
        );

        // Publish the update
        return $this->hub->publish($update);
    }
}