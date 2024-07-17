import { ProductItemProps, ProductListItem } from './ProductListItem';
import { DEFAULT_PAGE_SIZE } from 'config/constants';
import { TypeListedProductFragment } from 'graphql/requests/products/fragments/ListedProductFragment.generated';
import { GtmMessageOriginType } from 'gtm/enums/GtmMessageOriginType';
import { GtmProductListNameType } from 'gtm/enums/GtmProductListNameType';
import React, { RefObject } from 'react';
import { SwipeableHandlers } from 'react-swipeable';
import { useComparison } from 'utils/productLists/comparison/useComparison';
import { useWishlist } from 'utils/productLists/wishlist/useWishlist';
import { useCurrentPageQuery } from 'utils/queryParams/useCurrentPageQuery';

type ProductsListProps = {
    products: TypeListedProductFragment[];
    gtmProductListName: GtmProductListNameType;
    gtmMessageOrigin: GtmMessageOriginType;
    ref?: RefObject<HTMLUListElement>;
    productRefs?: RefObject<HTMLLIElement>[];
    swipeHandlers?: SwipeableHandlers;
    className?: string;
    isWithSimpleCards?: boolean;
    productItemProps?: Partial<ProductItemProps>;
};

export const ProductsListContent: FC<ProductsListProps> = ({
    products,
    gtmProductListName,
    gtmMessageOrigin = GtmMessageOriginType.other,
    productRefs,
    ref,
    children,
    swipeHandlers,
    productItemProps,
    className,
}) => {
    const currentPage = useCurrentPageQuery();
    const { toggleProductInComparison, isProductInComparison } = useComparison();
    const { toggleProductInWishlist, isProductInWishlist } = useWishlist();

    return (
        <ul className={className} ref={ref} {...swipeHandlers}>
            {products.map((product, index) => (
                <ProductListItem
                    key={product.uuid}
                    gtmMessageOrigin={gtmMessageOrigin}
                    gtmProductListName={gtmProductListName}
                    isProductInComparison={isProductInComparison(product.uuid)}
                    isProductInWishlist={isProductInWishlist(product.uuid)}
                    listIndex={(currentPage - 1) * DEFAULT_PAGE_SIZE + index}
                    product={product}
                    ref={productRefs?.[index]}
                    toggleProductInComparison={() => toggleProductInComparison(product.uuid)}
                    toggleProductInWishlist={() => toggleProductInWishlist(product.uuid)}
                    {...productItemProps}
                />
            ))}
            {children}
        </ul>
    );
};
