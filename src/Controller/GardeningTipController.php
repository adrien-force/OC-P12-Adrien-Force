<?php

namespace App\Controller;

use App\Entity\GardeningTip;
use App\Repository\GardeningTipRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class GardeningTipController extends AbstractController
{
    #[Route('/api/conseil', name: 'app_gardening_tip_list', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Retourne la liste des conseils de jardinage',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: GardeningTip::class, groups: ['gardening_tip:read']))
        )
    )]
    public function getAllGardeningTips
    (
        GardeningTipRepository $gardeningTipRepository,
        SerializerInterface    $serializer,
        TagAwareCacheInterface $cache
    ): JsonResponse
    {
        $idCache = sprintf("gardening_tips");
        $gardeningTips = $cache->get($idCache, function(ItemInterface $item) use ($gardeningTipRepository, $serializer) {
            $item->tag("gardening_tips");
            $item->expiresAfter(3600);
            $gardeningTips = $gardeningTipRepository->findAll();
            if ($gardeningTips === []) {
                throw $this->createNotFoundException('Aucun conseil trouvé');
            }
            return $serializer->serialize(data: $gardeningTips, format: 'json', context: ['groups' => 'gardening_tip:read']);
        });

        return new JsonResponse($gardeningTips, Response::HTTP_OK, [], true);

    }

    #[Route('/api/conseil/{month}', name: 'app_gardening_tip_month', methods: ['GET'])]
    #[OA\Parameter(
        name: 'month',
        description: 'Numéro du mois',
        in: 'path',
        required: true,
    )]
    #[OA\Schema(type: 'integer')]
    #[OA\Response(
        response: 200,
        description: 'Retourne la liste des conseils de jardinage d\'un mois donné',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: GardeningTip::class, groups: ['gardening_tip:read']))
        )
    )]
    public function getGardeningTipsFromMonth
    (
        GardeningTipRepository $gardeningTipRepository,
        SerializerInterface    $serializer,
        int                 $month,
        TagAwareCacheInterface $cache
    ): JsonResponse
    {
        if ($month < 1 || $month > 12) {
            throw new BadRequestHttpException('Le mois doit être compris entre 1 et 12');
        }

        $idCache = sprintf("gardening_tips_month_%s", $month);
        $gardeningTips = $cache->get($idCache, function(ItemInterface $item) use ($gardeningTipRepository, $serializer, $month) {
            $item->tag("gardening_tips_month_$month");
            $item->expiresAfter(3600);
            $gardeningTips = $gardeningTipRepository->findByMonth($month);
            if ($gardeningTips === []) {
                throw $this->createNotFoundException('Aucun conseil trouvé');
            }
            return $serializer->serialize(data: $gardeningTips, format: 'json', context: ['groups' => 'gardening_tip:read']);
        });
        return new JsonResponse($gardeningTips, Response::HTTP_OK, [], true);
    }

    #[Route('/api/conseil/{id}', name: 'app_gardening_tip_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Seul un administrateur peut supprimer un conseil', statusCode: Response::HTTP_FORBIDDEN)]
    #[OA\Parameter(
        name: 'id',
        description: 'Identifiant du conseil',
        in: 'path',
        required: true,
    )]
    #[OA\Schema(type: 'integer')]
    #[OA\Response(
        response: 200,
        description: 'Permet de supprimer un conseil de jardinage',
        )]
    public function deleteGardeningTip
    (
        GardeningTipRepository $gardeningTipRepository,
        EntityManagerInterface $entityManager,
        int                    $id,
        TagAwareCacheInterface $cache
    ): JsonResponse
    {
        if (empty($id) || ctype_space($id)) {
            throw new BadRequestHttpException('L\'identifiant du conseil ne peut pas être vide ou constitué d\'espaces blancs');
        }

        $gardeningTip = $gardeningTipRepository->find($id);

        if ($gardeningTip === null) {
            throw $this->createNotFoundException('Conseil non trouvé');
        }

        $this->invalidateGardeningTipCache($cache, $gardeningTip);
        $entityManager->remove($gardeningTip);
        $entityManager->flush();
        return new JsonResponse('Conseil supprimé', Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/conseil', name: 'app_gardening_tip_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Seul un administrateur peut créer un conseil', statusCode: Response::HTTP_FORBIDDEN)]
    #[OA\RequestBody(
        description: 'Les données pour créer un conseil de jardinage',
        required: true,
        content: new OA\JsonContent(
            required: ['content'],
            properties: [
                new OA\Property(property: 'content', description: 'Le contenu du conseil de jardinage', type: 'string')
            ],
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Permets de créer un conseil de jardinage',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: GardeningTip::class, groups: ['gardening_tip:write']))
        )
    )]
    public function createGardeningTip
    (
        Request                $request,
        EntityManagerInterface $entityManager,
        SerializerInterface    $serializer,
        ValidatorInterface     $validator,
        TagAwareCacheInterface $cache
    ): JsonResponse
    {
        $json = $request->getContent();

        if (empty($json)) {
            return new JsonResponse('Le contenu du conseil ne peut pas être vide');
        }

        $gardeningTip = $serializer->deserialize($json, 'App\Entity\GardeningTip', 'json');
        $errors = $validator->validate($gardeningTip);

        if ($errors->count() > 0) {
            throw new ValidationFailedException($gardeningTip, $errors);
        }

        $this->invalidateGardeningTipCache($cache, $gardeningTip);
        $entityManager->persist($gardeningTip);
        $entityManager->flush();

        return new JsonResponse('Conseil crée', Response::HTTP_CREATED);
    }

    #[Route('/api/conseil/{id}', name: 'app_gardening_tip_update', methods: ['PUT'])]
    #[isGranted('ROLE_ADMIN', message: 'Seul un administrateur peut modifier un conseil', statusCode: Response::HTTP_FORBIDDEN)]
    #[OA\Parameter(
        name: 'id',
        description: 'Identifiant du conseil',
        in: 'path',
        required: true,
    )]
    #[OA\Schema(type: 'integer')]
    #[OA\RequestBody(
        description: 'Les données pour créer un conseil de jardinage',
        required: true,
        content: new OA\JsonContent(
            required: ['content'],
            properties: [
                new OA\Property(property: 'content', description: 'Le contenu du conseil de jardinage', type: 'string')
            ],
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Permet de modifier un conseil de jardinage',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: GardeningTip::class, groups: ['gardening_tip:write']))
        )
    )]
    public function updateGardeningTip
    (
        Request                $request,
        EntityManagerInterface $entityManager,
        SerializerInterface    $serializer,
        int                    $id,
        GardeningTipRepository $gardeningTipRepository,
        ValidatorInterface     $validator,
        TagAwareCacheInterface $cache
    ): JsonResponse
    {
        $gardeningTip = $gardeningTipRepository->find($id);

        if ($gardeningTip === null) {
            throw $this->createNotFoundException('Conseil non trouvé');
        }

        $json = $request->getContent();

        if (empty($json)) {
            throw new BadRequestHttpException('Le contenu du conseil que vous souhaitez modifier ne peut pas être vide');
        }

        $serializer->deserialize(
            data: $json,
            type:'App\Entity\GardeningTip',
            format:'json',
            context: ['object_to_populate' => $gardeningTip]
        );

        $errors = $validator->validate($gardeningTip);
        if ($errors->count() > 0) {
            throw new ValidationFailedException($gardeningTip, $errors);
        }

        $this->invalidateGardeningTipCache($cache, $gardeningTip);
        $entityManager->persist($gardeningTip);
        $entityManager->flush();

        return new JsonResponse('Conseil modifié', Response::HTTP_OK);
    }

    private function invalidateGardeningTipCache(TagAwareCacheInterface $cache, GardeningTip $gardeningTip): void
    {
        $cache->invalidateTags(["gardening_tips"]);
        if ($month = $gardeningTip->getMonth()) {
            $cache->invalidateTags(["gardening_tips_month_$month"]);
        }
    }
}
