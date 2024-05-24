(() => {
    "use strict";
    const r = window.React, br = window.wc.wcBlocksRegistry, s = window.wc.wcSettings, e = window.wp.htmlEntities, i = window.wp.i18n;

    const settings = (0, s.getSetting)('tradesafe_data', {});
    const label = (0, e.decodeEntities)(settings.title) || (0, i.__)('TradeSafe', 'tradesafe_payment_gateway');
    const Content = () => (0, e.decodeEntities)(settings.description || "");

    const logo = (0, r.createElement)("div", {
            style: {
                display: "flex",
                flexDirection: "row",
                "justify-content": "space-between",
                gap: "0.5rem",
                width: "100%"
            }
        },
        (0, r.createElement)("div", {
                style: {
                    display: "flex",
                    gap: "0.5rem"
                }
            },
            (0, r.createElement)("img", {src: settings?.logo_urls[0], alt: settings?.title}, null),
            label
        ),
        (0, r.createElement)("img", {src: settings?.logo_urls[1], alt: "Payment Types", style: {"padding-right": "1rem"}}),
    );

    (0, br.registerPaymentMethod)({
        name: 'tradesafe',
        label: logo,
        content: (0, r.createElement)(Content, null),
        edit: (0, r.createElement)(Content, null),
        canMakePayment: () => true,
        ariaLabel: label,
        supports: {
            features: settings.supports,
        },
    });
})();
