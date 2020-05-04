# Mobiletech Module

## Installation

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