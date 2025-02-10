<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Controller\Api;

use Crovitche\SwissGeoBundle\Entity\Locality;
use Crovitche\SwissGeoBundle\Repository\LocalityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, RequestStack};
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/localities', name: 'swissgeo_api_localities_')]
class LocalityApiController extends AbstractController
{
    public function __construct(
        private readonly LocalityRepository $repository,
        private readonly RequestStack $requestStack
    ) {
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        /** @var Request $request */
        $request = $this->requestStack->getCurrentRequest();

        $regionAbbreviation = $request->query->get('region_abbreviation');
        $postalCodeAndLabel = $request->query->get('postal_code_and_label');

        $localities = $this->repository->findAllBySearchCriteria(
            $regionAbbreviation,
            $postalCodeAndLabel
        );

        $formattedResponse = [];
        /** @var Locality $locality */
        foreach ($localities as $locality) {
            $itemData['id'] = $locality->getId();
            $itemData['text'] = $locality->postalCodeAndLabel;
            $formattedResponse[] = $itemData;
        }

        return $this->json($formattedResponse);
    }
}
