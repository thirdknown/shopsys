<?php

namespace Shopsys\FrameworkBundle\Controller\Admin;

use Shopsys\FrameworkBundle\Component\Grid\GridFactory;
use Shopsys\FrameworkBundle\Component\Grid\QueryBuilderDataSource;
use Shopsys\FrameworkBundle\Component\Router\Security\Annotation\CsrfProtection;
use Shopsys\FrameworkBundle\Form\Admin\Administrator\AdministratorFormType;
use Shopsys\FrameworkBundle\Model\Administrator\Activity\AdministratorActivityFacade;
use Shopsys\FrameworkBundle\Model\Administrator\Administrator;
use Shopsys\FrameworkBundle\Model\Administrator\AdministratorDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Administrator\AdministratorFacade;
use Shopsys\FrameworkBundle\Model\Administrator\Exception\AdministratorNotFoundException;
use Shopsys\FrameworkBundle\Model\Administrator\Exception\DeletingLastAdministratorException;
use Shopsys\FrameworkBundle\Model\Administrator\Exception\DeletingSelfException;
use Shopsys\FrameworkBundle\Model\Administrator\Exception\DuplicateUserNameException;
use Shopsys\FrameworkBundle\Model\Administrator\Security\AdministratorRolesChangedFacade;
use Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AdministratorController extends AdminBaseController
{
    protected const MAX_ADMINISTRATOR_ACTIVITIES_COUNT = 10;

    /**
     * @var \Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider
     */
    protected $breadcrumbOverrider;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Administrator\AdministratorFacade
     */
    protected $administratorFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Grid\GridFactory
     */
    protected $gridFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Administrator\Activity\AdministratorActivityFacade
     */
    protected $administratorActivityFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Administrator\AdministratorDataFactoryInterface
     */
    protected $administratorDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Administrator\Security\AdministratorRolesChangedFacade
     */
    protected $administratorRolesChangedFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Administrator\AdministratorFacade $administratorFacade
     * @param \Shopsys\FrameworkBundle\Component\Grid\GridFactory $gridFactory
     * @param \Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider $breadcrumbOverrider
     * @param \Shopsys\FrameworkBundle\Model\Administrator\Activity\AdministratorActivityFacade $administratorActivityFacade
     * @param \Shopsys\FrameworkBundle\Model\Administrator\AdministratorDataFactoryInterface $administratorDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Administrator\Security\AdministratorRolesChangedFacade $administratorRolesChangedFacade
     */
    public function __construct(
        AdministratorFacade $administratorFacade,
        GridFactory $gridFactory,
        BreadcrumbOverrider $breadcrumbOverrider,
        AdministratorActivityFacade $administratorActivityFacade,
        AdministratorDataFactoryInterface $administratorDataFactory,
        AdministratorRolesChangedFacade $administratorRolesChangedFacade
    ) {
        $this->administratorFacade = $administratorFacade;
        $this->gridFactory = $gridFactory;
        $this->breadcrumbOverrider = $breadcrumbOverrider;
        $this->administratorActivityFacade = $administratorActivityFacade;
        $this->administratorDataFactory = $administratorDataFactory;
        $this->administratorRolesChangedFacade = $administratorRolesChangedFacade;
    }

    /**
     * @Route("/administrator/list/")
     */
    public function listAction()
    {
        $queryBuilder = $this->administratorFacade->getAllListableQueryBuilder();
        $dataSource = new QueryBuilderDataSource($queryBuilder, 'a.id');

        $grid = $this->gridFactory->create('administratorList', $dataSource);
        $grid->setDefaultOrder('realName');

        $grid->addColumn('realName', 'a.realName', t('Full name'), true);
        $grid->addColumn('email', 'a.email', t('Email'));

        $grid->setActionColumnClassAttribute('table-col table-col-10');
        $grid->addEditActionColumn('admin_administrator_edit', ['id' => 'a.id']);
        $grid->addDeleteActionColumn('admin_administrator_delete', ['id' => 'a.id'])
            ->setConfirmMessage(t('Do you really want to remove this administrator?'));

        $grid->setTheme('@ShopsysFramework/Admin/Content/Administrator/listGrid.html.twig');

        return $this->render('@ShopsysFramework/Admin/Content/Administrator/list.html.twig', [
            'gridView' => $grid->createView(),
        ]);
    }

    /**
     * @Route("/administrator/edit/{id}", requirements={"id" = "\d+"})
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $id
     */
    public function editAction(Request $request, int $id)
    {
        $administrator = $this->administratorFacade->getById($id);

        $loggedUser = $this->getUser();

        if (!$loggedUser instanceof Administrator) {
            throw new AccessDeniedException(sprintf(
                'Logged user is not instance of "%s". That should not happen due to security.yaml configuration.',
                Administrator::class
            ));
        }

        if ($administrator->isSuperadmin() && !$loggedUser->isSuperadmin()) {
            $message = 'Superadmin can only be edited by superadmin.';

            throw new AccessDeniedException($message);
        }

        $administratorData = $this->administratorDataFactory->createFromAdministrator($administrator);

        $form = $this->createForm(AdministratorFormType::class, $administratorData, [
            'administrator' => $administrator,
            'scenario' => AdministratorFormType::SCENARIO_EDIT,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->administratorFacade->edit($id, $administratorData);

                if ($loggedUser->getId() === $id) {
                    $this->administratorRolesChangedFacade->refreshAdministratorToken($administrator);
                }

                $this->addSuccessFlashTwig(
                    t('Administrator <strong><a href="{{ url }}">{{ name }}</a></strong> modified'),
                    [
                        'name' => $administratorData->realName,
                        'url' => $this->generateUrl('admin_administrator_edit', ['id' => $administrator->getId()]),
                    ]
                );

                return $this->redirectToRoute('admin_administrator_list');
            } catch (DuplicateUserNameException $ex) {
                $this->addErrorFlashTwig(
                    t('Login name <strong>{{ name }}</strong> is already used'),
                    [
                        'name' => $administratorData->username,
                    ]
                );
            }
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addErrorFlash(t('Please check the correctness of all data filled.'));
        }

        $this->breadcrumbOverrider->overrideLastItem(
            t('Editing administrator - %name%', ['%name%' => $administrator->getRealName()])
        );

        $lastAdminActivities = $this->administratorActivityFacade->getLastAdministratorActivities(
            $administrator,
            static::MAX_ADMINISTRATOR_ACTIVITIES_COUNT
        );

        return $this->render('@ShopsysFramework/Admin/Content/Administrator/edit.html.twig', [
            'form' => $form->createView(),
            'administrator' => $administrator,
            'lastAdminActivities' => $lastAdminActivities,
        ]);
    }

    /**
     * @Route("/administrator/my-account/")
     */
    public function myAccountAction()
    {
        /** @var \Shopsys\FrameworkBundle\Model\Administrator\Administrator $loggedUser */
        $loggedUser = $this->getUser();

        return $this->redirectToRoute('admin_administrator_edit', [
            'id' => $loggedUser->getId(),
        ]);
    }

    /**
     * @Route("/administrator/new/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function newAction(Request $request)
    {
        $form = $this->createForm(AdministratorFormType::class, $this->administratorDataFactory->create(), [
            'scenario' => AdministratorFormType::SCENARIO_CREATE,
            'administrator' => null,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $administratorData = $form->getData();

            try {
                $administrator = $this->administratorFacade->create($administratorData);

                $this->addSuccessFlashTwig(
                    t('Administrator <strong><a href="{{ url }}">{{ name }}</a></strong> created'),
                    [
                        'name' => $administrator->getRealName(),
                        'url' => $this->generateUrl('admin_administrator_edit', ['id' => $administrator->getId()]),
                    ]
                );

                return $this->redirectToRoute('admin_administrator_list');
            } catch (DuplicateUserNameException $ex) {
                $this->addErrorFlashTwig(
                    t('Login name <strong>{{ name }}</strong> is already used'),
                    [
                        'name' => $administratorData->username,
                    ]
                );
            }
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addErrorFlash(t('Please check the correctness of all data filled.'));
        }

        return $this->render('@ShopsysFramework/Admin/Content/Administrator/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/administrator/delete/{id}", requirements={"id" = "\d+"})
     * @CsrfProtection
     * @param int $id
     */
    public function deleteAction($id)
    {
        try {
            $realName = $this->administratorFacade->getById($id)->getRealName();

            $this->administratorFacade->delete($id);
            $this->addSuccessFlashTwig(
                t('Administrator <strong>{{ name }}</strong> deleted.'),
                [
                    'name' => $realName,
                ]
            );
        } catch (DeletingSelfException $ex) {
            $this->addErrorFlash(t('You can\'t delete yourself.'));
        } catch (DeletingLastAdministratorException $ex) {
            $this->addErrorFlashTwig(
                t('Administrator <strong>{{ name }}</strong> is the only one and can\'t be deleted.'),
                [
                    'name' => $this->administratorFacade->getById($id)->getRealName(),
                ]
            );
        } catch (AdministratorNotFoundException $ex) {
            $this->addErrorFlash(t('Selected administrated doesn\'t exist.'));
        }

        return $this->redirectToRoute('admin_administrator_list');
    }
}
