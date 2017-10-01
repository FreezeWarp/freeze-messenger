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
class Anonymous implements GrantTypeInterface
{
    private $userInfo;
    private $anonId;
    protected $storage;

    /**
     * @param OAuth2\Storage\UserCredentialsInterface $storage REQUIRED Storage class for retrieving user credentials information
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
        /* Use cache if possible, since the anonymous user will be queried quite frequently.
         * getUser(), which is called by getUserDetails(), may also implement a general cache of all users, but since cache is redundancy to begin with, I tend not to avoid it. */
        $cache = new \CacheFactory();
        if ($cache->exists('fim_oauth2_anonymousUserArray'))
            $userInfo = $cache->get('fim_oauth2_anonymousUserArray');
        else
            $cache->set('fim_oauth2_anonymousUserArray', $userInfo = $this->storage->getUserFromId(\fimUser::ANONYMOUS_USER_ID), 3600 * 24);

        /* Sanity checks */
        if (empty($userInfo)) {
            $response->setError(400, 'invalid_grant', 'Unable to retrieve user information');
            return null;
        }

        if (!isset($userInfo['user_id']))
            throw new \LogicException("you must set the user_id on the array returned by getUserDetails");


        /* Return */
        $this->anonId = rand(1000, 9999);
        $this->userInfo = $userInfo;
        return true;
    }

    public function getClientId()
    {
        return null;
    }

    public function getUserId()
    {
        return \fimUser::ANONYMOUS_USER_ID;
    }

    public function getAnonymousUserId()
    {
        return $this->anonId;
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