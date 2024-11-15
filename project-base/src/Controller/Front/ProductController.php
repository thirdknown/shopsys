<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Form\Front\Product\ProductFilterFormType;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\String\TransformString;
use Shopsys\FrameworkBundle\Model\Category\Category;
use Shopsys\FrameworkBundle\Model\Category\CategoryFacade;
use Shopsys\FrameworkBundle\Model\Module\ModuleFacade;
use Shopsys\FrameworkBundle\Model\Module\ModuleList;
use Shopsys\FrameworkBundle\Model\Product\Brand\BrandFacade;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfigFactory;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterData;
use Shopsys\FrameworkBundle\Model\Product\Listing\ProductListOrderingModeForBrandFacade;
use Shopsys\FrameworkBundle\Model\Product\Listing\ProductListOrderingModeForListFacade;
use Shopsys\FrameworkBundle\Model\Product\Listing\ProductListOrderingModeForSearchFacade;
use Shopsys\FrameworkBundle\Model\Product\ProductOnCurrentDomainFacadeInterface;
use Shopsys\FrameworkBundle\Twig\RequestExtension;
use Shopsys\ReadModelBundle\Product\Detail\ProductDetailViewFacadeInterface;
use Shopsys\ReadModelBundle\Product\Listed\ListedProductVariantsViewFacadeInterface;
use Shopsys\ReadModelBundle\Product\Listed\ListedProductViewFacadeInterface;
use Symfony\Component\HttpFoundation\Request;

class ProductController extends FrontBaseController
{
    public const SEARCH_TEXT_PARAMETER = 'q';
    private const SEARCH_TEXT_DEFAULT_VALUE = '';
    public const PAGE_QUERY_PARAMETER = 'page';
    public const PRODUCTS_PER_PAGE = 12;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfigFactory
     */
    private $productFilterConfigFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Category\CategoryFacade
     */
    private $categoryFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\ProductOnCurrentDomainElasticFacade
     */
    private $productOnCurrentDomainFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Twig\RequestExtension
     */
    private $requestExtension;

    /**
     * @var \App\Model\Product\Listing\ProductListOrderingModeForListFacade
     */
    private $productListOrderingModeForListFacade;

    /**
     * @var \App\Model\Product\Listing\ProductListOrderingModeForBrandFacade
     */
    private $productListOrderingModeForBrandFacade;

    /**
     * @var \App\Model\Product\Listing\ProductListOrderingModeForSearchFacade
     */
    private $productListOrderingModeForSearchFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Module\ModuleFacade
     */
    private $moduleFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Brand\BrandFacade
     */
    private $brandFacade;

    /**
     * @var \Shopsys\ReadModelBundle\Product\Listed\ListedProductViewElasticFacade
     */
    private $listedProductViewFacade;

    /**
     * @var \Shopsys\ReadModelBundle\Product\Listed\ListedProductVariantsViewFacadeInterface
     */
    protected $listedProductVariantsViewFacade;

    /**
     * @var \Shopsys\ReadModelBundle\Product\Detail\ProductDetailViewFacadeInterface
     */
    protected $productDetailViewFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Twig\RequestExtension $requestExtension
     * @param \Shopsys\FrameworkBundle\Model\Category\CategoryFacade $categoryFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductOnCurrentDomainElasticFacade $productOnCurrentDomainFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfigFactory $productFilterConfigFactory
     * @param \App\Model\Product\Listing\ProductListOrderingModeForListFacade $productListOrderingModeForListFacade
     * @param \App\Model\Product\Listing\ProductListOrderingModeForBrandFacade $productListOrderingModeForBrandFacade
     * @param \App\Model\Product\Listing\ProductListOrderingModeForSearchFacade $productListOrderingModeForSearchFacade
     * @param \Shopsys\FrameworkBundle\Model\Module\ModuleFacade $moduleFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Brand\BrandFacade $brandFacade
     * @param \Shopsys\ReadModelBundle\Product\Listed\ListedProductViewElasticFacade $listedProductViewFacade
     * @param \Shopsys\ReadModelBundle\Product\Listed\ListedProductVariantsViewFacadeInterface $listedProductVariantsViewFacade
     * @param \Shopsys\ReadModelBundle\Product\Detail\ProductDetailViewFacadeInterface $productDetailViewFacade
     */
    public function __construct(
        RequestExtension $requestExtension,
        CategoryFacade $categoryFacade,
        Domain $domain,
        ProductOnCurrentDomainFacadeInterface $productOnCurrentDomainFacade,
        ProductFilterConfigFactory $productFilterConfigFactory,
        ProductListOrderingModeForListFacade $productListOrderingModeForListFacade,
        ProductListOrderingModeForBrandFacade $productListOrderingModeForBrandFacade,
        ProductListOrderingModeForSearchFacade $productListOrderingModeForSearchFacade,
        ModuleFacade $moduleFacade,
        BrandFacade $brandFacade,
        ListedProductViewFacadeInterface $listedProductViewFacade,
        ListedProductVariantsViewFacadeInterface $listedProductVariantsViewFacade,
        ProductDetailViewFacadeInterface $productDetailViewFacade
    ) {
        $this->requestExtension = $requestExtension;
        $this->categoryFacade = $categoryFacade;
        $this->domain = $domain;
        $this->productOnCurrentDomainFacade = $productOnCurrentDomainFacade;
        $this->productFilterConfigFactory = $productFilterConfigFactory;
        $this->productListOrderingModeForListFacade = $productListOrderingModeForListFacade;
        $this->productListOrderingModeForBrandFacade = $productListOrderingModeForBrandFacade;
        $this->productListOrderingModeForSearchFacade = $productListOrderingModeForSearchFacade;
        $this->moduleFacade = $moduleFacade;
        $this->brandFacade = $brandFacade;
        $this->listedProductViewFacade = $listedProductViewFacade;
        $this->listedProductVariantsViewFacade = $listedProductVariantsViewFacade;
        $this->productDetailViewFacade = $productDetailViewFacade;
    }

    /**
     * @param int $id
     */
    public function detailAction($id)
    {
        $productDetailView = $this->productDetailViewFacade->getVisibleProductDetail($id);

        if ($productDetailView->getMainVariantId() !== null) {
            return $this->redirectToRoute('front_product_detail', ['id' => $productDetailView->getMainVariantId()]);
        }

        return $this->render('Front/Content/Product/detail.html.twig', [
            'productView' => $productDetailView,
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $id
     */
    public function listByCategoryAction(Request $request, $id)
    {
        /** @var \App\Model\Category\Category $category */
        $category = $this->categoryFacade->getVisibleOnDomainById($this->domain->getId(), $id);

        $requestPage = $request->get(self::PAGE_QUERY_PARAMETER);

        if (!$this->isRequestPageValid($requestPage)) {
            return $this->redirectToRoute('front_product_list', $this->getRequestParametersWithoutPage());
        }
        $page = $requestPage === null ? 1 : (int)$requestPage;

        $orderingModeId = $this->productListOrderingModeForListFacade->getOrderingModeIdFromRequest(
            $request
        );

        $productFilterData = new ProductFilterData();

        $productFilterConfig = $this->createProductFilterConfigForCategory($category);
        $filterForm = $this->createForm(ProductFilterFormType::class, $productFilterData, [
            'product_filter_config' => $productFilterConfig,
        ]);
        $filterForm->handleRequest($request);

        $paginationResult = $this->listedProductViewFacade->getFilteredPaginatedInCategory(
            $id,
            $productFilterData,
            $orderingModeId,
            $page,
            self::PRODUCTS_PER_PAGE
        );

        $productFilterCountData = null;

        if ($this->moduleFacade->isEnabled(ModuleList::PRODUCT_FILTER_COUNTS)) {
            $productFilterCountData = $this->productOnCurrentDomainFacade->getProductFilterCountDataInCategory(
                $id,
                $productFilterConfig,
                $productFilterData
            );
        }

        $viewParameters = [
            'paginationResult' => $paginationResult,
            'productFilterCountData' => $productFilterCountData,
            'category' => $category,
            'filterForm' => $filterForm->createView(),
            'filterFormSubmitted' => $filterForm->isSubmitted(),
            'visibleChildren' => $this->categoryFacade->getAllVisibleChildrenByCategoryAndDomainId(
                $category,
                $this->domain->getId()
            ),
            'priceRange' => $productFilterConfig->getPriceRange(),
        ];

        if ($request->isXmlHttpRequest()) {
            return $this->render('Front/Content/Product/ajaxList.html.twig', $viewParameters);
        }

        return $this->render('Front/Content/Product/list.html.twig', $viewParameters);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $id
     */
    public function listByBrandAction(Request $request, $id)
    {
        $requestPage = $request->get(self::PAGE_QUERY_PARAMETER);

        if (!$this->isRequestPageValid($requestPage)) {
            return $this->redirectToRoute('front_brand_detail', $this->getRequestParametersWithoutPage());
        }
        $page = $requestPage === null ? 1 : (int)$requestPage;

        $orderingModeId = $this->productListOrderingModeForBrandFacade->getOrderingModeIdFromRequest(
            $request
        );

        $paginationResult = $this->listedProductViewFacade->getPaginatedForBrand(
            $id,
            $orderingModeId,
            $page,
            self::PRODUCTS_PER_PAGE
        );

        $brand = $this->brandFacade->getById($id);

        $viewParameters = [
            'paginationResult' => $paginationResult,
            'brand' => $brand,
        ];

        if ($request->isXmlHttpRequest()) {
            return $this->render('Front/Content/Product/ajaxListByBrand.html.twig', $viewParameters);
        }

        return $this->render('Front/Content/Product/listByBrand.html.twig', $viewParameters);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function searchAction(Request $request)
    {
        $searchText = TransformString::replaceInvalidUtf8CharactersByQuestionMark(
            trim((string)$request->query->get(self::SEARCH_TEXT_PARAMETER, self::SEARCH_TEXT_DEFAULT_VALUE))
        );

        $requestPage = $request->get(self::PAGE_QUERY_PARAMETER);

        if (!$this->isRequestPageValid($requestPage)) {
            return $this->redirectToRoute('front_product_search', $this->getRequestParametersWithoutPage());
        }
        $page = $requestPage === null ? 1 : (int)$requestPage;

        $orderingModeId = $this->productListOrderingModeForSearchFacade->getOrderingModeIdFromRequest(
            $request
        );

        $productFilterData = new ProductFilterData();

        $productFilterConfig = $this->createProductFilterConfigForSearch($searchText);
        $filterForm = $this->createForm(ProductFilterFormType::class, $productFilterData, [
            'product_filter_config' => $productFilterConfig,
        ]);
        $filterForm->handleRequest($request);

        $paginationResult = $this->listedProductViewFacade->getFilteredPaginatedForSearch(
            $searchText,
            $productFilterData,
            $orderingModeId,
            $page,
            self::PRODUCTS_PER_PAGE
        );

        $productFilterCountData = null;

        if ($this->moduleFacade->isEnabled(ModuleList::PRODUCT_FILTER_COUNTS)) {
            $productFilterCountData = $this->productOnCurrentDomainFacade->getProductFilterCountDataForSearch(
                $searchText,
                $productFilterConfig,
                $productFilterData
            );
        }

        $viewParameters = [
            'paginationResult' => $paginationResult,
            'productFilterCountData' => $productFilterCountData,
            'filterForm' => $filterForm->createView(),
            'filterFormSubmitted' => $filterForm->isSubmitted(),
            'searchText' => $searchText,
            'SEARCH_TEXT_PARAMETER' => self::SEARCH_TEXT_PARAMETER,
            'priceRange' => $productFilterConfig->getPriceRange(),
        ];

        if ($request->isXmlHttpRequest()) {
            return $this->render('Front/Content/Product/ajaxSearch.html.twig', $viewParameters);
        }
        $viewParameters['foundCategories'] = $this->searchCategories($searchText);

        return $this->render('Front/Content/Product/search.html.twig', $viewParameters);
    }

    /**
     * @param \App\Model\Category\Category $category
     * @return \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfig
     */
    private function createProductFilterConfigForCategory(Category $category)
    {
        return $this->productFilterConfigFactory->createForCategory(
            $this->domain->getId(),
            $this->domain->getLocale(),
            $category
        );
    }

    /**
     * @param string|null $searchText
     * @return \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfig
     */
    private function createProductFilterConfigForSearch($searchText)
    {
        return $this->productFilterConfigFactory->createForSearch(
            $this->domain->getId(),
            $this->domain->getLocale(),
            $searchText
        );
    }

    /**
     * @param string|null $searchText
     * @return \App\Model\Category\Category[]
     */
    private function searchCategories($searchText)
    {
        /** @var \App\Model\Category\Category[] $categories */
        $categories = $this->categoryFacade->getVisibleByDomainAndSearchText(
            $this->domain->getId(),
            $this->domain->getLocale(),
            $searchText
        );

        return $categories;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function selectOrderingModeForListAction(Request $request)
    {
        $productListOrderingConfig = $this->productListOrderingModeForListFacade->getProductListOrderingConfig();

        $orderingModeId = $this->productListOrderingModeForListFacade->getOrderingModeIdFromRequest(
            $request
        );

        return $this->render('Front/Content/Product/orderingSetting.html.twig', [
            'orderingModesNames' => $productListOrderingConfig->getSupportedOrderingModesNamesIndexedById(),
            'activeOrderingModeId' => $orderingModeId,
            'cookieName' => $productListOrderingConfig->getCookieName(),
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function selectOrderingModeForListByBrandAction(Request $request)
    {
        $productListOrderingConfig = $this->productListOrderingModeForBrandFacade->getProductListOrderingConfig();

        $orderingModeId = $this->productListOrderingModeForBrandFacade->getOrderingModeIdFromRequest(
            $request
        );

        return $this->render('Front/Content/Product/orderingSetting.html.twig', [
            'orderingModesNames' => $productListOrderingConfig->getSupportedOrderingModesNamesIndexedById(),
            'activeOrderingModeId' => $orderingModeId,
            'cookieName' => $productListOrderingConfig->getCookieName(),
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function selectOrderingModeForSearchAction(Request $request)
    {
        $productListOrderingConfig = $this->productListOrderingModeForSearchFacade->getProductListOrderingConfig();

        $orderingModeId = $this->productListOrderingModeForSearchFacade->getOrderingModeIdFromRequest(
            $request
        );

        return $this->render('Front/Content/Product/orderingSetting.html.twig', [
            'orderingModesNames' => $productListOrderingConfig->getSupportedOrderingModesNamesIndexedById(),
            'activeOrderingModeId' => $orderingModeId,
            'cookieName' => $productListOrderingConfig->getCookieName(),
        ]);
    }

    /**
     * @param string|null $page
     * @return bool
     */
    private function isRequestPageValid($page)
    {
        return $page === null || (preg_match('@^([2-9]|[1-9][0-9]+)$@', $page));
    }

    /**
     * @return array
     */
    private function getRequestParametersWithoutPage()
    {
        $parameters = $this->requestExtension->getAllRequestParams();
        unset($parameters[self::PAGE_QUERY_PARAMETER]);

        return $parameters;
    }
}
