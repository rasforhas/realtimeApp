<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Participant;
use App\Repository\ConversationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/conversations", name="conversations.")
 */
class ConversationController extends AbstractController
{
    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var ConversationRepository
     */
    private $conversationRepository;

    /**
     * ConversationController constructor.
     * @param UserRepository $userRepository
     * @param EntityManager $entityManager
     * @param ConversationRepository $conversationRepository
     */
    public function __construct(UserRepository $userRepository, EntityManager $entityManager, ConversationRepository $conversationRepository)
    {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->conversationRepository = $conversationRepository;
    }

    /**
     * @Route("/{id}", name="getConversations")
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function index(Request $request, int $id)
    {
        $otherUser = $request->get('otherUser', 0);
        $otherUser = $this->userRepository->find($id);

        if(is_null($otherUser))
        {
            throw new \Exception("User not found");
        }

        //cannot create conv. w/ yourself
        if($otherUser->getId() === $this->getUser()->getId())
        {
            throw new \Exception("You cannot create conversation w/ yourself");
        }

        //check if conv already exists
        $conversation = $this->conversationRepository->findConversationByParticipants(
            $otherUser->getId(),
            $this->getUser()->getId()
        );
        if (count($conversation))
        {
            throw new \Exception("The conversation already exists");
        }
        $conversation = new Conversation();
        $participant = new Participant();
        $participant->setUser($this->getUser());
        $participant->setConversation($conversation);

        $otherParticipant = new Participant();
        $otherParticipant->setUser($otherUser);
        $otherParticipant->setConversation($conversation);

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $this->entityManager->persist($conversation);
            $this->entityManager->persist($participant);
            $this->entityManager->persist($otherParticipant);

            $this->entityManager->flush();
            $this->entityManager->commit();

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }


        return $this->json([
            'id' => $conversation->getId()
        ], Response::HTTP_CREATED, [], []);
    }
}
