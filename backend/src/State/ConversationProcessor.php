<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\ConversationInput;
use App\Entity\Conversation;
use App\Entity\ConversationParticipant;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ConversationProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private ProcessorInterface $persistProcessor
    ) {
    }

    /**
     * @param ConversationInput $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Conversation
    {
        // Create the conversation entity from the DTO
        $conversation = new Conversation();
        $conversation->setName($data->name);
        $conversation->setAvatarUrl($data->avatarUrl);
        $conversation->setIsGroup($data->isGroup);
        $conversation->setIsEncrypted($data->isEncrypted);
        $conversation->setSettings($data->settings);

        // Set the current user as the creator
        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            throw new BadRequestHttpException('User not authenticated');
        }

        $conversation->setCreator($currentUser);

        // Add the creator as a participant (admin)
        $creatorParticipant = new ConversationParticipant();
        $creatorParticipant->setUser($currentUser)
            ->setConversation($conversation)
            ->setRole('ADMIN')
            ->setIsAdmin(true);

        $conversation->addParticipant($creatorParticipant);

        // Add other participants
        $userRepository = $this->entityManager->getRepository(User::class);

        if (empty($data->participantIds)) {
            throw new BadRequestHttpException('At least one participant is required');
        }

        foreach ($data->participantIds as $userId) {
            // Skip if the ID is the same as the creator
            if ($userId === $currentUser->getId()) {
                continue;
            }

            $user = $userRepository->find($userId);

            if (!$user) {
                throw new NotFoundHttpException(sprintf('User with ID "%s" not found', $userId));
            }

            $participant = new ConversationParticipant();
            $participant->setUser($user)
                ->setConversation($conversation)
                ->setRole('MEMBER');

            $conversation->addParticipant($participant);
        }

        // For non-group conversations, ensure there are exactly 2 participants
        if (!$conversation->isGroup() && count($conversation->getParticipants()) !== 2) {
            throw new BadRequestHttpException('A direct conversation must have exactly two participants.');
        }

        // Save everything to the database
        $this->entityManager->persist($conversation);
        foreach ($conversation->getParticipants() as $participant) {
            $this->entityManager->persist($participant);
        }
        $this->entityManager->flush();

        return $conversation;
    }
}