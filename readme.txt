=== TradeSafe Payment Gateway for WooCommerce ===
Contributors: richardnbanks, tradesafeops
Tags: woocommerce, dokan, payment gateway, escrow, credit card
Requires at least: 5.6
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 2.18.12
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

= 2.18.12 2024-09-27 =

### Bug Fixes

* Allow orders to be updated if order is marked as complete

= 2.18.11 2024-09-23 =

### Bug Fixes

* Changed how main orders are accepted

= 2.18.10 2024-09-09 =

### Bug Fixes

* Added note to order if marked as completed instead of delivered

= 2.18.9 2024-08-29 =

### Bug Fixes

* Added static definition to function

= 2.18.8 2024-08-28 =

### Bug Fixes

* Changed how withdrawals are executed with dokan

= 2.18.7 2024-08-20 =

### Bug Fixes

* Added check for missing transaction id meta data

= 2.18.6 2024-08-16 =

### Bug Fixes

* Removed option to set fee allocation

= 2.18.5 2024-08-15 =

### Bug Fixes

* Changed how user id is loaded when checking if a withdraw method is valid

= 2.18.4 2024-08-14 =

### Bug Fixes

* Changed how user id is loaded for withdraw request

= 2.18.3 2024-08-02 =

### Bug Fixes

* Change how user id is loaded when verifing withdrawals
* Mark orders status as on-hold when a dispute is raised

= 2.18.2 2024-07-24 =

### Bug Fixes

* Added additional infomation to dokan error message
* Updated label for payment frequency

= 2.18.1 2024-06-12 =

### Bug Fixes

* Added check for required fields for withdrawals in Dokan
* Allow suborders to be marked as complete

= 2.18.0 2024-06-07 =

### Features

* Added custom inspection period
