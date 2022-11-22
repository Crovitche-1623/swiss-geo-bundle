<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Controller\Api;

use Crovitche\SwissGeoBundle\Repository\RegionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/regions', name: 'swissgeo_regions_')]
class RegionApiController extends AbstractController
{
    public function __construct(
        private readonly RegionRepository $repository,
        private readonly RequestStack $requestStack
    )
    {}

    #[Route(path: '/', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        /** @var  Request  $request */
        $request = $this->requestStack->getCurrentRequest();

        $formattedRegions = [];
        foreach ($this->repository->findAll($request->query->get('region')) as $regionAbbreviation => $region) {
            $formattedRegions[] = [
                'id' => $regionAbbreviation,
                'text' => $region
            ];
        }

        return $this->json($formattedRegions);
    }
}
