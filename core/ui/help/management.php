<?php

get_current_screen()->add_help_tab( array(
	'id'      => 'overview',
	'title'   => Shopp::__('Overview'),
	'content' => Shopp::_mx(

'### Management

The **Management** settings allow you to tailor order management to your order processing workflow.

#### Dashboard Widgets

You can turn off the widgets displayed in the **WordPress Dashboard** if you prefer to not have them.

#### Order Status Labels

The **Order Status Labels** setting allows you to setup custom order status labels and map them to Shopp order workflow events. When an event on an order occurs, Shopp will automatically update the order status to your preferred order status label. For example, a label set up as  "Complete" can be mapped to the event **Paid**. As soon as an order has been paid for by a customer, it will automatically be set to "Complete".

The order events you can map the **Order Status Labels** to include:

* **Review**
For an order when it is under review by the bank or credit card processor.
* **Purchase Order**
An order is created as a purchase order when the customer submits their order.
* **Invoiced**
An invoiced order is accepted by the merchant to establish an order balance.
* **Authorized**
When a payment submitted by the customer is authorized by the credit card processor.
* **Paid**
When funds from the payment are captured into the merchant account.
* **Shipped**
As soon as an item shipment notice is submitted.
* **Refunded**
When payment for an order is refunded.
* **Void**
When an order is cancelled.

#### Order Cancellation Reasons

You can setup a predefined list of order cancellation reasons that can be used to speed up the logging process of refunding and cancelling orders.

#### Next Order Number

Order numbers are sequentially issued, but you can adjust the next order number issued to better align orders, or to start at a higher number. If set to a number that has already been issued to an order, the next available number will be used instead.

',

'Management help tab')
) );
