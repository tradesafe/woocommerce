=== TradeSafe Payment Gateway for WooCommerce ===
Contributors: richardnbanks, tradesafeops
Tags: woocommerce, dokan, payment gateway, escrow, credit card
Requires at least: 5.6
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 2.19.4
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

The official TradeSafe plugin for WooCommerce

== Description ==
TradeSafe, backed by Standard Bank, provides an escrow payments-based solution that integrates seamlessly with your existing WordPress store.

With an average documented cart abandonment rate of 70%, it’s reported that many South Africans are becoming fearful when buying online. This is where we come in. TradeSafe bridges the trust gap and eliminates the fear when customers want to make payment upfront in full, online. We give your customers the peace of mind that they will receive what they paid for, leading to a boost in purchase confidence, and ultimately improving conversion rates.

The TradeSafe plugin includes payment gateways namely Ozow (Instant EFT), Visa/Mastercard, PayJustNow (Buy Now Pay Later), SnapScan and normal EFT, with many more to come.

The plugin also includes support for paying multiple vendors when using the Dokan plugin.

### How it Works

- At checkout, the customer chooses to pay using TradeSafe.
- The customer will be directed to a payment page, where they choose their payment option namely EFT, Instant EFT (Ozow), Card, SnapScan, and Buy Now Pay Later (PayJustNow).
- Once payment is successful, the order is marked as PROCESSING and delivery is kicked off.
- When delivery has been completed, a new state called DELIVERED is reached. This triggers an email and sms which TradeSafe will send to the customer, asking them if they received what they ordered.
- If yes, TradeSafe releases the funds to you, and/or your vendors if trading in a multi-vendor setup.
- The customer has 24 hours in which to make their decision. If we do not hear from the customer within that time, TradeSafe will deem this as acceptance on the buyer's part and automatically release the funds to you.
- If the customer is unhappy with the order, then the customer can submit a comment. The transaction will then be placed on hold in WooCommerce. This will give you the opportunity to enact your own return policies until a successful resolution has been achieved.
- No refund will be processed without your consent. TradeSafe performs all refunds on your behalf. We take away this admin burden.
- The buyer must have a material complaint and provide evidence. We will release the funds to you in the event the complaint/issue is non-material or frivolous.

== Frequently Asked Questions ==

= How does TradeSafe benefit me as a merchant? =

- Boosts sales conversions. TradeSafe gives new uninitiated customers the peace of mind to make that upfront purchase.
- We enable split payments for each transaction which allows your digital store to earn a commission for each transaction. No longer do you have to do this manually.
- Refunds. Just hit that button, and we will refund the customer on your behalf.
- Wallet functionality. TradeSafe provides two settlement options whereby we either pay to the beneficiary's bank account, or we credit their escrow wallet where they can manually withdraw the funds in WooCommerce.
- We augment your store's credibility and reputational cred

= How does TradeSafe benefit my customers? =

- Removes uncertainty and stress. An escrow holding account, together with Standard Bank’s logo, gives buyers the confidence to make that upfront payment.
- We provide multiple payment options for their convenience.
- Customers are never redirected to another site, and they never need to register on a separate platform. We ensure a seamless user journey.

= How and when do I get paid? =

We provide you two options how the funds are to be disbursed to you and/or your vendors.

1. Automatically. TradeSafe will make payment to your bank account. There is an additional small charge per transaction should you choose this option.
2. Wallet withdraw. You can choose to withdrawal your funds from your WooCommerce dashboard.

The payment frequencies can be set at your discretion. We will pay the funds to you either on a daily, weekly, bi-monthly, or monthly basis. Some merchants preferred to be paid at the end of the month, and some immediately. Just tell us what would suit you.

= Does the plugin support both production mode and sandbox mode for testing?

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

= 2.19.4 2025-05-05 =

### Bug Fixes

* Added type def to number format ([db7a57](https://github.com/tradesafe/woocommerce/commit/db7a57382acdd9463a59fc0cefb2a47eee993562))

= 2.19.3 2025-02-19 =

### Bug Fixes

* Updated callback to use order key instead of transaction id ([31447e](https://github.com/tradesafe/woocommerce/commit/31447e97fdae72a22d726e646097a22a69f6e771))

= 2.19.2 2025-02-14 =

### Bug Fixes

* Show old fee allocation option for sites that don't default to seller ([8ac37f](https://github.com/tradesafe/woocommerce/commit/8ac37f5dc6681142c88d59d85e768dad40fcc972))

= 2.19.1 2024-12-17 =

### Bug Fixes

* Removed unused signature check ([fb5dc8](https://github.com/tradesafe/woocommerce/commit/fb5dc858978fa1a736855842a341f2a38b15c305))

= 2.19.0 2024-12-10 =

### Features

* Log withdraw in Dokan when a transaction is made as delivered and a user has an automatic payout ([e973d7](https://github.com/tradesafe/woocommerce/commit/e973d7f16e97fd3a9e96f1179e6a345285df2b51))
