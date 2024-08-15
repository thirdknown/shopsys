<?php

declare(strict_types=1);

namespace Shopsys\FrameworkBundle\Controller\Admin;

use Shopsys\FrameworkBundle\Component\Router\Security\Annotation\CsrfProtection;
use Shopsys\FrameworkBundle\Form\Admin\SalesRepresentative\SalesRepresentativeFormType;
use Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserFacade;
use Shopsys\FrameworkBundle\Model\SalesRepresentative\Exception\SalesRepresentativeNotFoundException;
use Shopsys\FrameworkBundle\Model\SalesRepresentative\SalesRepresentativeDataFactory;
use Shopsys\FrameworkBundle\Model\SalesRepresentative\SalesRepresentativeFacade;
use Shopsys\FrameworkBundle\Model\SalesRepresentative\SalesRepresentativeGridFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SalesRepresentativeController extends AdminBaseController
{
    /**
     * @param \Shopsys\FrameworkBundle\Model\SalesRepresentative\SalesRepresentativeDataFactory $salesRepresentativeDataFactory
     * @param \Shopsys\FrameworkBundle\Model\SalesRepresentative\SalesRepresentativeFacade $salesRepresentativeFacade
     * @param \Shopsys\FrameworkBundle\Model\SalesRepresentative\SalesRepresentativeGridFactory $salesRepresentativeGridFactory
     * @param \Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider $breadcrumbOverrider
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserFacade $customerUserFacade
     */
    public function __construct(
        protected readonly SalesRepresentativeDataFactory $salesRepresentativeDataFactory,
        protected readonly SalesRepresentativeFacade $salesRepresentativeFacade,
        protected readonly SalesRepresentativeGridFactory $salesRepresentativeGridFactory,
        protected readonly BreadcrumbOverrider $breadcrumbOverrider,
        protected readonly CustomerUserFacade $customerUserFacade,
    ) {
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[Route(path: '/sales-representative/list/')]
    public function listAction(): Response
    {
        $grid = $this->salesRepresentativeGridFactory->create();

        return $this->render('@ShopsysFramework/Admin/Content/SalesRepresentative/list.html.twig', [
            'gridView' => $grid->createView(),
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[Route(path: '/sales-representative/new/')]
    public function newAction(Request $request): Response
    {
        $salesRepresentativeData = $this->salesRepresentativeDataFactory->create();
        $form = $this->createForm(SalesRepresentativeFormType::class, $salesRepresentativeData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $salesRepresentative = $this->salesRepresentativeFacade->create($salesRepresentativeData);

            $this->addSuccessFlashTwig(
                t('Sales representative <strong><a href="{{ url }}">{{ label }}</a></strong> was created'),
                [
                    'label' => $salesRepresentative->hasNoneOfNamesSet() ? $salesRepresentative->getId() : $salesRepresentative->getFullName(),
                    'url' => $this->generateUrl('admin_salesrepresentative_edit', ['id' => $salesRepresentative->getId()]),
                ],
            );

            return $this->redirectToRoute('admin_salesrepresentative_list');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addErrorFlash(t('Please check the correctness of all data filled.'));
        }

        return $this->render('@ShopsysFramework/Admin/Content/SalesRepresentative/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[Route(path: '/sales-representative/edit/{id}', requirements: ['id' => '\d+'])]
    public function editAction(Request $request, int $id): Response
    {
        $salesRepresentative = $this->salesRepresentativeFacade->getById($id);
        $salesRepresentativeData = $this->salesRepresentativeDataFactory->createFromSalesRepresentative($salesRepresentative);

        $form = $this->createForm(SalesRepresentativeFormType::class, $salesRepresentativeData, [
            'salesRepresentative' => $salesRepresentative,
        ]);
        $form->handleRequest($request);

        $label = $salesRepresentative->hasNoneOfNamesSet() ? $salesRepresentative->getId() : $salesRepresentative->getFullName();

        if ($form->isSubmitted() && $form->isValid()) {
            $this->salesRepresentativeFacade->edit($salesRepresentative, $salesRepresentativeData);

            $this->addSuccessFlashTwig(
                t('Sales representative <strong><a href="{{ url }}">{{ label }}</a></strong> was edited'),
                [
                    'label' => $label,
                    'url' => $this->generateUrl('admin_salesrepresentative_edit', ['id' => $id]),
                ],
            );

            return $this->redirectToRoute('admin_salesrepresentative_list');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addErrorFlash(t('Please check the correctness of all data filled.'));
        }

        $this->breadcrumbOverrider->overrideLastItem(
            t('Editing sales representative - %label%', [
                'label' => $label,
            ]),
        );

        return $this->render('@ShopsysFramework/Admin/Content/SalesRepresentative/edit.html.twig', [
            'form' => $form->createView(),
            'salesRepresentative' => $salesRepresentative,
        ]);
    }

    /**
     * @CsrfProtection
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[Route(path: '/sales-representative/delete/{id}', requirements: ['id' => '\d+'])]
    public function deleteAction(int $id): Response
    {
        $customersUsingThisSalesRepresentative = $this->customerUserFacade->findEmailsOfCustomerUsersUsingSalesRepresentative($id);

        if (count($customersUsingThisSalesRepresentative) !== 0) {
            $this->addErrorFlashTwig(
                t('Sales representative cannot be deleted, because some customers are using it: {{ customers }}'),
                [
                    'customers' => implode(', ', $customersUsingThisSalesRepresentative),
                ],
            );

            return $this->redirectToRoute('admin_salesrepresentative_list');
        }

        try {
            $salesRepresentative = $this->salesRepresentativeFacade->getById($id);

            $this->salesRepresentativeFacade->delete($id);
            $this->addSuccessFlashTwig(
                t('Sales representative <strong>{{ label }}</strong> deleted.'),
                [
                    'label' => $salesRepresentative->hasNoneOfNamesSet() ? $salesRepresentative->getId() : $salesRepresentative->getFullName(),
                ],
            );
        } catch (SalesRepresentativeNotFoundException $ex) {
            $this->addErrorFlash(t('Selected sales representative doesn\'t exist.'));
        }

        return $this->redirectToRoute('admin_salesrepresentative_list');
    }
}
