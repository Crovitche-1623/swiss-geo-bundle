<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Form;

use App\Form\Type\Select2ChoiceType;
use Crovitche\SwissGeoBundle\Repository\RegionRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author  Thibault Gattolliat
 */
class RegionType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Merging would be better instead of overriding
            'attr' => [
                'class' => 'select2-single',
                'data-controller' => 'select2-single region-type',
            ],
            'choices' => \array_flip(RegionRepository::getRegions()),
            'label' => 'Canton',
            'mapped' => false,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getParent(): string
    {
        return Select2ChoiceType::class;
    }
}
