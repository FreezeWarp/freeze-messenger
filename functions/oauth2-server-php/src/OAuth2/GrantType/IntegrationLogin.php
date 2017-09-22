<?php
namespace OAuth2\GrantType;

use OAuth2\Storage\UserCredentialsInterface;
use OAuth2\ResponseType\AccessTokenInterface;
use OAuth2\RequestInterface;
use OAuth2\ResponseInterface;

/**
 *
 * @author Joseph T. Parsons
 */
class IntegrationLogin implements GrantTypeInterface
{
    private $userInfo;
    protected $storage;

    /**
     * @param UserCredentialsInterface $storage REQUIRED Storage class for retrieving user credentials information
     */
    public function __construct(UserCredentialsInterface $storage, \fimUser $user)
    {
        $this->storage = $storage;
        $this->userInfo['user_id'] = $user->id;
    }

    public function getQuerystringIdentifier()
    {
        return 'integrationLogin';
    }

    public function validateRequest(RequestInterface $request, ResponseInterface $response)
    {
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