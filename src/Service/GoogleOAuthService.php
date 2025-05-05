<?php

namespace App\Service;

use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class GoogleOAuthService
{
    private Google $provider;
    private Request $request;

    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
        
        $this->provider = new Google([
            'clientId'     => '982307941710-dhcue5105sfqaedhkad2tjr6g5d9pvtm.apps.googleusercontent.com',
            'clientSecret' => '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',
            'redirectUri'  => 'http://127.0.0.1:8000/connect/google/check',
        ]);
    }

    public function getAuthorizationUrl(): string
    {
        $options = [
            'scope' => ['email', 'profile']
        ];
        
        return $this->provider->getAuthorizationUrl($options);
    }

    public function getAccessToken(string $code): AccessToken
    {
        return $this->provider->getAccessToken('authorization_code', [
            'code' => $code
        ]);
    }

    public function getUserInfo(AccessToken $accessToken): array
    {
        $resourceOwner = $this->provider->getResourceOwner($accessToken);
        return $resourceOwner->toArray();
    }
} 