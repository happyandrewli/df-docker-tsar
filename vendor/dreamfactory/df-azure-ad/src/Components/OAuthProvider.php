<?php
namespace DreamFactory\Core\AzureAD\Components;

use Illuminate\Http\Request;
use GuzzleHttp\ClientInterface;
use SocialiteProviders\Manager\OAuth2\User;
use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use DreamFactory\Core\OAuth\Components\DfOAuthTwoProvider;

class OAuthProvider extends AbstractProvider implements ProviderInterface
{
    use DfOAuthTwoProvider;

    const API_VERSION = '1.6';

    /** @var null|string */
    protected $tokenUrl = null;

    /** @var null|string */
    protected $authUrl = null;

    /** @var array */
    protected $scopes = ['User.Read'];

    /** @var string */
    protected $resource = 'https://graph.windows.net/';

    /** @var string */
    protected $graphUrl = 'https://graph.windows.net/';

    /**
     * @param string $clientId
     * @param string $clientSecret
     * @param string $redirectUrl
     */
    public function __construct($clientId, $clientSecret, $redirectUrl)
    {
        /** @var Request $request */
        $request = \Request::instance();
        parent::__construct($request, $clientId, $clientSecret, $redirectUrl);
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            $this->authUrl, $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->tokenUrl;
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $userUrl = $this->graphUrl . 'me?api-version=' . static::API_VERSION;
        $response = $this->getHttpClient()->get($userUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['objectId'],
            'nickname' => $user['displayName'],
            'name'     => $user['givenName'] . ' ' . $user['surname'],
            'email'    => $user['userPrincipalName'],
            'avatar'   => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $postKey = (version_compare(ClientInterface::VERSION, '6') === 1) ? 'form_params' : 'body';
        $postValue = $this->getTokenFields($code);

        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            $postKey  => $postValue,
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCodeFields($state = null)
    {
        $fields = [
            'client_id'     => $this->clientId,
            'scope'         => $this->formatScopes($this->getScopes(), $this->scopeSeparator),
            'response_type' => 'code',
            'resource'      => $this->resource,
            'redirect_uri'  => $this->redirectUrl,
        ];

        if ($this->usesState()) {
            $fields['state'] = $state;
        }

        return array_merge($fields, $this->parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        $fields = [
            'grant_type'   => 'authorization_code',
            'client_id'    => $this->clientId,
            'code'         => $code,
            'redirect_uri' => $this->redirectUrl,
        ];

        if (!empty($this->clientSecret)) {
            $fields['client_secret'] = $this->clientSecret;
        }

        return $fields;
    }

    /**
     * Sets the OAuth2 endpoints based on tenant ID
     *
     * @param $tenantId
     */
    public function setEndpoints($tenantId)
    {
        $this->tokenUrl = 'https://login.microsoftonline.com/' . $tenantId . '/oauth2/token';
        $this->authUrl = 'https://login.microsoftonline.com/' . $tenantId . '/oauth2/authorize';
    }

    /**
     * Sets the OAuth 2 resource
     *
     * @param $resource
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
    }

}