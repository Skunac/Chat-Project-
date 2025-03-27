<?php

namespace App\EventListener;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JWTCreatedListener implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::JWT_CREATED => 'onJWTCreated',
        ];
    }

    /**
     * Add custom claims to the JWT token
     */
    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();
        $payload = $event->getData();

        if (!$user instanceof User) {
            return;
        }

        $subscriptions = [];

        foreach ($user->getConversationParticipations() as $participant) {
            if ($participant->getLeftAt() === null) {
                $conversationId = $participant->getConversation()->getId();
                $subscriptions[] = 'conversation/' . $conversationId;
            }
        }

        $payload['mercure'] = [
            'subscribe' => $subscriptions,
            'publish' => $subscriptions,
        ];

        $event->setData($payload);
    }
}