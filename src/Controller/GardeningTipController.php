<?php

namespace App\Controller;

use App\Repository\GardeningTipRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class GardeningTipController extends AbstractController
{
    #[Route('/api/test', name: 'app_gardening_tip_test')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/GardeningTipController.php',
        ]);
    }

    #[Route('/api/conseil', name: 'app_gardening_tip_list', methods: ['GET'])]
    public function getAllGardeningTips
    (
        GardeningTipRepository $gardeningTipRepository,
        SerializerInterface    $serializer
    ): JsonResponse
    {
        $gardeningTips = $gardeningTipRepository->findAll();

        if ($gardeningTips !== []) {
            $jsonGardeningTips = $serializer->serialize(data: $gardeningTips, format: 'json', context: ['groups' => 'gardening_tip:read']);
            return new JsonResponse($jsonGardeningTips, Response::HTTP_OK, [], true);
        } else {
            throw $this->createNotFoundException('Aucun conseil trouvé');
        }
    }

    #[Route('/api/conseil/{id}', name: 'app_gardening_tip_show', methods: ['GET'])]
    public function getGardeningTip
    (
        GardeningTipRepository $gardeningTipRepository,
        SerializerInterface    $serializer,
        int                    $id
    ): JsonResponse
    {
        $gardeningTip = $gardeningTipRepository->find($id);

        if ($gardeningTip !== null) {
            $jsonGardeningTip = $serializer->serialize(data: $gardeningTip, format: 'json', context: ['groups' => 'gardening_tip:read']);
            return new JsonResponse($jsonGardeningTip, Response::HTTP_OK, [], true);
        } else {
            throw $this->createNotFoundException('Conseil non trouvé');
        }
    }

    #[Route('/api/conseil/{id}', name: 'app_gardening_tip_delete', methods: ['DELETE'])]
    public function deleteGardeningTip
    (
        GardeningTipRepository $gardeningTipRepository,
        EntityManagerInterface $entityManager,
        int                    $id
    ): JsonResponse
    {
        $gardeningTip = $gardeningTipRepository->find($id);

        if ($gardeningTip !== null) {
            $entityManager->remove($gardeningTip);
            $entityManager->flush();
            return new JsonResponse('Conseil supprimé', Response::HTTP_NO_CONTENT);
        } else {
            throw $this->createNotFoundException('Conseil non trouvé');
        }
    }

    #[Route('/api/conseil', name: 'app_gardening_tip_create', methods: ['POST'])]
    public function createGardeningTip
    (
        Request                $request,
        EntityManagerInterface $entityManager,
        SerializerInterface    $serializer,
        ValidatorInterface     $validator
    ): JsonResponse
    {
        $json = $request->getContent();
        $gardeningTip = $serializer->deserialize($json, 'App\Entity\GardeningTip', 'json');

        $errors = $validator->validate($gardeningTip);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $entityManager->persist($gardeningTip);
        $entityManager->flush();

        return new JsonResponse('Conseil crée', Response::HTTP_CREATED);
    }

    #[Route('/api/conseil/{id}', name: 'app_gardening_tip_update', methods: ['PUT'])]
    public function updateGardeningTip
    (
        Request                $request,
        EntityManagerInterface $entityManager,
        SerializerInterface    $serializer,
        int                    $id,
        GardeningTipRepository $gardeningTipRepository,
        ValidatorInterface     $validator,
    ): JsonResponse
    {
        $gardeningTip = $gardeningTipRepository->find($id);

        if ($gardeningTip !== null) {
            $json = $request->getContent();
            $gardeningTip = $serializer->deserialize($json, 'App\Entity\GardeningTip', 'json');

            $errors = $validator->validate($gardeningTip);
            if ($errors->count() > 0) {
                return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
            }

            $entityManager->persist($gardeningTip);
            $entityManager->flush();

            return new JsonResponse('Conseil modifié', Response::HTTP_OK);
        } else {
            throw $this->createNotFoundException('Conseil non trouvé');
        }
    }
}
