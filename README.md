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
