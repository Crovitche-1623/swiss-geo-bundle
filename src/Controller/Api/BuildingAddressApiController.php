<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Controller\Api;

use Crovitche\SwissGeoBundle\Entity\BuildingAddress;
use Crovitche\SwissGeoBundle\Repository\BuildingAddressRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, RequestStack};
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/addresses', name: 'swissgeo_api_addresses_')]
class BuildingAddressApiController extends AbstractController
{
    public function __construct(
        private readonly BuildingAddressRepository $repository,
        private readonly RequestStack $requestStack
    ) {
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        /** @var Request $request */
        $request = $this->requestStack->getCurrentRequest();

        $streetLocality = $request->query->get('street_locality');

        if (!$streetLocality) {
            throw new BadRequestException('The parameter street_locality is missing');
        }

        $addresses = $this->repository->findAllByStreetLocality(
            streetLocalityId: (int) $streetLocality,
            number: $request->query->get('address_number')
        );

        $formattedResponse = [];

        /** @var BuildingAddress $address */
        foreach ($addresses as $address) {
            $addressData['id'] = $address->getId();
            $addressData['text'] = $address->getNumber();
            $formattedResponse[] = $addressData;
        }

        return $this->json($formattedResponse);
    }
}
