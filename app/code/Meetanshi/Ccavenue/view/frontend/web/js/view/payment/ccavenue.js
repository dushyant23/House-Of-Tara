/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component,
              rendererList) {
        'use strict';

        rendererList.push(
            {
                type: 'ccavenue',
                component: 'Meetanshi_Ccavenue/js/view/payment/method-renderer/ccavenue-payments'
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
