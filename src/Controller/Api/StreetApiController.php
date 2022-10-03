<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Controller\Api;

use Crovitche\SwissGeoBundle\Entity\Street;
use Crovitche\SwissGeoBundle\Repository\StreetRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/streets', name: 'swissgeo_api_streets_')]
class StreetApiController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(
        StreetRepository $repository,
        Request $request
    ): JsonResponse {
        $locality = $request->query->get('locality');

        if (!$locality) {
            throw new BadRequestException("The parameter locality is missing");
        }

        $formattedResponse = [];

        $streets = $repository->findAllByLocality(
            localityId: (int) $locality,
            name: $request->query->get('name')
        );

        /** @var  Street  $street */
        foreach ($streets as $street) {
            $streetData['id'] = $street->getId();
            $streetData['text'] = $street->getName();
            $formattedResponse[] = $streetData;
        }

        return $this->json($formattedResponse);
    }
}
