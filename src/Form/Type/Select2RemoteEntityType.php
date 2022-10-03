<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Form\Type;

use Crovitche\SwissGeoBundle\Form\DataTransformer\SlugToEntityTransformer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use JetBrains\PhpStorm\Pure;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author  Thibault Gattolliat
 */
class Select2RemoteEntityType extends AbstractType
{
    #[Pure]
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    )
    {}

    /**
     * {@inheritDoc}
     *
     * @static
     */
    public function buildView(
        FormView $view,
        FormInterface $form,
        array $options
    ): void
    {
        $view->vars['attr']['data-payload-name'] = $options['payload-name'];
        $view->vars['attr']['data-autocomplete-url'] = $options['autocomplete-url'];
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void
    {
        // PRE_SET_DATA also call PRE_SET_DATA when the form is added so
        // infinite loop must be stopped using a variable
        if (false === $options['form_has_been_added']) {
            $builder->addEventListener(
                eventName: FormEvents::PRE_SET_DATA,
                listener: function (FormEvent $event): void {
                    $form = $event->getForm();
                    $config = $form->getConfig();
                    $options = $config->getOptions();

                    // Set choices to data if there are any
                    $choices = new ArrayCollection();
                    if ($data = $event->getData()) {
                        $choices->add($data);
                    }
                    $options['choices'] = $choices;
                    $options['form_has_been_added'] = true;

                    $parentForm = $form->getParent();
                    $type = get_class($config->getType()->getInnerType());
                    if (!$parentForm) {
                        throw new LogicException(
                            message: sprintf("%s must have a parent form", $type)
                        );
                    }

                    // Replace the field
                    $parentForm->add(
                        child: $form->getName(),
                        type: $type,
                        options: $options
                    );
                }
            );
        }
        $builder->resetViewTransformers();
        $builder->addModelTransformer(
            new SlugToEntityTransformer(
                entityManager: $this->entityManager,
                className: $options['class'],
                queryBuilder: $options['query'] ? $options['query']() : null
            )
        );
    }

    /**
     * {@inheritDoc}
     *
     * @static
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined('query');
        $resolver->setAllowedTypes('query', [\Closure::class, 'null']);
        $resolver->setDefault('query', null);

        $resolver->setDefined('form_has_been_added');
        $resolver->setAllowedTypes('form_has_been_added', 'boolean');
        $resolver->setDefault('form_has_been_added', false);

        $resolver->setRequired('autocomplete-url');
        $resolver->setAllowedTypes('autocomplete-url', 'string');

        $resolver->setRequired('payload-name');
        $resolver->setAllowedTypes('payload-name', 'string');

        $resolver->setDefaults([
            'choices' => [],
            'choice_value' => static function (?object $object): string {
                if (!$object) {
                    return '';
                }

                if (method_exists($object, 'getSlug')) {
                    return $object->getSlug();
                }

                return (string) $object->getId();
            },
            'attr' => [
                'class' => 'select2-remote-single',
                'data-controller' => 'select2-remote-single'
            ]
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @static
     */
    public function getParent(): string
    {
        return EntityType::class;
    }
}
