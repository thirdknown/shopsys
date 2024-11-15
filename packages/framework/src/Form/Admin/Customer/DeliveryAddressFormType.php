<?php

namespace Shopsys\FrameworkBundle\Form\Admin\Customer;

use Shopsys\FrameworkBundle\Form\GroupType;
use Shopsys\FrameworkBundle\Form\ValidationGroup;
use Shopsys\FrameworkBundle\Model\Country\CountryFacade;
use Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class DeliveryAddressFormType extends AbstractType
{
    public const VALIDATION_GROUP_DIFFERENT_DELIVERY_ADDRESS = 'differentDeliveryAddress';

    /**
     * @var \Shopsys\FrameworkBundle\Model\Country\CountryFacade
     */
    private $countryFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Country\CountryFacade $countryFacade
     */
    public function __construct(CountryFacade $countryFacade)
    {
        $this->countryFacade = $countryFacade;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $countries = $this->countryFacade->getAllEnabledOnDomain($options['domain_id']);

        $builderDeliveryAddress = $builder->create('deliveryAddress', GroupType::class, [
            'label' => t('Delivery address'),
            'attr' => [
                'id' => 'customer_form_deliveryAddressData',
            ],
        ]);
        $builderDeliveryAddress
            ->add(
                $builderDeliveryAddress
                    ->create('deliveryAddressFields', FormType::class, [
                        'inherit_data' => true,
                        'attr' => [
                            'class' => 'js-delivery-address-fields form-line__js',
                        ],
                        'render_form_row' => false,
                    ])
                    ->add('companyName', TextType::class, [
                        'required' => false,
                        'constraints' => [
                            new Constraints\Length([
                                'max' => 100,
                                'maxMessage' => 'Company name cannot be longer than {{ limit }} characters',
                            ]),
                        ],
                        'label' => t('Company'),
                    ])
                    ->add('firstName', TextType::class, [
                        'required' => true,
                        'constraints' => [
                            new Constraints\NotBlank([
                                'message' => 'Please enter first name of contact person',
                                'groups' => [static::VALIDATION_GROUP_DIFFERENT_DELIVERY_ADDRESS],
                            ]),
                            new Constraints\Length([
                                'max' => 100,
                                'maxMessage' => 'First name of contact person cannot be longer than {{ limit }} characters',
                            ]),
                        ],
                        'label' => t('First name'),
                    ])
                    ->add('lastName', TextType::class, [
                        'required' => true,
                        'constraints' => [
                            new Constraints\NotBlank([
                                'message' => 'Please enter last name of contact person',
                                'groups' => [static::VALIDATION_GROUP_DIFFERENT_DELIVERY_ADDRESS],
                            ]),
                            new Constraints\Length([
                                'max' => 100,
                                'maxMessage' => 'Last name of contact person cannot be longer than {{ limit }} characters',
                            ]),
                        ],
                        'label' => t('Last name'),
                    ])
                    ->add('telephone', TextType::class, [
                        'required' => false,
                        'constraints' => [
                            new Constraints\Length([
                                'max' => 30,
                                'maxMessage' => 'Telephone number cannot be longer than {{ limit }} characters',
                            ]),
                        ],
                        'label' => t('Telephone'),
                    ])
                    ->add('street', TextType::class, [
                        'required' => true,
                        'constraints' => [
                            new Constraints\NotBlank([
                                'message' => 'Please enter street',
                                'groups' => [static::VALIDATION_GROUP_DIFFERENT_DELIVERY_ADDRESS],
                            ]),
                            new Constraints\Length([
                                'max' => 100,
                                'maxMessage' => 'Street name cannot be longer than {{ limit }} characters',
                                'groups' => [static::VALIDATION_GROUP_DIFFERENT_DELIVERY_ADDRESS],
                            ]),
                        ],
                        'label' => t('Street'),
                    ])
                    ->add('city', TextType::class, [
                        'required' => true,
                        'constraints' => [
                            new Constraints\NotBlank([
                                'message' => 'Please enter city',
                                'groups' => [static::VALIDATION_GROUP_DIFFERENT_DELIVERY_ADDRESS],
                            ]),
                            new Constraints\Length([
                                'max' => 100,
                                'maxMessage' => 'City name cannot be longer than {{ limit }} characters',
                                'groups' => [static::VALIDATION_GROUP_DIFFERENT_DELIVERY_ADDRESS],
                            ]),
                        ],
                        'label' => t('City'),
                    ])
                    ->add('postcode', TextType::class, [
                        'required' => true,
                        'constraints' => [
                            new Constraints\NotBlank([
                                'message' => 'Please enter zip code',
                                'groups' => [static::VALIDATION_GROUP_DIFFERENT_DELIVERY_ADDRESS],
                            ]),
                            new Constraints\Length([
                                'max' => 30,
                                'maxMessage' => 'Zip code cannot be longer than {{ limit }} characters',
                                'groups' => [static::VALIDATION_GROUP_DIFFERENT_DELIVERY_ADDRESS],
                            ]),
                        ],
                        'label' => t('Postcode'),
                    ])
                    ->add('country', ChoiceType::class, [
                        'required' => true,
                        'choices' => $countries,
                        'choice_label' => 'name',
                        'choice_value' => 'id',
                        'constraints' => [
                            new Constraints\NotBlank(['message' => 'Please choose country']),
                        ],
                        'label' => t('Country'),
                    ])
            );

        $builder->add($builderDeliveryAddress);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired('domain_id')
            ->addAllowedTypes('domain_id', 'int')
            ->setDefaults([
                'data_class' => DeliveryAddressData::class,
                'attr' => ['novalidate' => 'novalidate'],
                'validation_groups' => function (FormInterface $form) {
                    $validationGroups = [ValidationGroup::VALIDATION_GROUP_DEFAULT];

                    /** @var \Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressData $deliveryAddressData */
                    $deliveryAddressData = $form->getData();

                    if ($deliveryAddressData->addressFilled) {
                        $validationGroups[] = static::VALIDATION_GROUP_DIFFERENT_DELIVERY_ADDRESS;
                    }

                    return $validationGroups;
                },
            ]);
    }
}
