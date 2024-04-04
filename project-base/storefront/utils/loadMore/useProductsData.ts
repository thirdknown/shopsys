import { calculatePageSize } from './calculatePageSize';
import { getPageSizeInfo } from './getPageSizeInfo';
import { getPreviousProductsFromCache } from './getPreviousProductsFromCache';
import { hasReadAllProductsFromCache } from './hasReadAllProductsFromCache';
import { mergeProductEdges } from './mergeProductEdges';
import { readProductsFromCache } from './readProductsFromCache';
import { getEndCursor } from 'components/Blocks/Product/Filter/utils/getEndCursor';
import { DEFAULT_PAGE_SIZE } from 'config/constants';
import { DocumentNode } from 'graphql';
import { ListedProductConnectionFragment } from 'graphql/requests/products/fragments/ListedProductConnectionFragment.generated';
import {
    BrandProductsQueryVariables,
    BrandProductsQuery,
} from 'graphql/requests/products/queries/BrandProductsQuery.generated';
import {
    CategoryProductsQueryVariables,
    CategoryProductsQuery,
} from 'graphql/requests/products/queries/CategoryProductsQuery.generated';
import {
    FlagProductsQueryVariables,
    FlagProductsQuery,
} from 'graphql/requests/products/queries/FlagProductsQuery.generated';
import { useRouter } from 'next/router';
import { useRef, useState, useEffect } from 'react';
import { useClient } from 'urql';
import { mapParametersFilter } from 'utils/filterOptions/mapParametersFilter';
import { getSlugFromUrl } from 'utils/parsing/getSlugFromUrl';
import { useCurrentFilterQuery } from 'utils/queryParams/useCurrentFilterQuery';
import { useCurrentLoadMoreQuery } from 'utils/queryParams/useCurrentLoadMoreQuery';
import { useCurrentPageQuery } from 'utils/queryParams/useCurrentPageQuery';
import { useCurrentSortQuery } from 'utils/queryParams/useCurrentSortQuery';

export const useProductsData = (
    queryDocument: DocumentNode,
    totalProductCount: number,
    additionalParams?: {
        shouldAbortFetchingProducts: boolean;
        abortedFetchCallback: () => void;
    },
): [ListedProductConnectionFragment['edges'] | undefined, boolean, boolean, boolean] => {
    const client = useClient();
    const { asPath } = useRouter();
    const currentPage = useCurrentPageQuery();
    const currentFilter = useCurrentFilterQuery();
    const currentSort = useCurrentSortQuery();
    const currentLoadMore = useCurrentLoadMoreQuery();
    const urlSlug = getSlugFromUrl(asPath);
    const mappedFilter = mapParametersFilter(currentFilter);

    const previousLoadMoreRef = useRef(currentLoadMore);
    const previousPageRef = useRef(currentPage);
    const initialPageSizeRef = useRef(calculatePageSize(currentLoadMore));

    const [productsData, setProductsData] = useState(
        readProductsFromCache(
            queryDocument,
            client,
            urlSlug,
            currentSort,
            mappedFilter,
            getEndCursor(currentPage),
            initialPageSizeRef.current,
        ),
    );

    const [fetching, setFetching] = useState(!productsData.products);
    const [loadMoreFetching, setLoadMoreFetching] = useState(false);

    const fetchProducts = async (
        variables: CategoryProductsQueryVariables | FlagProductsQueryVariables | BrandProductsQueryVariables,
        previouslyQueriedProductsFromCache: ListedProductConnectionFragment['edges'] | undefined,
    ) => {
        const response = await client
            .query<
                CategoryProductsQuery | BrandProductsQuery | FlagProductsQuery,
                typeof variables
            >(queryDocument, variables)
            .toPromise();

        if (!response.data) {
            setProductsData({ products: undefined, hasNextPage: false });

            return;
        }

        setProductsData({
            products: mergeProductEdges(previouslyQueriedProductsFromCache, response.data.products.edges),
            hasNextPage: response.data.products.pageInfo.hasNextPage,
        });
        stopFetching();
    };

    const startFetching = () => {
        if (previousLoadMoreRef.current === currentLoadMore || currentLoadMore === 0) {
            setFetching(true);
        } else {
            setLoadMoreFetching(true);
            previousLoadMoreRef.current = currentLoadMore;
        }
    };

    const stopFetching = () => {
        setFetching(false);
        setLoadMoreFetching(false);
    };

    useEffect(() => {
        if (additionalParams?.shouldAbortFetchingProducts) {
            additionalParams.abortedFetchCallback();

            return;
        }

        if (previousPageRef.current !== currentPage) {
            previousPageRef.current = currentPage;
            initialPageSizeRef.current = DEFAULT_PAGE_SIZE;
        }

        const previousProductsFromCache = getPreviousProductsFromCache(
            queryDocument,
            client,
            urlSlug,
            currentSort,
            mappedFilter,
            DEFAULT_PAGE_SIZE,
            initialPageSizeRef.current,
            currentPage,
            currentLoadMore,
            readProductsFromCache,
        );

        if (
            hasReadAllProductsFromCache(
                previousProductsFromCache?.length,
                currentLoadMore,
                currentPage,
                totalProductCount,
            )
        ) {
            return;
        }

        const { pageSize, isMoreThanOnePage } = getPageSizeInfo(!!previousProductsFromCache, currentLoadMore);
        const endCursor = getEndCursor(currentPage, isMoreThanOnePage ? undefined : currentLoadMore);

        startFetching();
        fetchProducts(
            {
                endCursor,
                filter: mappedFilter,
                orderingMode: currentSort,
                urlSlug,
                pageSize,
            },
            previousProductsFromCache,
        );
    }, [urlSlug, currentSort, JSON.stringify(currentFilter), currentPage, currentLoadMore]);

    return [productsData.products, productsData.hasNextPage, fetching, loadMoreFetching];
};
