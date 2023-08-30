<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Controller\Api;

use Crovitche\SwissGeoBundle\Entity\Street;
use Crovitche\SwissGeoBundle\Repository\StreetRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, RequestStack};
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/streets', name: 'swissgeo_api_streets_')]
class StreetApiController extends AbstractController
{
    public function __construct(
        private readonly StreetRepository $repository,
        private readonly RequestStack $requestStack
    ) {
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        /** @var Request $request */
        $request = $this->requestStack->getCurrentRequest();

        $locality = $request->query->get('locality');

        if (!$locality) {
            throw new BadRequestException('The parameter locality is missing');
        }

        $formattedResponse = [];

        $streets = $this->repository->findAllByLocality(
            localityId: (int) $locality,
            name: $request->query->get('name')
        );

        /** @var Street $street */
        foreach ($streets as $street) {
            $streetData['id'] = $street->getId();
            $streetData['text'] = $street->getName();
            $formattedResponse[] = $streetData;
        }

        return $this->json($formattedResponse);
    }
}
