<?php

namespace App\Controller;

use App\Entity\Sport;
use App\Repository\SportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class SportController extends AbstractController
{
    #[Route('/api/sports', name: 'sport', methods: ['GET'])]
    public function index(SportRepository $sportRepository, SerializerInterface $serializer): JsonResponse
    {
        try {

            $sports = $sportRepository->findAll();

            $jsonSports = $serializer->serialize($sports, 'json');

            return new JsonResponse($jsonSports, Response::HTTP_OK, [], true);

        } catch (\Exception $e) {
            return new JsonResponse(['error_message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    #[Route('/api/sports/new', name: 'createSport', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error_message' => 'Invalid JSON format'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $sport = $serializer->deserialize($request->getContent(), Sport::class, 'json');

            $entityManager->persist($sport);
            $entityManager->flush();

            return new JsonResponse(null, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['error_message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/sports/{id}', name: "updateSport", methods: ['PUT'])]
    public function update(Request $request, SerializerInterface $serializer, Sport $currentSport, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $updatedSport = $serializer->deserialize($request->getContent(),
                Sport::class,
                'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $currentSport]);

            $entityManager->persist($updatedSport);
            $entityManager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return new JsonResponse(['error_message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    #[Route('/api/sports/{id}', name: 'deleteSport', methods: ['DELETE'])]
    public function delete(Sport $sport, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $entityManager->remove($sport);
            $entityManager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);

        } catch (\Exception $e) {
            return new JsonResponse(['error_message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }
}
