<?php
/**
 * @author Maxime Renou <maxime@bluesquare.io>
 */

namespace Bluesquare\LinkedInAPIv2;

class LinkedInClient
{
    protected $client_id, $client_secret, $redirect_uri, $scopes;

    /**
     * LinkedInClient constructor.
     * @param string $client_id
     * @param string $client_secret
     * @param string $redirect_uri
     * @param array $scopes
     */
    public function __construct($client_id, $client_secret, $redirect_uri, $scopes = null)
    {
        if (!is_array($scopes))
            $scopes = ['r_liteprofile', 'r_emailaddress', 'w_member_social'];

        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->redirect_uri = $redirect_uri;
        $this->scopes = $scopes;
    }

    public function setRedirectUri($redirect_uri)
    {
        $this->redirect_uri = $redirect_uri;
    }

    public function stringifyScopes()
    {
        return implode(',', $this->scopes);
    }

    /**
     * Did we get an authorization code from LinkedIn?
     * @return bool
     */
    public function checkAuthorizationCallback()
    {
        return !empty($_GET['code']);
    }

    public function getAuthorizationURL()
    {
        $scopes = $this->stringifyScopes();
        $redirect_uri = urlencode($this->redirect_uri);
        $state = 'qwerty';
        $url = "https://www.linkedin.com/oauth/v2/authorization?client_id={$this->client_id}&redirect_uri={$redirect_uri}&scope={$scopes}&response_type=code&state={$state}";
        return $url;
    }

    /**
     * Redirect to authorization URL
     */
    public function authorize()
    {
        $url = $this->getAuthorizationURL();
        header("Location: $url");
        die;
    }

    /**
     * Did authorization failed?
     * @return bool
     */
    public function authorizationFailed()
    {
        return !empty($_GET['status']) && $_GET['status'] === 'user_cancelled_login';
    }

    /**
     * Get LinkedInUserClient from code
     * @param null|string $code
     * @return LinkedInUserClient
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getUserClient($code = null)
    {
        if (is_null($code)) {
            if (empty($_GET['code'])) {
                throw new \Exception("Missing authorization code");
            }
            else {
                $code = $_GET['code'];
            }
        }

        $client = new \GuzzleHttp\Client();
        $res = $client->request('POST', 'https://www.linkedin.com/uas/oauth2/accessToken', [
            'query' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'redirect_uri' => $this->redirect_uri
            ]
        ]);

        $body = $res->getBody();

        if ($res->getStatusCode() !== 200)
            throw new \Exception("LinkedIn token request failed: ".$body);

        if (($data = \json_decode($body, true)) === false)
            throw new \Exception("Invalid JSON from LinkedIn: ".$body);

        if (empty($data['access_token']))
            throw new \Exception("Missing access_token from LinkedIn: ".$body);

        return new LinkedInUserClient($data['access_token']);
    }
}