<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Form;

use Crovitche\SwissGeoBundle\Entity\BuildingAddress;
use App\Form\Type\Select2RemoteEntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author  Thibault Gattolliat
 */
class BuildingAddressType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void
    {
        $builder
            ->add('number', Select2RemoteEntityType::class, [
                'class' => BuildingAddress::class,
                'attr' => [
                    'class' => 'meilisearch-select2-remote-single',
                    'data-controller' => 'meilisearch-select2-remote-single'
                ],
                'autocomplete-url' => 'http://localhost:7700/indexes/addresses/search',
                'payload-name' => 'q',
                'mapped' => false
            ]);
    }

    /**
     * {@inheritDoc}
     *
     * @static
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BuildingAddress::class
        ]);
    }
}
