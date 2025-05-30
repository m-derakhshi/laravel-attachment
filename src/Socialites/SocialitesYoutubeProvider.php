<?php

namespace MDerakhshi\LaravelAttachment\Socialites;

use GuzzleHttp\RequestOptions;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class SocialitesYoutubeProvider extends AbstractProvider implements ProviderInterface
{
    public const IDENTIFIER = 'YOUTUBE';

    /**
     * {@inheritdoc}
     */
    protected $scopes
        = [
            'https://www.googleapis.com/auth/youtube.readonly',
        ];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://accounts.google.com/o/oauth2/v2/auth',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://oauth2.googleapis.com/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://www.googleapis.com/youtube/v3/channels?part=snippet&mine=true',
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        $responseJson = json_decode((string) $response->getBody(), true);

        return $responseJson['items'][0] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['id'] ?? null,
            'nickname' => $user['snippet']['title'] ?? null,
            'name' => null,
            'email' => null,
            'avatar' => $user['snippet']['thumbnails']['high']['url'] ?? null,
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
