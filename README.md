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
