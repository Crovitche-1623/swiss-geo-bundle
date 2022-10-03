<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Controller\Api;

use Crovitche\SwissGeoBundle\Repository\RegionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/regions', name: 'swissgeo_regions_')]
class RegionApiController extends AbstractController
{
    #[Route(path: '/', name: 'index', methods: ['GET'])]
    public function index(
        RegionRepository $repository,
        Request $request
    ): JsonResponse {
        $formattedRegions = [];
        foreach ($repository->findAll($request->query->get('region')) as $regionAbbreviation => $region) {
            $formattedRegions[] = [
                'id' => $regionAbbreviation,
                'text' => $region
            ];
        }

        return $this->json($formattedRegions);
    }
}
