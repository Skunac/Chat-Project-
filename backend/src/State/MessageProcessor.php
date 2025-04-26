<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use App\Service\MercurePublisherService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class MessageProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private ProcessorInterface $persistProcessor,
        private MercurePublisherService $mercurePublisher
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Message
    {
        // Create the message entity from the DTO
        $message = new Message();
        $message->setContent($data->content);
        $message->setConversation($data->conversation);

        // Set the current user as the creator
        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            throw new BadRequestHttpException('User not authenticated');
        }
        $message->setSender($currentUser);

        // Persist with the standard processor
        $persistedMessage = $this->persistProcessor->process($message, $operation, $uriVariables, $context);

        // Now publish to Mercure after it's been persisted
        $this->publishToMercure($persistedMessage);

        return $persistedMessage;
    }

    private function publishToMercure(Message $message): void
    {
        // Create topic based on conversation ID
        $topic = 'conversation/' . $message->getConversation()->getId();

        // Prepare message data for publishing
        $publishData = [
            'id' => $message->getId(),
            'content' => $message->getContent(),
            'senderId' => $message->getSender()->getId(),
            'senderName' => $message->getSender()->getDisplayName(),
            'sentAt' => $message->getSentAt()->format('c'),
            'conversationId' => $message->getConversation()->getId(),
        ];

        // Build targets array
        $targets = [];
        foreach ($message->getConversation()->getParticipants() as $participant) {
            $targets[] = 'user/' . $participant->getUser()->getId();
        }

        // Publish to Mercure
        $this->mercurePublisher->publish(
            $topic,
            $publishData,
            $targets,
            false
        );
    }
}