<?php

namespace Crm\MobiletechModule\Authenticator;

use Crm\ApplicationModule\Authenticator\AuthenticatorInterface;
use Crm\MobiletechModule\Repository\MobiletechPhoneNumbersRepository;
use Crm\UsersModule\Auth\UserAuthenticator;
use Crm\UsersModule\Events\UserSignInEvent;
use Nette\Database\Table\IRow;
use Nette\Localization\ITranslator;
use Nette\Security\AuthenticationException;
use Nette\Security\Passwords;

class MobiletechAuthenticator implements AuthenticatorInterface
{
    protected $source = UserSignInEvent::SOURCE_WEB;

    private $mobiletechPhoneNumbersRepository;

    private $translator;

    /** @var string */
    private $mobilePhone = null;

    /** @var string */
    private $password = null;

    public function __construct(
        MobiletechPhoneNumbersRepository $mobiletechPhoneNumbersRepository,
        ITranslator $translator
    ) {
        $this->mobiletechPhoneNumbersRepository = $mobiletechPhoneNumbersRepository;
        $this->translator = $translator;
    }

    public function setCredentials(array $credentials) : AuthenticatorInterface
    {
        $this->password = $credentials['password'] ?? null;
        if (isset($credentials['mobile_phone'])) {
            $this->mobilePhone = $this->sanitizeSlovakMobilePhoneNumber($credentials['mobile_phone']);
        }

        // if mobile phone number was not provided, try to check username field
        // (compatibility with existing login forms without any change)
        if ($this->mobilePhone === null && isset($credentials['username'])) {
            $this->mobilePhone = $this->sanitizeSlovakMobilePhoneNumber($credentials['username']);
        }

        return $this;
    }

    public function authenticate()
    {
        if ($this->mobilePhone !== null && $this->password !== null) {
            return $this->process();
        }

        return false;
    }

    public function getSource() : string
    {
        return $this->source;
    }

    public function shouldRegenerateToken(): bool
    {
        return false;
    }

    private function process(): IRow
    {
        $mobiletechPhoneNumber = $this->mobiletechPhoneNumbersRepository->findByMobilePhoneNumber($this->mobilePhone);
        if (!$mobiletechPhoneNumber) {
            throw new AuthenticationException($this->translator->translate('users.authenticator.identity_not_found'), UserAuthenticator::IDENTITY_NOT_FOUND);
        }
        $user = $mobiletechPhoneNumber->user;

        if (!Passwords::verify($this->password, $user[UserAuthenticator::COLUMN_PASSWORD_HASH])) {
            throw new AuthenticationException($this->translator->translate('users.authenticator.invalid_credentials'), UserAuthenticator::INVALID_CREDENTIAL);
        }

        return $user;
    }

    /**
     * Validate and sanitize slovak phone number. Finds number also in SME emails (09XXXXXXXX@post.sk).
     */
    private function sanitizeSlovakMobilePhoneNumber(string $phoneNumber): ?string
    {
        // remove spaces
        $phoneNumber = trim(preg_replace('/\s+/', '', $phoneNumber));

        // slovak mobile phone numbers start with 09 and have 8 more numbers; eg 0908123456
        preg_match('(09[0-9]{8})', $phoneNumber, $matches);

        if (isset($matches[0])) {
            return $matches[0];
        }
        return null;
    }
}
