export enum GtmEventType {
    page_view = 'page_view',
    consent_update = 'consent.update',
    add_to_cart = 'ec.add_to_cart',
    remove_from_cart = 'ec.remove_from_cart',
    cart_view = 'ec.cart_view',
    product_list_view = 'ec.product_list_view',
    product_click = 'ec.product_click',
    product_detail_view = 'ec.product_detail_view',
    payment_and_transport_page_view = 'ec.payment_and_transport_view',
    autocomplete_results_view = 'ec.autocomplete_results_view',
    autocomplete_result_click = 'ec.autocomplete_result_click',
    transport_change = 'ec.transport_change',
    contact_information_page_view = 'ec.contact_information_view',
    payment_change = 'ec.payment_change',
    payment_fail = 'ec.payment_fail',
    create_order = 'ec.create_order',
    show_message = 'ec.show_message',
    send_form = 'send_form',
}
