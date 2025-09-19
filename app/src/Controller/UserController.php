<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api/users')]
class UserController extends AbstractController
{
    private EntityManagerInterface $em;
    private SerializerInterface $serializer;

    public function __construct(EntityManagerInterface $em, SerializerInterface $serializer)
    {
        $this->em = $em;
        $this->serializer = $serializer;
    }

    // GET /api/users
    #[Route('', name: 'user_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $users = $this->em->getRepository(User::class)->findAll();
        $data = $this->serializer->serialize($users, 'json');
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    // GET /api/users/{uuid}
    #[Route('/{id}', name: 'user_get', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        $user = $this->em->getRepository(User::class)->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        $data = $this->serializer->serialize($user, 'json');
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    // POST /api/users
    #[Route('', name: 'user_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = new User();
        $user->setFullname($data['fullname'] ?? '');
        $user->setStudyLevel($data['study_level'] ?? '');
        $user->setAge((int)($data['age'] ?? 0));

        $this->em->persist($user);
        $this->em->flush();

        $responseData = $this->serializer->serialize($user, 'json');
        return new JsonResponse($responseData, Response::HTTP_CREATED, [], true);
    }

    // PUT /api/users/{uuid}
    #[Route('/{id}', name: 'user_update', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $user = $this->em->getRepository(User::class)->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $user->setFullname($data['fullname'] ?? $user->getFullname());
        $user->setStudyLevel($data['study_level'] ?? $user->getStudyLevel());
        $user->setAge((int)($data['age'] ?? $user->getAge()));

        $this->em->flush();

        $responseData = $this->serializer->serialize($user, 'json');
        return new JsonResponse($responseData, Response::HTTP_OK, [], true);
    }

    // DELETE /api/users/{uuid}
    #[Route('/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $user = $this->em->getRepository(User::class)->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($user);
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
