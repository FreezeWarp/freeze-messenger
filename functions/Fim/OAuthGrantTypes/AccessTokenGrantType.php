<?php
namespace Fim\OAuthGrantTypes;

use OAuth2\GrantType\GrantTypeInterface;
use OAuth2\Storage\UserCredentialsInterface;
use OAuth2\ResponseType\AccessTokenInterface;
use OAuth2\RequestInterface;
use OAuth2\ResponseInterface;
use LogicException;

class AccessTokenGrantType implements GrantTypeInterface
{
    /**
     * @var array
     */
    private $userInfo;
    /**
     * @var UserCredentialsInterface
     */
    protected $storage;
    /**
     * @param UserCredentialsInterface $storage - REQUIRED Storage class for retrieving user credentials information
     */
    public function __construct(UserCredentialsInterface $storage)
    {
        $this->storage = $storage;
    }
    /**
     * @return string
     */
    public function getQueryStringIdentifier()
    {
        return 'access_token';
    }
    /**
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @return bool|mixed|null
     *
     * @throws LogicException
     */
    public function validateRequest(RequestInterface $request, ResponseInterface $response)
    {
        if (!$request->request("access_token")) {
            $response->setError(400, 'invalid_request', 'Missing parameter: "access_token" required');
            return null;
        }
        $accessToken = $this->storage->getAccessToken($request->request("access_token"));
        if (empty($accessToken)) {
            $response->setError(400, 'invalid_grant', 'Unable to retrieve access token.');
            return null;
        }
        $userInfo = $this->storage->getUserFromId($accessToken['user_id']);
        if (empty($userInfo)) {
            $response->setError(400, 'invalid_grant', 'Unable to retrieve user information');
            return null;
        }
        if (!isset($userInfo['user_id'])) {
            throw new \LogicException("you must set the user_id on the array returned by getUserDetails");
        }
        $this->userInfo = $userInfo;
        return true;
    }
    /**
     * Get client id
     *
     * @return mixed|null
     */
    public function getClientId()
    {
        return null;
    }
    /**
     * Get user id
     *
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userInfo['user_id'];
    }
    /**
     * Get scope
     *
     * @return null|string
     */
    public function getScope()
    {
        return isset($this->userInfo['scope']) ? $this->userInfo['scope'] : null;
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