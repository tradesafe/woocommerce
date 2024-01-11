(() => {
    "use strict";
    const r = window.React

    const settings = window.wc.wcSettings.getSetting('tradesafe_data', {});
    const label = window.wp.htmlEntities.decodeEntities(settings.title) || window.wp.i18n.__('TradeSafe', 'tradesafe_payment_gateway');
    const Content = () => {
        return window.wp.htmlEntities.decodeEntities(settings.description || '');
    };

    const logo = r.createElement("div", {
            style: {
                display: "flex",
                flexDirection: "row",
                "justify-content": "space-between",
                gap: "0.5rem",
                width: "100%"
            }
        },
        r.createElement("div", {
                style: {
                    display: "flex",
                    gap: "0.5rem"
                }
            },
            r.createElement("img", {src: settings?.logo_urls[0], alt: settings?.title}, null),
            label
        ),
        r.createElement("img", {src: settings?.logo_urls[1], alt: "Payment Types", style: {"padding-right": "1rem"}}),
    )

    const Block_Gateway = {
        name: 'tradesafe',
        label: logo,
        content: Object(window.wp.element.createElement)(Content, null),
        edit: Object(window.wp.element.createElement)(Content, null),
        canMakePayment: () => true,
        ariaLabel: label,
        supports: {
            features: settings.supports,
        },
    };

    window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway);
})();
