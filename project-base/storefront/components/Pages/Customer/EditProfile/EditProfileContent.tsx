import { BillingAddress } from './BillingAddress';
import { CompanyCustomer } from './CompanyCustomer';
import { DeliveryAddress } from './DeliveryAddress';
import { PersonalData } from './PersonalData';
import { SubmitButton } from 'components/Forms/Button/SubmitButton';
import { Form, FormButtonWrapper, FormContentWrapper } from 'components/Forms/Form/Form';
import {
    useCustomerChangeProfileForm,
    useCustomerChangeProfileFormMeta,
} from 'components/Pages/Customer/EditProfile/customerChangeProfileFormMeta';
import { useChangePersonalDataMutation } from 'graphql/requests/customer/mutations/ChangePersonalDataMutation.generated';
import { GtmMessageOriginType } from 'gtm/enums/GtmMessageOriginType';
import useTranslation from 'next-translate/useTranslation';
import { FormProvider, SubmitHandler } from 'react-hook-form';
import { CurrentCustomerType } from 'types/customer';
import { CustomerChangeProfileFormType } from 'types/form';
import { handleFormErrors } from 'utils/forms/handleFormErrors';
import { useErrorPopup } from 'utils/forms/useErrorPopup';
import { showSuccessMessage } from 'utils/toasts/showSuccessMessage';

type EditProfileContentProps = {
    currentCustomerUser: CurrentCustomerType;
};

export const EditProfileContent: FC<EditProfileContentProps> = ({ currentCustomerUser }) => {
    const { t } = useTranslation();
    const [, customerEditProfile] = useChangePersonalDataMutation();

    const [formProviderMethods] = useCustomerChangeProfileForm({
        ...currentCustomerUser,
        country: {
            label: currentCustomerUser.country.name,
            value: currentCustomerUser.country.code,
        },
    });
    const formMeta = useCustomerChangeProfileFormMeta(formProviderMethods);
    const isSubmitting = formProviderMethods.formState.isSubmitting;

    const onSubmitCustomerChangeProfileFormHandler: SubmitHandler<CustomerChangeProfileFormType> = async (
        customerChangeProfileFormData,
        event,
    ) => {
        event?.preventDefault();

        onChangeProfileHandler(customerChangeProfileFormData);
    };

    const onChangeProfileHandler = async (customerChangeProfileFormData: CustomerChangeProfileFormType) => {
        const changeProfileResult = await customerEditProfile({
            input: {
                billingAddressUuid: currentCustomerUser.billingAddressUuid,
                firstName: customerChangeProfileFormData.firstName,
                lastName: customerChangeProfileFormData.lastName,
                telephone: customerChangeProfileFormData.telephone,
                street: customerChangeProfileFormData.street,
                city: customerChangeProfileFormData.city,
                country: customerChangeProfileFormData.country.value,
                postcode: customerChangeProfileFormData.postcode,
                companyCustomer: customerChangeProfileFormData.companyCustomer,
                companyName: customerChangeProfileFormData.companyName,
                companyNumber: customerChangeProfileFormData.companyNumber,
                companyTaxNumber: customerChangeProfileFormData.companyTaxNumber,
                newsletterSubscription: customerChangeProfileFormData.newsletterSubscription,
            },
        });

        if (changeProfileResult.data?.ChangePersonalData !== undefined) {
            showSuccessMessage(formMeta.messages.success);
        }

        handleFormErrors(changeProfileResult.error, formProviderMethods, t, formMeta.messages.error);
    };

    useErrorPopup(formProviderMethods, formMeta.fields, undefined, GtmMessageOriginType.other);

    return (
        <FormProvider {...formProviderMethods}>
            <Form onSubmit={formProviderMethods.handleSubmit(onSubmitCustomerChangeProfileFormHandler)}>
                <FormContentWrapper>
                    <PersonalData />

                    {currentCustomerUser.companyCustomer && <CompanyCustomer />}

                    <BillingAddress />

                    <DeliveryAddress
                        defaultDeliveryAddress={currentCustomerUser.defaultDeliveryAddress}
                        deliveryAddresses={currentCustomerUser.deliveryAddresses}
                    />

                    <FormButtonWrapper className="mt-0 pb-6">
                        <SubmitButton isDisabled={isSubmitting}>{t('Save profile')}</SubmitButton>
                    </FormButtonWrapper>
                </FormContentWrapper>
            </Form>
        </FormProvider>
    );
};