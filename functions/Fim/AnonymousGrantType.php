<?php
namespace Fim;

use OAuth2\GrantType\GrantTypeInterface;
use OAuth2\Storage\UserCredentialsInterface;
use OAuth2\ResponseType\AccessTokenInterface;
use OAuth2\RequestInterface;
use OAuth2\ResponseInterface;

/**
 *
 * @author Joseph T. Parsons
 */
class AnonymousGrantType implements GrantTypeInterface
{
    private $userInfo;
    private $anonId;
    protected $storage;

    /**
     * @param UserCredentialsInterface $storage REQUIRED Storage class for retrieving user credentials information
     */
    public function __construct(UserCredentialsInterface $storage)
    {
        $this->storage = $storage;
    }

    public function getQuerystringIdentifier()
    {
        return 'anonymous';
    }

    public function validateRequest(RequestInterface $request, ResponseInterface $response)
    {
        global $anonId;

        $anonId = rand(1000, 9999);

        $this->userInfo = [
            'user_id' => \Fim\User::ANONYMOUS_USER_ID,
        ];

        return true;
    }

    public function getClientId()
    {
        return null;
    }

    public function getUserId()
    {
        return $this->userInfo['user_id'];
    }

    public function getScope()
    {
        return isset($this->userInfo['scope']) ? $this->userInfo['scope'] : null;
    }

    public function createAccessToken(AccessTokenInterface $accessToken, $client_id, $user_id, $scope)
    {
        return $accessToken->createAccessToken($client_id, $user_id, $scope);
    }
}
?>