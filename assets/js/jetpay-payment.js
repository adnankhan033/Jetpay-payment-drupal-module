$ = jQuery;

drupalSettings.jetpay_order["cancelCallback"] = () => {
    console.log("Payment Cancelled");
    history.back();
};

function jetpaySuccessCallback() {
    // Options for the observer (which mutations to observe)
    const config = {
        attributes: true,
        childList: false,
        characterData: false,
        subtree: false,
    };

    // Create an observer instance linked to the callback function
    const observer = new MutationObserver(function (mutationsList, obs) {
        if (
            swal.getTitle().textContent === "Payment was successful !" &&
            swal.getConfirmButton().textContent === "OK"
        ) {
            observer.disconnect();
            swal.getConfirmButton().addEventListener("click", () => {
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            });
        }
    });

    // Start observing the target node for configured mutations
    observer.observe(document.body, config);
}

(function ($, Drupal, drupalSettings) {
    Drupal.behaviors.jetPay = {
        attach: function (context, settings) {
            $(document, context)
                .once("jetPay")
                .each(function (e) {
                    jetpay(settings.jetpay_order);
                });
            setTimeout(() => {
                jetpaySuccessCallback();
            }, 1500);
        },
    };
})(jQuery, Drupal, drupalSettings);
