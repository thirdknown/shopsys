<?php

namespace Shopsys\FrameworkBundle\Component\Grid;

use Shopsys\FrameworkBundle\Component\Grid\Exception\DuplicateColumnIdException;
use Shopsys\FrameworkBundle\Component\Grid\Exception\EmptyGridIdException;
use Shopsys\FrameworkBundle\Component\Grid\InlineEdit\GridInlineEditInterface;
use Shopsys\FrameworkBundle\Component\Router\Security\RouteCsrfProtector;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class Grid
{
    public const GET_PARAMETER = 'g';
    protected const DEFAULT_VIEW_THEME = '@ShopsysFramework/Admin/Grid/Grid.html.twig';
    protected const DEFAULT_LIMIT = 30;

    /**
     * @var string
     */
    protected $id;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Grid\Column[]
     */
    protected $columnsById = [];

    /**
     * @var \Shopsys\FrameworkBundle\Component\Grid\ActionColumn[]
     */
    protected $actionColumns = [];

    /**
     * @var bool
     */
    protected $enablePaging = false;

    /**
     * @var bool
     */
    protected $enableSelecting = false;

    /**
     * @var array
     */
    protected $allowedLimits = [30, 100, 200, 500];

    /**
     * @var int
     */
    protected $limit;

    /**
     * @var bool
     */
    protected $isLimitFromRequest = false;

    /**
     * @var int
     */
    protected $page = 1;

    /**
     * @var int|null
     */
    protected $totalCount;

    /**
     * @var int|null
     */
    protected $pageCount;

    /**
     * @var string|null
     */
    protected $orderSourceColumnName;

    /**
     * @var string|null
     */
    protected $orderDirection;

    /**
     * @var bool
     */
    protected $isOrderFromRequest = false;

    /**
     * @var array
     */
    protected $rows = [];

    /**
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    protected $requestStack;

    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    protected $router;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Router\Security\RouteCsrfProtector
     */
    protected $routeCsrfProtector;

    /**
     * @var \Twig\Environment
     */
    protected $twig;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Grid\DataSourceInterface
     */
    protected $dataSource;

    /**
     * @var string
     */
    protected $actionColumnClassAttribute = '';

    /**
     * @var \Shopsys\FrameworkBundle\Component\Grid\InlineEdit\GridInlineEditInterface|null
     */
    protected $inlineEditService;

    /**
     * @var string|null
     */
    protected $orderingEntityClass;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Paginator\PaginationResult
     */
    protected $paginationResults;

    /**
     * @var string|string[]|null
     */
    protected $viewTheme;

    /**
     * @var array
     */
    protected $viewTemplateParameters;

    /**
     * @var array
     */
    protected $selectedRowIds;

    /**
     * @var bool
     */
    protected $multipleDragAndDrop;

    /**
     * @param string $id
     * @param \Shopsys\FrameworkBundle\Component\Grid\DataSourceInterface $dataSource
     * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
     * @param \Symfony\Component\Routing\RouterInterface $router
     * @param \Shopsys\FrameworkBundle\Component\Router\Security\RouteCsrfProtector $routeCsrfProtector
     * @param \Twig\Environment $twig
     */
    public function __construct(
        $id,
        DataSourceInterface $dataSource,
        RequestStack $requestStack,
        RouterInterface $router,
        RouteCsrfProtector $routeCsrfProtector,
        Environment $twig
    ) {
        if ($id === '') {
            $message = 'Grid id cannot be empty.';

            throw new EmptyGridIdException($message);
        }

        $this->id = $id;
        $this->dataSource = $dataSource;
        $this->requestStack = $requestStack;
        $this->router = $router;
        $this->routeCsrfProtector = $routeCsrfProtector;
        $this->twig = $twig;

        $this->limit = static::DEFAULT_LIMIT;
        $this->page = 1;

        $this->viewTheme = static::DEFAULT_VIEW_THEME;
        $this->viewTemplateParameters = [];

        $this->selectedRowIds = [];
        $this->multipleDragAndDrop = false;

        $this->loadFromRequest();
    }

    /**
     * @param string $id
     * @param string $sourceColumnName
     * @param string $title
     * @param bool $sortable
     * @return \Shopsys\FrameworkBundle\Component\Grid\Column
     */
    public function addColumn($id, $sourceColumnName, $title, $sortable = false)
    {
        if (array_key_exists($id, $this->columnsById)) {
            throw new DuplicateColumnIdException(
                'Duplicate column id "' . $id . '" in grid "' . $this->id . '"'
            );
        }
        $column = new Column($id, $sourceColumnName, $title, $sortable);
        $this->columnsById[$id] = $column;

        return $column;
    }

    /**
     * @param string $type
     * @param string $name
     * @param string $route
     * @param array $bindingRouteParams
     * @param array $additionalRouteParams
     * @return \Shopsys\FrameworkBundle\Component\Grid\ActionColumn
     */
    public function addActionColumn(
        $type,
        $name,
        $route,
        array $bindingRouteParams = [],
        array $additionalRouteParams = []
    ) {
        $actionColumn = new ActionColumn(
            $this->router,
            $this->routeCsrfProtector,
            $type,
            $name,
            $route,
            $bindingRouteParams,
            $additionalRouteParams
        );
        $this->actionColumns[] = $actionColumn;

        return $actionColumn;
    }

    /**
     * @param string $route
     * @param array $bindingRouteParams
     * @param array $additionalRouteParams
     * @return \Shopsys\FrameworkBundle\Component\Grid\ActionColumn
     */
    public function addEditActionColumn($route, array $bindingRouteParams = [], array $additionalRouteParams = [])
    {
        return $this->addActionColumn(
            ActionColumn::TYPE_EDIT,
            t('Edit'),
            $route,
            $bindingRouteParams,
            $additionalRouteParams
        );
    }

    /**
     * @param string $route
     * @param array $bindingRouteParams
     * @param array $additionalRouteParams
     * @return \Shopsys\FrameworkBundle\Component\Grid\ActionColumn
     */
    public function addDeleteActionColumn($route, array $bindingRouteParams = [], array $additionalRouteParams = [])
    {
        return $this->addActionColumn(
            ActionColumn::TYPE_DELETE,
            t('Delete'),
            $route,
            $bindingRouteParams,
            $additionalRouteParams
        );
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Grid\InlineEdit\GridInlineEditInterface $inlineEditService
     */
    public function setInlineEditService(GridInlineEditInterface $inlineEditService)
    {
        $this->inlineEditService = $inlineEditService;
    }

    /**
     * @return bool
     */
    public function isInlineEdit()
    {
        return $this->inlineEditService !== null;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Grid\InlineEdit\GridInlineEditInterface|null
     */
    public function getInlineEditService()
    {
        return $this->inlineEditService;
    }

    /**
     * @param array $row
     * @return mixed
     */
    public function getRowId($row)
    {
        return self::getValueFromRowBySourceColumnName($row, $this->dataSource->getRowIdSourceColumnName());
    }

    /**
     * @param string $classAttribute
     */
    public function setActionColumnClassAttribute($classAttribute)
    {
        $this->actionColumnClassAttribute = $classAttribute;
    }

    /**
     * @param string|string[] $viewTheme
     * @param array $viewParameters
     */
    public function setTheme($viewTheme, array $viewParameters = [])
    {
        $this->viewTheme = $viewTheme;
        $this->viewTemplateParameters = $viewParameters;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Grid\GridView
     */
    public function createView()
    {
        $gridView = $this->createViewWithoutRows();

        if ($this->isEnabledPaging()) {
            $this->executeTotalQuery();
        }
        $this->loadRows();

        return $gridView;
    }

    /**
     * @param int $rowId
     * @return \Shopsys\FrameworkBundle\Component\Grid\GridView
     */
    public function createViewWithOneRow($rowId)
    {
        $gridView = $this->createViewWithoutRows();
        $this->loadRowsWithOneRow($rowId);

        return $gridView;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Grid\GridView
     */
    public function createViewWithoutRows()
    {
        $this->rows = [];

        return new GridView(
            $this,
            $this->requestStack,
            $this->router,
            $this->twig,
            $this->viewTheme,
            $this->viewTemplateParameters
        );
    }

    public function enablePaging()
    {
        $this->enablePaging = true;
    }

    public function enableSelecting()
    {
        $this->enableSelecting = true;
    }

    /**
     * @param int $limit
     */
    public function setDefaultLimit($limit)
    {
        if (!$this->isLimitFromRequest) {
            $this->setLimit((int)$limit);
        }
    }

    /**
     * @param string $columnId
     * @param string $direction
     */
    public function setDefaultOrder($columnId, $direction = DataSourceInterface::ORDER_ASC)
    {
        if (!$this->isOrderFromRequest) {
            $prefix = $direction === DataSourceInterface::ORDER_DESC ? '-' : '';
            $this->setOrderingByOrderString($prefix . $columnId);
        }
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Grid\Column[]
     */
    public function getColumnsById()
    {
        return $this->columnsById;
    }

    /**
     * @param string $columnId
     * @return bool
     */
    public function existsColumn($columnId)
    {
        return array_key_exists($columnId, $this->columnsById);
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Grid\ActionColumn[]
     */
    public function getActionColumns()
    {
        return $this->actionColumns;
    }

    /**
     * @return array
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * @return bool
     */
    public function isEnabledPaging()
    {
        return $this->enablePaging;
    }

    /**
     * @return bool
     */
    public function isEnabledSelecting()
    {
        return $this->enableSelecting;
    }

    /**
     * @param array $row
     * @return bool
     */
    public function isRowSelected(array $row)
    {
        $rowId = $this->getRowId($row);

        return in_array($rowId, $this->selectedRowIds, true);
    }

    /**
     * @return array
     */
    public function getSelectedRowIds()
    {
        return $this->selectedRowIds;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     */
    protected function setLimit($limit)
    {
        if (in_array($limit, $this->allowedLimits, true)) {
            $this->limit = $limit;
        }
    }

    /**
     * @return array
     */
    public function getAllowedLimits()
    {
        return $this->allowedLimits;
    }

    /**
     * @return int|null
     */
    public function getTotalCount()
    {
        return $this->totalCount;
    }

    /**
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @return int
     */
    public function getPageCount()
    {
        return $this->pageCount;
    }

    /**
     * @return string|null
     */
    public function getOrderSourceColumnName()
    {
        return $this->orderSourceColumnName;
    }

    /**
     * @return string|null
     */
    public function getOrderSourceColumnNameWithDirection()
    {
        $prefix = '';

        if ($this->getOrderDirection() === DataSourceInterface::ORDER_DESC) {
            $prefix = '-';
        }

        return $prefix . $this->getOrderSourceColumnName();
    }

    /**
     * @return string|null
     */
    public function getOrderDirection()
    {
        return $this->orderDirection;
    }

    /**
     * @return string
     */
    public function getActionColumnClassAttribute()
    {
        return $this->actionColumnClassAttribute;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Paginator\PaginationResult
     */
    public function getPaginationResults()
    {
        return $this->paginationResults;
    }

    /**
     * @param string $orderString
     */
    protected function setOrderingByOrderString($orderString)
    {
        if (substr($orderString, 0, 1) === '-') {
            $this->orderDirection = DataSourceInterface::ORDER_DESC;
        } else {
            $this->orderDirection = DataSourceInterface::ORDER_ASC;
        }
        $this->orderSourceColumnName = trim($orderString, '-');
    }

    protected function loadFromRequest()
    {
        $queryData = $this->requestStack->getMainRequest()->query->all(self::GET_PARAMETER);

        if (array_key_exists($this->id, $queryData)) {
            $gridQueryData = $queryData[$this->id];

            if (array_key_exists('limit', $gridQueryData)) {
                $this->setLimit((int)trim($gridQueryData['limit']));
                $this->isLimitFromRequest = true;
            }

            if (array_key_exists('page', $gridQueryData)) {
                $this->page = max((int)trim($gridQueryData['page']), 1);
            }

            if (array_key_exists('order', $gridQueryData)) {
                $this->setOrderingByOrderString(trim($gridQueryData['order']));
                $this->isOrderFromRequest = true;
            }
        }

        $requestData = $this->requestStack->getMainRequest()->request->all(self::GET_PARAMETER);

        if (!array_key_exists($this->id, $requestData)) {
            return;
        }

        $gridRequestData = $requestData[$this->id];

        if (array_key_exists('selectedRowIds', $gridRequestData) && is_array($gridRequestData['selectedRowIds'])) {
            $this->selectedRowIds = array_map('json_decode', $gridRequestData['selectedRowIds']);
        }
    }

    /**
     * @param array|string $removeParameters
     * @return array
     */
    public function getGridParameters($removeParameters = [])
    {
        $gridParameters = [];

        if ($this->isEnabledPaging()) {
            $gridParameters['limit'] = $this->getLimit();

            if ($this->getPage() > 1) {
                $gridParameters['page'] = $this->getPage();
            }
        }

        if ($this->getOrderSourceColumnName() !== null) {
            $gridParameters['order'] = $this->getOrderSourceColumnNameWithDirection();
        }

        foreach ((array)$removeParameters as $parameterToRemove) {
            if (array_key_exists($parameterToRemove, $gridParameters)) {
                unset($gridParameters[$parameterToRemove]);
            }
        }

        return $gridParameters;
    }

    /**
     * @param array|string|null $parameters
     * @param array|string|null $removeParameters
     * @return array
     */
    public function getUrlGridParameters($parameters = null, $removeParameters = null)
    {
        $gridParameters = array_replace_recursive(
            $this->getGridParameters($removeParameters),
            (array)$parameters
        );

        return [self::GET_PARAMETER => [$this->getId() => $gridParameters]];
    }

    /**
     * @param array|string|null $parameters
     * @param array|string|null $removeParameters
     * @return array
     */
    public function getUrlParameters($parameters = null, $removeParameters = null)
    {
        return array_replace_recursive(
            $this->requestStack->getMainRequest()->query->all(),
            $this->requestStack->getMainRequest()->attributes->get('_route_params'),
            $this->getUrlGridParameters($parameters, $removeParameters)
        );
    }

    protected function loadRows()
    {
        if (array_key_exists($this->orderSourceColumnName, $this->columnsById)
            && $this->columnsById[$this->orderSourceColumnName]->isSortable()
        ) {
            $orderSourceColumnName = $this->columnsById[$this->orderSourceColumnName]->getOrderSourceColumnName();
        } else {
            $orderSourceColumnName = null;
        }

        $orderDirection = $this->orderDirection;

        if ($this->isDragAndDrop()) {
            $orderSourceColumnName = null;
            $orderDirection = null;
        }

        $this->paginationResults = $this->dataSource->getPaginatedRows(
            $this->enablePaging ? $this->limit : null,
            $this->page,
            $orderSourceColumnName,
            $orderDirection
        );

        $this->rows = $this->paginationResults->getResults();
    }

    /**
     * @param int $rowId
     */
    protected function loadRowsWithOneRow($rowId)
    {
        $this->rows = [$this->dataSource->getOneRow($rowId)];
    }

    protected function executeTotalQuery()
    {
        $this->totalCount = $this->dataSource->getTotalRowsCount();
        $this->pageCount = max(ceil($this->totalCount / $this->limit), 1);
        $this->page = min($this->page, $this->pageCount);
    }

    /**
     * @param array $row
     * @param string $sourceColumnName
     * @return mixed
     */
    public static function getValueFromRowBySourceColumnName(array $row, $sourceColumnName)
    {
        $sourceColumnNameParts = explode('.', $sourceColumnName);

        if (count($sourceColumnNameParts) === 1) {
            return $row[$sourceColumnNameParts[0]];
        }

        if (count($sourceColumnNameParts) === 2) {
            if (array_key_exists($sourceColumnNameParts[0], $row)
                && array_key_exists($sourceColumnNameParts[1], $row[$sourceColumnNameParts[0]])
            ) {
                return $row[$sourceColumnNameParts[0]][$sourceColumnNameParts[1]];
            }

            if (array_key_exists($sourceColumnNameParts[1], $row)) {
                return $row[$sourceColumnNameParts[1]];
            }

            return $row[$sourceColumnName];
        }

        return $row[$sourceColumnName];
    }

    /**
     * @param string $entityClass
     */
    public function enableDragAndDrop($entityClass)
    {
        $this->orderingEntityClass = $entityClass;
    }

    public function enableMultipleDragAndDrop()
    {
        $this->multipleDragAndDrop = true;
    }

    /**
     * @return bool
     */
    public function isDragAndDrop()
    {
        return $this->orderingEntityClass !== null;
    }

    /**
     * @return string|null
     */
    public function getOrderingEntityClass()
    {
        return $this->orderingEntityClass;
    }

    /**
     * @return bool
     */
    public function isMultipleDragAndDrop()
    {
        return $this->multipleDragAndDrop;
    }

    /**
     * @param string[] $orderedColumnIds
     */
    public function reorderColumns(array $orderedColumnIds): void
    {
        $orderedColumns = [];

        foreach ($orderedColumnIds as $columnId) {
            $orderedColumns[$columnId] = $this->columnsById[$columnId];
        }

        $this->columnsById = [...$orderedColumns, ...$this->columnsById];
    }
}
