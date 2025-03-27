<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use App\Service\MercurePublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class MessageProcessor
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
        private ProcessorInterface $persistProcessor,
        private MercurePublisher $mercurePublisher
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

        if ($data->parentMessage) {
            $parentMessage = $this->entityManager->getRepository(Message::class)->find($data->parentMessage);
            if (!$parentMessage) {
                throw new BadRequestHttpException('Parent message not found');
            }
            $message->setParentMessage($parentMessage);
        }

        if ($data->metadata) {
            $message->setMetadata($data->metadata);
        }

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        $this->publishToMercure($message);

        return $message;
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

        if ($message->getParentMessage()) {
            $publishData['parentMessageId'] = $message->getParentMessage()->getId();
        }

        if (!empty($message->getMetadata())) {
            $publishData['metadata'] = $message->getMetadata();
        }


        $targets = [];
        foreach ($message->getConversation()->getParticipants() as $participant) {
            if ($participant->getLeftAt() === null) {
                $targets[] = 'user/' . $participant->getUser()->getId();
            }
        }

        $this->mercurePublisher->publish(
            $topic,
            $publishData,
            $targets,
            false
        );
    }
}