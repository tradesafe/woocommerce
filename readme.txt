=== TradeSafe Payment Gateway for WooCommerce ===
Contributors: richardnbanks, tradesafeops
Tags: woocommerce, dokan, payment gateway, escrow, credit card
Requires at least: 5.6
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 2.22.2
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

The official TradeSafe plugin for WooCommerce

== Description ==
TradeSafe provides an escrow payments-based solution that integrates seamlessly with your existing WordPress store.

With an average documented cart abandonment rate of 70%, it’s reported that many South Africans are becoming fearful when buying online. TradeSafe bridges this trust gap by ensuring customers only release funds once they’ve received what they paid for, increasing purchase confidence and improving conversion rates.

What sets TradeSafe apart is that we sit between payment and delivery — securing funds until the transaction is successfully completed, while still providing a seamless checkout experience.

Merchants have the flexibility to choose between escrow-secured payments or express payments, depending on their operational requirements and risk appetite.
At checkout, customers will see either the TradeSafe or Relay branding, depending on whether escrow-secured or express payment flow is selected.

The TradeSafe plugin includes payment gateways namely Ozow (Instant EFT), Visa/Mastercard, PayJustNow (Buy Now Pay Later), SnapScan, RCS, Diners Club, and normal EFT, with many more to come.

Now incorporating Relay for direct settlement — improving cash flow, speeding up payments, and simplifying reconciliation across supported payment methods.

The plugin also includes support for paying multiple vendors when using the Dokan plugin.

### How it Works

- At checkout, the customer chooses to pay using TradeSafe.
- Based on your configuration, the transaction follows either an escrow-secured flow (TradeSafe) or an express payment flow (Relay).
- The customer is directed to a payment page where they select their preferred payment method (EFT, Instant EFT (Ozow), Card, SnapScan, RCS, Diners Club, or Buy Now Pay Later (PayJustNow)).

- For express payments (Relay):
- Payment is processed and settled directly to you.
- The order is marked as PROCESSING and fulfilment begins immediately.

- For escrow-secured payments (TradeSafe):
- Once payment is successful, the order is marked as PROCESSING and delivery is kicked off.
- When delivery has been completed, the order moves to a DELIVERED state.
- TradeSafe notifies the customer via email and SMS to confirm whether the order was received as expected.
- If confirmed, funds are released to you, and/or your vendors if trading in a multi-vendor setup.
- The customer has 24 hours to respond. If no response is received, funds are automatically released.
- If the customer is unhappy, they can raise a dispute. The transaction is placed on hold, allowing you to resolve the matter in line with your return policies.
- No refund will be processed without your consent. TradeSafe manages refunds on your behalf.
- Customers must submit a material complaint with supporting evidence. Non-material or frivolous complaints will result in funds being released to you.

== Frequently Asked Questions ==

= How does TradeSafe benefit me as a merchant? =

- Boosts sales conversions by removing friction and building trust at checkout.
- More cost-efficient payment processing compared to traditional gateways.
- Flexibility to choose between escrow-secured or express payment flows.
- Faster settlement and improved reconciliation through direct settlement infrastructure.
- We enable split payments for each transaction which allows your digital store to earn a commission for each transaction. No longer do you have to do this manually.
- Refunds. Just hit that button, and we will refund the customer on your behalf.
- Wallet functionality. TradeSafe provides two settlement options whereby we either pay to the beneficiary's bank account, or we credit their escrow wallet where they can manually withdraw the funds in WooCommerce.
- We augment your store's credibility and reputational cred.

= How does TradeSafe benefit my customers? =

- Removes uncertainty and stress. An escrow holding account gives buyers the confidence to make that upfront payment.
- Multiple payment options for convenience within a single checkout experience.
- Customers are never redirected to another site, and they never need to register on a separate platform. We ensure a seamless user journey.

= How and when do I get paid? =

We provide you two options how the funds are to be disbursed to you and/or your vendors.

1. Automatically. TradeSafe will make payment to your bank account. There is an additional small charge per transaction should you choose this option.
2. Wallet withdraw. You can choose to withdrawal your funds from your WooCommerce dashboard.

The payment frequencies can be set at your discretion. We will pay the funds to you either on a daily, weekly, bi-monthly, or monthly basis. Some merchants prefer to be paid at the end of the month, and some immediately. Just tell us what would suit you.

= What is Relay" =

Relay is TradeSafe’s direct settlement infrastructure, enabling payments to be processed and settled directly to you without going through the escrow workflow. It improves cash flow, speeds up payment processing, and simplifies reconciliation, while supporting the same payment methods your customers already know about.

= How do I configure Relay for my customers? =

Relay can be enabled by selecting the express payment option within your TradeSafe merchant or plugin settings. When enabled, transactions are processed via Relay and bypass the escrow workflow, with funds settled directly to you. Customers will see Relay branding at checkout for these transactions.

= Does the plugin support both production mode and sandbox mode for testing? =

Absolutely. You can toggle between production and sandbox easily within your TradeSafe merchant dashboard.

= How do I sign up? =

Simply go to [www.tradesafe.co.za](https://www.tradesafe.co.za) and sign up for a merchant account at the top right of the page. Complete a few wizards, and you will then be granted access to the developer dashboard where you can begin installing and testing the plug-in in a testing environment.

Once you have tested, and everything is working great, then we will perform some verification procedures before taking you live.

= Where can I find documentation? =

For more information on how to install and configure the plugin, please refer to our [guide](https://developer.tradesafe.co.za/docs/1.2/plugins/woocommerce)

= Who do I reach out to if I need technical support or if I have any further queries? =

Please do not hesitate to email [support@tradesafe.co.za](mailto:support@tradesafe.co.za) or phone us on 010 020 3101. We are here to help you every step of the way.

== Screenshots ==

1. Checkout Page
2. Payment Page
2. Mobile Payment Page

== Changelog ==

= 2.22.2 2025-08-27 =

### Bug Fixes

* Changed how user data is checked to allow a withdraw in dokan ([e83205](https://github.com/tradesafe/woocommerce/commit/e832059ac9b7cbb0e820678303913c540496f2de))


= 2.22.1 2025-08-25 =

### Features

* Require php 8.0 ([1e61fb](https://github.com/tradesafe/woocommerce/commit/1e61fb663939ceb3fc7ead143d5023b4f9bfc931))


= 2.21.0 2025-08-25 =

### Features

* Set minimum php version to 8.0 ([d66cdc](https://github.com/tradesafe/woocommerce/commit/d66cdc4c423ebbd80bcd0452817a016b85d2db58))

### Bug Fixes

* Changed array key from status to action ([e54908](https://github.com/tradesafe/woocommerce/commit/e54908796595e3a87d3b1154d826c48aea9d44ba))

##### Deps

* Update composer (#49) ([1baf26](https://github.com/tradesafe/woocommerce/commit/1baf265354afe959bbf81b2605304d7d932fdad0))
