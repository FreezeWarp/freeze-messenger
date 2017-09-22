<?php
require_once('LoginDatabase.php');
class LoginOAuth extends LoginDatabase {
    /**
     * @var LoginFactory
     */
    public $loginFactory;

    /**
     * @var fimDatabase
     */
    public $database;


    public function __construct(LoginFactory $loginFactory, fimDatabase $database) {
        $this->loginFactory = $loginFactory;
        $this->database = $database;
    }

    public function hasLoginCredentials(): bool {
        return isset($_REQUEST['grant_type']);
    }

    public function getLoginCredentials() {
        return;
    }

    public function setUser() {
        $this->database->cleanSessions();

        /* Depending on which grant_type is set, we interact with the OAuth layer a little bit differently. */
        switch ($_REQUEST['grant_type']) {
            case 'anonymous':
                $this->oauthGrantType = new OAuth2\GrantType\Anonymous($this->loginFactory->oauthStorage);
            break;

            case 'access_token':
                $this->oauthGrantType = new OAuth2\GrantType\AccessToken($this->loginFactory->oauthStorage);
            break;

            case 'refresh_token':
                $this->oauthGrantType = new OAuth2\GrantType\RefreshToken($this->loginFactory->oauthStorage, [
                    'always_issue_new_refresh_token' => true
                ]);
            break;

            default:
                new fimError('invalidGrantType', 'The grant type specified is invalid or unsupported.');
        }
    }
}