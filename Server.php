<?php

namespace filsh\yii2\oauth2server;

use OAuth2\ClientAssertionType\ClientAssertionTypeInterface;
use OAuth2\RequestInterface;
use OAuth2\ResponseInterface;
use OAuth2\ScopeInterface;
use OAuth2\TokenType\TokenTypeInterface;

/**
 * Class Server
 * @package filsh\yii2\oauth2server
 *
 * @property Module $module
 */
class Server extends \OAuth2\Server
{
    use traits\ClassNamespace;
    
    /**
     * @var Module
     */
    protected $module;
    
    public function __construct(
        Module $module,
        $storage = array(),
        array $config = array(),
        array $grantTypes = array(),
        array $responseTypes = array(),
        TokenTypeInterface $tokenType = null,
        ScopeInterface $scopeUtil = null,
        ClientAssertionTypeInterface $clientAssertionType = null
    ) {
        $this->module = $module;
        parent::__construct(
            $storage,
            $config,
            $grantTypes,
            $responseTypes,
            $tokenType,
            $scopeUtil,
            $clientAssertionType
        );
    }
    
    public function createAccessToken($clientId, $userId, $scope = null, $includeRefreshToken = true)
    {
        $accessToken = $this->getAccessTokenResponseType();

        return $accessToken->createAccessToken($clientId, $userId, $scope, $includeRefreshToken);
    }
    
    public function verifyResourceRequest(
        RequestInterface $request = null,
        ResponseInterface $response = null,
        $scope = null
    ) {
        if ($request === null) {
            $request = $this->module->getRequest();
        }

        parent::verifyResourceRequest($request, $response, $scope);
    }
    
    public function handleTokenRequest(RequestInterface $request = null, ResponseInterface $response = null)
    {
        if ($request === null) {
            $request = $this->module->getRequest();
        }

        return parent::handleTokenRequest($request, $response);
    }
}
