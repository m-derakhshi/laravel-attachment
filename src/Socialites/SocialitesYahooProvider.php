<?php

namespace MDerakhshi\LaravelAttachment\Socialites;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class SocialitesYahooProvider extends AbstractProvider implements ProviderInterface
{
    public const IDENTIFIER = 'YAHOO';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['openid2'];

    /**
     * Note: When redirectUrl is OOB, it will not add openid2_realm in params
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        $parseUrl = parse_url($this->redirectUrl);
        if (array_key_exists('scheme', $parseUrl) && array_key_exists('host', $parseUrl)) {
            $this->with(['openid2_realm' => $parseUrl['scheme'].'://'.$parseUrl['host']]);
        }

        return $this->buildAuthUrlFromBase('https://api.login.yahoo.com/oauth2/request_auth', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.login.yahoo.com/oauth2/get_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.login.yahoo.com/openid/v1/userinfo', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * Maps Yahoo object to User Object.
     *
     * Note: To have access to e-mail, you need to request "Profiles (Social Directory) - Read/Write Public and Private"
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => Arr::get($user, 'sub'),
            'nickname' => Arr::get($user, 'nickname', Arr::get($user, 'sub')),
            'name' => trim(sprintf('%s %s', Arr::get($user, 'given_name'), Arr::get($user, 'family_name'))),
            'email' => Arr::get($user, 'email'),
            'avatar' => Arr::get($user, 'picture'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }
}
