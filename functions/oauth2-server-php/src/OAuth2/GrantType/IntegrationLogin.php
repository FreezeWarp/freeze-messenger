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

    /**
     * @var \fimUser
     */
    private $userInfo;


    /**
     * @param \fimUser $user
     */
    public function __construct(\fimUser $user)
    {
        $this->userInfo = $user;
    }

    /**
     * @return string
     */
    public function getQueryStringIdentifier()
    {
        return 'integrationLogin';
    }

    /**
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @return true as long as a valid user ID was provided at construction.
     */
    public function validateRequest(RequestInterface $request, ResponseInterface $response)
    {
        return $this->userInfo->isValid();
    }

    /**
     * @return null
     */
    public function getClientId()
    {
        return null;
    }

    public function getUserId()
    {
        return $this->userInfo->id;
    }

    /**
     * @return null
     */
    public function getScope()
    {
        return null;
    }

    /**
     * Create access token
     *
     * @param AccessTokenInterface $accessToken
     * @param mixed                $client_id   - client identifier related to the access token.
     * @param mixed                $user_id     - user id associated with the access token
     * @param string               $scope       - scopes to be stored in space-separated string.
     * @return array
     */
    public function createAccessToken(AccessTokenInterface $accessToken, $client_id, $user_id, $scope)
    {
        return $accessToken->createAccessToken($client_id, $user_id, $scope);
    }
}
?>