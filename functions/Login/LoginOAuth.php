<?php
namespace Login;

class LoginOAuth extends LoginDatabase {
    public function __construct(LoginFactory $loginFactory) {
        parent::__construct($loginFactory);
    }

    public function hasLoginCredentials(): bool {
        return isset($_REQUEST['grant_type']);
    }

    public function getLoginCredentials() {
        return;
    }

    public function setUser() {
        $this->loginFactory->oauthStorage->cleanSessions();

        /* Depending on which grant_type is set, we interact with the OAuth layer a little bit differently. */
        switch ($_REQUEST['grant_type']) {
            case 'anonymous':
                $this->oauthGrantType = new \Fim\OAuthGrantTypes\AnonymousGrantType($this->loginFactory->oauthStorage);
            break;

            case 'access_token':
                $this->oauthGrantType = new \Fim\OAuthGrantTypes\AccessTokenGrantType($this->loginFactory->oauthStorage);
            break;

            case 'refresh_token':
                $this->oauthGrantType = new \Fim\OAuthGrantTypes\RefreshTokenGrantType($this->loginFactory->oauthStorage, [
                    'always_issue_new_refresh_token' => true
                ]);
            break;

            default:
                new \Fim\Error('invalidGrantType', 'The grant type specified is invalid or unsupported.');
        }
    }
}