<?php
namespace OAuth2\Storage;

use OAuth2\OpenID\Storage\UserClaimsInterface;
use OAuth2\OpenID\Storage\AuthorizationCodeInterface as OpenIDAuthorizationCodeInterface;
use OAuth2\GrantType\Anonymous as Anonymous;

/**
 * FIMDatabase implementation of OAuth
 *
 * @author Joseph Parsons
 */
class FIMDatabaseOAuth implements
    AuthorizationCodeInterface,
    AccessTokenInterface,
    ClientCredentialsInterface,
    UserCredentialsInterface,
    RefreshTokenInterface,
    UserClaimsInterface,
    OpenIDAuthorizationCodeInterface
{
    protected $db;
    protected $config;
    protected $exceptionHandler;

    const CLIENT_TABLE_FIELDS = 'client_id, client_secret, redirect_uri, grant_types, scope, user_id';
    const ACCESS_TOKEN_TABLE_FIELDS = 'access_token, client_id, user_id, anon_id, expires, scope';
    const AUTHORIZATION_CODE_TABLE_FIELDS = 'authorization_code, client_id, user_id, redirect_uri, expires, scope, id_token';
    const REFRESH_TOKEN_TABLE_FIELDS = 'refresh_token, client_id, user_id, anon_id, expires, scope';

    public function __construct($db, $exceptionHandler, $config = array())
    {
        if (!$db instanceof \FIMDatabase) {
            throw new $exceptionHandler('First argument to OAuth2\Storage\FIMDatabase must be an instance of FIMDatabase');
        }

        $this->db = $db;
        $this->config = array_merge(array(
            'client_table' => $db->sqlPrefix . 'oauth_clients',
            'access_token_table' => $db->sqlPrefix . 'oauth_access_tokens',
            'refresh_token_table' => $db->sqlPrefix . 'oauth_refresh_tokens',
            'code_table' => $db->sqlPrefix . 'oauth_authorization_codes',
            'user_table' => $db->sqlPrefix . 'oauth_users',
            'jwt_table'  => $db->sqlPrefix . 'oauth_jwt',
            'jti_table'  => $db->sqlPrefix . 'oauth_jti',
            'scope_table'  => $db->sqlPrefix . 'oauth_scopes',
            'public_key_table'  => $db->sqlPrefix . 'oauth_public_keys',
        ), $config);
        $this->exceptionHandler = $exceptionHandler;
    }

    /* OAuth2\Storage\ClientCredentialsInterface */
    public function checkClientCredentials($client_id, $client_secret = null)
    {
        $result = $this->db->where(array('client_id' => $client_id))->select(array($this->config['client_table'] => self::CLIENT_TABLE_FIELDS))->getAsArray(false);

        // make this extensible
        return $result && $result['client_secret'] == $client_secret;
    }

    public function isPublicClient($client_id)
    {

        $result = $this->db->where(array('client_id' => $client_id))->select(array($this->config['client_table'] => self::CLIENT_TABLE_FIELDS))->getAsArray(false);

        if (!$result) {
            return false;
        }

        return empty($result['client_secret']);
    }

    /* OAuth2\Storage\ClientInterface */
    public function getClientDetails($client_id)
    {
        return $this->db->where(array('client_id' => $client_id))->select(array($this->config['client_table'] => self::CLIENT_TABLE_FIELDS))->getAsArray(false);
    }

    public function setClientDetails($client_id, $client_secret = null, $redirect_uri = null, $grant_types = null, $scope = null, $user_id = null)
    {
        return $this->db->upsert($this->config['client_table'], array(
            'client_id' => $client_id
        ), array(
            'client_secret' => $client_secret,
            'redirect_uri' => $redirect_uri,
            'grant_types' => $grant_types,
            'scope' => $scope,
            'user_id' => $user_id
        ));
    }

    public function checkRestrictedGrantType($client_id, $grant_type)
    {
        $details = $this->getClientDetails($client_id);
        if (isset($details['grant_types'])) {
            $grant_types = explode(' ', $details['grant_types']);

            return in_array($grant_type, (array) $grant_types);
        }

        // if grant_types are not defined, then none are restricted
        return true;
    }

    /* OAuth2\Storage\AccessTokenInterface */
    public function getAccessToken($access_token)
    {
        // Note: the original PDO implementation converted expires into a timestamp here as well. The FIMDatabase layer stores/fetches _all_ dates as timestamps (for reasons like this), so the conversion is unecessary.
        return $this->db->where(array('access_token' => $access_token))->select(array($this->config['access_token_table'] => self::ACCESS_TOKEN_TABLE_FIELDS))->getAsArray(false);
    }

    public function setAccessToken($access_token, $client_id, $user_id, $expires, $scope = null)
    {
        global $anonId;

        // Delete tokens that expired more than a minute ago.
        $this->db->delete($this->config['access_token_table'], [
            'expires' => $this->db->now(-60, 'lt')
        ]);

        return $this->db->upsert($this->config['access_token_table'], array(
            'access_token' => $access_token
        ), array(
            'client_id' => $client_id,
            'expires' => $expires,
            'user_id' => $user_id,
            'scope' => $scope,
            'http_user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''  ,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'anon_id' => ($user_id === \fimUser::ANONYMOUS_USER_ID ? $anonId : 0),
        ));
    }

    public function unsetAccessToken($access_token)
    {
        return $this->db->delete($this->config['access_token_table'], array(
            'access_token' => $access_token
        ));
    }

    /* OAuth2\Storage\AuthorizationCodeInterface */
    public function getAuthorizationCode($code)
    {
        // Note: the original PDO implementation converted expires into a timestamp here as well. The FIMDatabase layer stores/fetches _all_ dates as timestamps (for reasons like this), so the conversion is unecessary.
        return $this->db->where(array('authorization_code' => $code))->select(array($this->config['code_table'] => self::AUTHORIZATION_CODE_TABLE_FIELDS))->getAsArray(false);
    }

    public function setAuthorizationCode($code, $client_id, $user_id, $redirect_uri, $expires, $scope = null, $id_token = null)
    {
        if (func_num_args() > 6) { // ...Wait, why?
            // we are calling with an id token
            return call_user_func_array(array($this, 'setAuthorizationCodeWithIdToken'), func_get_args());
        }


        return $this->db->upsert($this->config['code_table'], array(
            'authorization_code' => $code
        ), array(
            'client_id' => $client_id,
            'user_id' => $user_id,
            'redirect_uri' => $redirect_uri,
            'expires' => $expires,
            'scope' => $scope
        ));
    }

    /* I don't know why this function exists. */
    private function setAuthorizationCodeWithIdToken($code, $client_id, $user_id, $redirect_uri, $expires, $scope = null, $id_token = null)
    {
        return $this->db->upsert($this->config['code_table'], array(
            'authorization_code' => $code
        ), array(
            'client_id' => $client_id,
            'user_id' => $user_id,
            'redirect_uri' => $redirect_uri,
            'expires' => $expires,
            'scope' => $scope,
            'id_token' => $id_token,
        ));
    }

    public function expireAuthorizationCode($code)
    {
        return $this->db->delete($this->config['code_table'], array(
            'access_token' => $code
        ));
    }

    /* OAuth2\Storage\UserCredentialsInterface */
    public function checkUserCredentials($username, $password)
    {
        if ($user = $this->getUser($username)) {
            return $this->checkPassword($user, $password);
        }

        return false;
    }

    public function getUserDetails($username)
    {
        return $this->getUser($username);
    }

    /* UserClaimsInterface */
    public function getUserClaims($user_id, $claims)
    {
        if (!$userDetails = $this->getUserDetails($user_id)) {
            return false;
        }

        $claims = explode(' ', trim($claims));
        $userClaims = array();

        // for each requested claim, if the user has the claim, set it in the response
        $validClaims = explode(' ', self::VALID_CLAIMS);
        foreach ($validClaims as $validClaim) {
            if (in_array($validClaim, $claims)) {
                if ($validClaim == 'address') {
                    // address is an object with subfields
                    $userClaims['address'] = $this->getUserClaim($validClaim, $userDetails['address'] ?: $userDetails);
                } else {
                    $userClaims = array_merge($userClaims, $this->getUserClaim($validClaim, $userDetails));
                }
            }
        }

        return $userClaims;
    }

    protected function getUserClaim($claim, $userDetails)
    {
        $userClaims = array();
        $claimValuesString = constant(sprintf('self::%s_CLAIM_VALUES', strtoupper($claim)));
        $claimValues = explode(' ', $claimValuesString);

        foreach ($claimValues as $value) {
            $userClaims[$value] = isset($userDetails[$value]) ? $userDetails[$value] : null;
        }

        return $userClaims;
    }

    /* OAuth2\Storage\RefreshTokenInterface */
    public function getRefreshToken($refresh_token)
    {
        $token = $this->db->where(array('refresh_token' => $refresh_token))->select(array($this->config['refresh_token_table'] => self::REFRESH_TOKEN_TABLE_FIELDS))->getAsArray(false);

        if ($token && $token['anon_id']) {
            global $anonId;
            $anonId = (int) $token['anon_id'];
        }

        return $token;
    }

    public function setRefreshToken($refresh_token, $client_id, $user_id, $expires, $scope = null)
    {
        global $anonId;

        // Delete tokens that expired more than a day ago.
        $this->db->delete($this->config['access_token_table'], [
            'expires' => $this->db->now(-60 * 60 * 24, 'lt')
        ]);

        return $this->db->insert($this->config['refresh_token_table'], array(
            'refresh_token' => $refresh_token,
            'client_id' => $client_id,
            'user_id' => $user_id,
            'anon_id' => $anonId,
            'expires' => $expires,
            'scope' => $scope,
        ));
    }

    public function unsetRefreshToken($refresh_token)
    {
        return $this->db->delete($this->config['refresh_token_table'], array(
            'refresh_token' => $refresh_token,
        ));
    }

    protected function checkPassword($user, $password)
    {
        return $user['userObj']->checkPasswordAndLockout($password);
    }

    public function getUser($username)
    {
        // if(integration) {
        //   $userArray = integration->select()
        //   if (fimUser($userArray['userId'])) update name, etc.
        //   else create user w/ name

        require_once(__DIR__ . '/../../../../fimUser.php');

        $userData = $this->db->getUsers(array(
            'userNames' => array($username),
            'includePasswords' => true
        ))->getAsUser();

        if ($userData->isValid()) {
            return array(
                'user_id' => $userData->id,
                'username' => $userData->name,
                'userObj' => $userData,
            );
        }
        else {
            throw new $this->exceptionHandler('noUser', 'No user was found by the specified username.');
        }
    }

    public function getUserFromId($userId)
    {
        $userData = $this->db->getUser($userId);

        return array(
            'user_id' => $userData->id,
            'username' => $userData->name,
            'userObj' => $userData,
        );
    }


    public function getClientScope($client_id)
    {
        if (!$clientDetails = $this->getClientDetails($client_id)) {
            return false;
        }

        if (isset($clientDetails['scope'])) {
            return $clientDetails['scope'];
        }

        return null;
    }

    /**
     * Clean expired entries from the database. (Also invokes fimDatabase's clean methods.)
     */
    public function cleanSessions() {
        $this->db->delete($this->db->sqlPrefix . 'oauth_access_tokens', array(
            'expires' => $this->db->now(-300, 'lte')
        ));

        $this->db->delete($this->db->sqlPrefix . 'oauth_authorization_codes', array(
            'expires' => $this->db->now(-300, 'lte')
        ));

        $this->db->cleanLockout();
        $this->db->cleanPermissionsCache();
        $this->db->cleanAccessFlood();
        $this->db->cleanMessageFlood();
    }
}
?>