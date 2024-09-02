import { ExtendedNextLink } from 'components/Basic/ExtendedNextLink/ExtendedNextLink';
import { Image } from 'components/Basic/Image/Image';
import { LinkButton } from 'components/Forms/Button/LinkButton';
import { useDomainConfig } from 'components/providers/DomainConfigProvider';
import { TypeComplaintDetailFragment } from 'graphql/requests/complaints/fragments/ComplaintDetailFragment.generated';
import useTranslation from 'next-translate/useTranslation';
import { ReactNode } from 'react';
import { useFormatDate } from 'utils/formatting/useFormatDate';
import { getInternationalizedStaticUrls } from 'utils/staticUrls/getInternationalizedStaticUrls';
import { twMergeCustom } from 'utils/twMerge';

type ComplaintItemProps = {
    complaintItem: TypeComplaintDetailFragment;
};

export const ComplaintItem: FC<ComplaintItemProps> = ({ complaintItem }) => {
    const { t } = useTranslation();
    const { formatDate } = useFormatDate();
    const { url } = useDomainConfig();
    const [customerComplaintDetailUrl] = getInternationalizedStaticUrls(['/customer/complaint-detail'], url);

    return (
        <div className="bg-backgroundMore flex flex-col gap-5 rounded-md p-4 vl:p-6">
            <div className="flex flex-col vl:flex-row vl:justify-between vl:items-start gap-4">
                <Image
                    priority
                    alt={complaintItem.items[0].orderItem?.productMainImage?.name || ''}
                    className="max-h-full object-contain h-[80px] w-[80px]"
                    height={80}
                    sizes="(max-width: 768px) 100vw, 50vw"
                    src={complaintItem.items[0].orderItem?.productMainImage?.url}
                    width={80}
                />
                <div className="flex flex-col gap-1">
                    <h5>
                        {complaintItem.items[0].orderItem?.product?.isVisible ? (
                            <ExtendedNextLink href={complaintItem.items[0].orderItem.product.slug} type="product">
                                {complaintItem.items[0].orderItem.name}
                            </ExtendedNextLink>
                        ) : (
                            complaintItem.items[0].orderItem?.name
                        )}
                    </h5>
                    <div className="flex gap-x-8 gap-y-2 flex-wrap">
                        <ComplaintItemColumnInfo
                            title={t('Complaint number')}
                            value={
                                <ExtendedNextLink
                                    type="complaintDetail"
                                    href={{
                                        pathname: customerComplaintDetailUrl,
                                        query: { complaintNumber: complaintItem.number },
                                    }}
                                >
                                    {complaintItem.number}
                                </ExtendedNextLink>
                            }
                        />
                        <ComplaintItemColumnInfo
                            title={t('Creation date')}
                            value={formatDate(complaintItem.createdAt, 'DD. MM. YYYY')}
                        />
                        <ComplaintItemColumnInfo
                            title={t('Status')}
                            value={complaintItem.status}
                            wrapperClassName="min-w-[80px]"
                        />
                    </div>
                </div>
                <div className="flex gap-2 items-center md:ml-auto">
                    <LinkButton
                        className="w-full md:w-auto whitespace-nowrap"
                        size="small"
                        type="complaintDetail"
                        href={{
                            pathname: customerComplaintDetailUrl,
                            query: { complaintNumber: complaintItem.number },
                        }}
                    >
                        {t('Complaint detail')}
                    </LinkButton>
                </div>
            </div>
        </div>
    );
};

type ComplaintItemColumnInfoProps = {
    title: string;
    value: ReactNode;
    valueClassName?: string;
    wrapperClassName?: string;
};

const ComplaintItemColumnInfo: FC<ComplaintItemColumnInfoProps> = ({
    title,
    value,
    valueClassName,
    wrapperClassName,
}) => {
    return (
        <div className={twMergeCustom('flex gap-4 items-end', wrapperClassName)}>
            <div className="flex flex-col gap-1">
                <span className="text-sm">{title}</span>
                <span className={twMergeCustom('font-bold leading-none', valueClassName)}>{value}</span>
            </div>
        </div>
    );
};
