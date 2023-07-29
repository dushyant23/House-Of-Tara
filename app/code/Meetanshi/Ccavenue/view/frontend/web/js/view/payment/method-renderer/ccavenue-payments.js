define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function ($, Component, setPaymentInformationAction, checkoutData, quote, fullScreenLoader) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Meetanshi_Ccavenue/payment/ccavenue-payments',
                redirectAfterPlaceOrder: false
            },

            /** Returns payment method instructions */
            getInstructions: function () {
                return window.checkoutConfig.payment.ccavenue_payment.payment_instruction;
            },

            getCcavenueLogoUrl: function () {
                return window.checkoutConfig.payment.ccavenue_payment.imageurl;
            },

            getCode: function () {
                return 'ccavenue';
            },

            afterPlaceOrder: function () {
                var self = this;
                this.isPlaceOrderActionAllowed(false);
                fullScreenLoader.startLoader();
                var html;

                $.when(sendRequest()).done(function(){
                    self.isPlaceOrderActionAllowed(true);
                    $("body").append(html);
                    $("#CcavenueForm").submit();
                }).fail(function(){
                    self.isPlaceOrderActionAllowed(true);
                });

                function sendRequest() {
                    return $.ajax({
                        type: 'POST',
                        url:  window.checkoutConfig.payment.ccavenue_payment.redirect_url,
                        dataType: "json",
                        success: function (response) {
                            if (response.success) {
                                html = response.html;
                                fullScreenLoader.stopLoader();
                            } else {
                                self.messageContainer.addErrorMessage({
                                    message: response.message || "Fail, please try again later."
                                });
                                fullScreenLoader.stopLoader();
                            }
                        },
                        error: function (response) {
                            fullScreenLoader.stopLoader();
                            self.messageContainer.addErrorMessage({
                                message: "Error, please try again later"
                            });
                        }
                    });
                }
            }

        });
    }
);
