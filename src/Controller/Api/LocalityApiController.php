<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Controller\Api;

use Crovitche\SwissGeoBundle\Entity\Locality;
use Crovitche\SwissGeoBundle\Repository\LocalityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/localities', name: 'swissgeo_api_localities_')]
class LocalityApiController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(
        LocalityRepository $repository,
        Request $request
    ): JsonResponse {
        $regionAbbreviation = $request->query->get('region_abbreviation');
        $postalCodeAndLabel = $request->query->get('postal_code_and_label');

        if ($regionAbbreviation &&
            !preg_match('/^[A-Z]{2}$/', $regionAbbreviation)) {
            throw new BadRequestException("Wrong region abbreviation format");
        }

        $localities = $repository->findAllBySearchCriteria(
            $regionAbbreviation,
            $postalCodeAndLabel
        );

        $formattedResponse = [];
        /** @var  Locality  $locality */
        foreach ($localities as $locality) {
            $itemData['id'] = $locality->getId();
            $itemData['text'] = $locality->postalCodeAndLabel;
            $formattedResponse[] = $itemData;
        }

        return $this->json($formattedResponse);
    }
}
