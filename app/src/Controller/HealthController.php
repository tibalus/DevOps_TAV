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
use OpenApi\Attributes as OA;

#[Route('/health')]
class HealthController extends AbstractController
{
    private EntityManagerInterface $em;
    private SerializerInterface $serializer;

    public function __construct(EntityManagerInterface $em, SerializerInterface $serializer)
    {
        $this->em = $em;
        $this->serializer = $serializer;
    }

    // GET /health
    #[Route('', name: 'health_check', methods: ['GET'])]
    #[OA\Get(
        path: '/health',
        summary: 'Health Check',
        description: 'Vérification du statut de l\'API et de la connexion à la base de données',
        tags: ['Health Check']
    )]
    #[OA\Response(
        response: 200,
        description: 'API et base de données fonctionnent correctement',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'api',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'ok'),
                        new OA\Property(property: 'timestamp', type: 'string', example: '2025-09-19T10:30:00+00:00')
                    ]
                ),
                new OA\Property(
                    property: 'database',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'ok'),
                        new OA\Property(property: 'message', type: 'string', example: 'Database connection successful')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 503,
        description: 'Problème de connexion à la base de données',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'api',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'ok'),
                        new OA\Property(property: 'timestamp', type: 'string', example: '2025-09-19T10:30:00+00:00')
                    ]
                ),
                new OA\Property(
                    property: 'database',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(property: 'message', type: 'string', example: 'Database connection failed'),
                        new OA\Property(property: 'error', type: 'string', example: 'Connection refused')
                    ]
                )
            ]
        )
    )]
    public function check(): JsonResponse
    {
        $healthData = [
            'api' => [
                'status' => 'ok',
                'timestamp' => (new \DateTime())->format(\DateTime::ISO8601)
            ],
            'database' => $this->checkDatabaseConnection()
        ];

        $httpStatus = $healthData['database']['status'] === 'ok' ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE;

        return new JsonResponse($healthData, $httpStatus);
    }

    private function checkDatabaseConnection(): array
    {
        try {
            // Test de la connexion à la base de données
            $connection = $this->em->getConnection();
            $connection->executeQuery('SELECT 1');
            
            return [
                'status' => 'ok',
                'message' => 'Database connection successful'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed',
                'error' => $e->getMessage()
            ];
        }
    }
}