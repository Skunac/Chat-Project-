<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

readonly class ConversationMessageProvider
{
    public function __construct(
        private ConversationRepository $conversationRepository,
        private MessageRepository      $messageRepository,
        private Security               $security
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $conversation = $this->conversationRepository->find($uriVariables['id']);
        if (!$conversation) {
            throw new NotFoundHttpException('Conversation not found');
        }

        $currentUser = $this->security->getUser();
        $isParticipant = false;
        foreach ($conversation->getParticipants() as $participant) {
            if ($participant->getUser()->getId() === $currentUser->getId() && $participant->getLeftAt() === null) {
                $isParticipant = true;
                break;
            }
        }

        if (!$isParticipant) {
            throw new AccessDeniedHttpException('You are not a participant in this conversation');
        }

        // Get query parameters with defaults
        $limit = $context['filters']['limit'] ?? 50;
        $offset = $context['filters']['offset'] ?? 0;

        // Return messages
        return $this->messageRepository->findByConversation(
            $conversation,
            (int)$limit,
            (int)$offset
        );
    }
}