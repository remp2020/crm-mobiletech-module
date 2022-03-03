# Mobiletech Module

[![Translation status @ Weblate](https://hosted.weblate.org/widgets/remp-crm/-/mobiletech-module/svg-badge.svg)](https://hosted.weblate.org/projects/remp-crm/mobiletech-module/)

---

_**WARNING: This is experimental WIP module. We do not recommend using this module in production.**_

---

## Installation

We recommend using Composer for installation and update management. To add CRM Mobiletech extension to your [REMP CRM](https://github.com/remp2020/crm-skeleton/) application use following command:

```bash
composer require remp/crm-mobiletech-module
```

Enable installed extension in your `app/config/config.neon` file:

```neon
extensions:
	# ...
	- Crm\MobiletechModule\DI\MobiletechModuleExtension
```

When your installation is ready and configured with Mobiletech (they know your [webhook API endpoint](#webhook-for-accepting-messages)), start accepting the messages.

We recommend creating separate asynchronous Hermes handler listening to `mobiletech-inbound` events and process inbound messages asynchronously.

In the processing class, you should trigger events or do actions based on the inbound message content. When you want to respond to the inbound message, you can trigger `MobiletechNotificationEvent` that'll handle the sending:

```
$this->emitter->emit(new MobiletechNotificationEvent(
    $this->emitter,
    new MobiletechNotificationEnvelope($inboundMessage),
    $inboundMessage->user, // reference to user the message is being sent to
    'unregistered_number', // reference to mobiletech_templates.code DB column 
    [] // parameters to be injected to the template
));
``` 

Outbound message expect a `mobiletech_template` to be provided. Content is processed as a `twig` template and can be injected with variables provided as parameters within `MobiletechNotificationEvent`. 

Please check [`MobiletechNotificationEvent`](./src/Events/MobiletechNotificationEvent.php) and [`MobiletechNotificationEnvelope`](./src/Events/MobiletechNotificationEnvelope.php) to understand the provided parameters and context.

## MobiletechAuthenticator

Authenticator is automatically registered alongside of other authenticators when extension is enabled.

`MobiletechAuthenticator` expects phone number in field `mobile_phone` or `username` field already used by `UsernameAuthenticator`. Reason for this is compatibility with already existing login forms. In case user uses phone number instead of email, he will be logged _(if we find match)_. Password is still required for this type of login.

## Webhook for accepting messages

Mobiletech requires the application to expose webhook to accept messages and respond in a specific way if they're accepted.

Webhook is available at `/api/v1/mobiletech/webhook`.

When message is received, webhook stores the message to `mobiletech_inbound_messages` and emits `mobiletech-inbound` Hermes event with reference to the stored message in case you want to do some extra processing.

## Development, live testing and release

By default, the module uses `Crm\MobiletechModule\Model\MockApiClient` to fake the communication with SMS gateway. Client stores the information about sent messages even none of the messages are actually sent. It also updates the status of message to be "delivered".

To start using production implementation of the client (you need this to be able to send messages), please alter your `config.neon` file:

```neon
services:
	# ...
	mobiletechApiClient:
		class: Crm\MobiletechModule\Model\MobiletechApiClient
```

## Testing responses to received messages

To test whether the webhook integration works, you should respond to the received message.

We've prepared asynchronous Hermes listener waiting for the incoming message. It responds with the predefined message template and billing key. To enable this, register the handler and configure it:

```php
class FooModule extends Crm\ApplicationModule\CrmModule
{
    public function registerHermesHandlers(Tomaj\Hermes\Dispatcher $dispatcher)
    {
        $dispatcher->registerHandler(
            'mobiletech-inbound',
            $this->getInstance(\Crm\MobiletechModule\Hermes\TestInboundHandler::class)
        );
    }
```
```neon
services:
	# ...
	mobiletechTestInboundHandler:
		setup:
			- setBillKey(AA99) # get the billing key from Mobiletech
			- setTemplateCode(pong) # insert the testing template to mobiletech_templates DB table
```

Once configured, make sure you have a record with your phone number in `mobiletech_phone_numbers` DB table and send testing SMS to the short number provided by Mobiletech. Webhook will accept it, trigger the testing handler and respond to it with the template you configured.

## Migration

If you already used Mobiletech in your previous implementation, please check these steps to see what's required to migrate to CRM.

#### Importing users

First you need to make sure all the users exist (in some form) in the system.

* If you have user's email, you can use [`api/v1/users/email`](https://github.com/remp2020/crm-users-module#post-apiv1usersemail) API [`/api/v1/users/create`](https://github.com/remp2020/crm-users-module#get-apiv1userscreate) API respectively to make sure the user exist.
* If you don't have user's email, you'll need to create your own module and import the users: either via [custom API](https://github.com/remp2020/crm-skeleton#registerapicalls) or [custom command](https://github.com/remp2020/crm-skeleton#registercommands). Email of user is not required. Make sure, you fill `public_name` correctly (e.g. use the phone number) as that's the field that's going to be displayed around the system.

#### Linking users with Mobiletech phone numbers

Mobiletech module links the actual `phone_number` with `user_id` within `mobiletech_phone_numbers` table.

You can use `Crm\MobiletechModule\Repository\MobiletechPhoneNumbersRepository::add()` to create link between phone number and a user.

- `phone_number` is expected to be in local format (`09xx123456`) and be unique.
- `user_id` needs to reference existing user and be unique.

That means, that at this point users cannot have more than one phone number assigned to their account.

#### Importing inbound/outbound messages

Mobiletech module keeps logs of all inbound and outbound messages. These messages are used to create/charge recurring payments, so make sure they're imported correctly.

To import the data, you should mainly follow database schema of `mobiletech_inbound_messages` and `mobiletech_outbound_messages`, whichs mostly mirror Mobiletech's API. Important notes:

- `mobiletech_id` always references ID of mobiletech message. For inbound messages, it's `id` parameter of `receive` message. For outbound messages, it's `id` parameter of `rcv_rsp` message (confirmation from Mobiletech).
- `user_id` is the reference to CRM user. Some messages might be received from/sent to users who are not present in the CRM and who are not registered during handling of the message. In that case you can keep the reference `NULL`.
- `from` is the sender's phone number in the international format (as received from Mobiletech).

##### Outbound-specific

- `mobiletech_template_id` references content of SMS sent to the user (in outbound messages). This is mandatory field and you need to seed the templates first before running this import.
- `status` is the `status` parameter of `status` message. It's the message sent from Mobiletech after sending the message to phone number.
- `rcv_msg_id` is the `mobiletech_id` of inbound message, which triggered outbound message. This can be empty only for *push* messages (recurrent charges initiated by server).
- `billkey` is the actual bill key used to send the message.
- `payment_id` references payment, that initiated sending of message with paid billing key:
  - Payment should have the same amount as amount of billing key.
  - Payment should be using `mobiletech_recurrent` (or `mobiletech`) payment gateway. 
  - Payment should have all following `payment_meta` set:
    -  `mobiletech_billkey`
    -  `mobiletech_inbound_message_id`
    -  `mobiletech_template_code`
    -  `mobiletech_template_params`
    
    See the [Importing payments](#importing-payments) section for more information.

#### Importing payments

Following section expects the most advanced scenario to be used - recurrent sms payments with multiple types of subscriptions.

* You should be importing only successful payments.
* The payment should be using `mobiletech_recurrent` payment gateway.
* The payment should have following meta values set:
    -  `mobiletech_billkey`. Bill key that was used to charge the payment. This key should be the same one, as the one on `mobiletech_outbound_message` that's going to be linked to this payment via `mobiletech_outbound_messages.payment_id` column.
    -  `mobiletech_inbound_message_id`. ID of message imported in `mobiletech_inbound_messages` that initiated the payment.
    -  `mobiletech_template_code`. ID of `mobiletech_templates` that was used to send the message.
    -  `mobiletech_template_params`. JSON-encoded string with parameters used within the template. For the import it's OK to use empty JSON object.
* When payment is imported, link it to the `mobiletech_outbound_messages` record which "handled" the payment and charged the user. To maintain integrity, `paid` payments should be linked to delivered `outbound` messages with status `D1`.

When payments are linked to the outbound messages, create record in the `recurrent_payments` table:

- `cid` column refers to `mobiletech_inbound_messages.mobiletech_id` column. It's reference to the message that initiated the recurrent payment and holds all necessary information to generate next payment in the future.
- `charge_at` is the date when user should be charged again.
- `expires` can remain NULL.
- `payment_id` can remain NULL. CRM will set this once it attempts to charge the user.
- `retries` is the number of attempts to charge the user. You can use `4` (default in CRM).
- `parent_payment_id` is reference to payment which scheduled this recurrent charge. You should import **at least one** payment in order to make SMS charging work and reference it here. If you import all user's payments, use ID of **last successful mobiletech payment** as a parent.
- `state` should be `active` in order to trigger the charge.

Some columns are omitted as they're straigt forward or up to you to fill (e.g. `subscription_type_id`).

Integrity checks for recurrent payments:

- The `recurrent_payments.cid` **must** refer to `mobiletech_inbound_messages.mobiletech_id` that exists.
- There **must** be a `mobiletech_outbound_message.rcv_msg_id` that holds the value of `cid` (and references the inbound message from previous step).
