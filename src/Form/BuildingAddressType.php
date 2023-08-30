<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Form;

use Crovitche\SwissGeoBundle\Entity\BuildingAddress;
use Crovitche\SwissGeoBundle\Form\Type\RemoteEntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author  Thibault Gattolliat
 */
class BuildingAddressType extends AbstractType
{
    /**
     * {@inheritDoc}
     *
     * @static
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => BuildingAddress::class,
            'attr' => [
                'autocomplete' => false,
                'class' => '',
                'data-controller' => 'crovitche--swiss-geo-bundle--tom-select',
                'data-crovitche--swiss-geo-bundle--tom-select-url-value' => 'http://localhost:7701/indexes/addresses/search',
            ],
            'autocomplete-url' => 'http://localhost:7701/indexes/addresses/search',
            'payload-name' => 'q',
            'choice_label' => static function (?BuildingAddress $address): string {
                $streetLocality = $address?->getStreetLocality();
                $locality = $streetLocality?->getLocality();

                return
                    $streetLocality?->getStreet()?->getName().' '.
                    $address?->getNumber().' '.
                    $locality?->postalCodeAndLabel.' ('.
                    $locality?->regionAbbreviation.')'
                ;
            },
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @static
     */
    public function getParent(): string
    {
        return RemoteEntityType::class;
    }
}
