<?php

namespace filsh\yii2\oauth2server;

use \Yii;
use yii\base\InvalidConfigException;
use yii\di\NotInstantiableException;
use yii\helpers\ArrayHelper;
use yii\i18n\PhpMessageSource;

/**
 * For example,
 *
 * ```php
 * 'oauth2' => [
 *     'class' => 'filsh\yii2\oauth2server\Module',
 *     'tokenParamName' => 'accessToken',
 *     'tokenAccessLifetime' => 3600 * 24,
 *     'storageMap' => [
 *         'user_credentials' => 'common\models\User',
 *         'refresh_token' => 'common\models\User',
 *     ],
 *     'grantTypes' => [
 *         'user_credentials' => [
 *             'class' => 'OAuth2\GrantType\UserCredentials',
 *         ],
 *         'refresh_token' => [
 *             'class' => 'OAuth2\GrantType\RefreshToken',
 *             'always_issue_new_refresh_token' => true
 *         ]
 *     ]
 * ]
 * ```
 */
class Module extends \yii\base\Module
{
    const VERSION = '2.0.0';

    /**
     * @var array Model's map
     */
    public $modelMap = [];

    /**
     * @var array Storage's map
     */
    public $storageMap = [];

    /**
     * @var array GrantTypes collection
     */
    public $grantTypes = [];

    /**
     * @var string name of access token parameter
     */
    public $tokenParamName;

    /**
     * @var int type max access lifetime
     */
    public $tokenAccessLifetime;
    /**
     * @var bool whether to use JWT tokens
     */
    public $useJwtToken = false;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->registerTranslations();
    }

    /**
     * Gets Oauth2 Server
     *
     * @return Server
     * @throws \ReflectionException
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    public function getServer()
    {
        if (!$this->has('server')) {
            $storages = [];

            if ($this->useJwtToken) {
                if(!array_key_exists('access_token', $this->storageMap) || !array_key_exists('public_key', $this->storageMap)) {
                        throw new InvalidConfigException('access_token and public_key must be set or set useJwtToken to false');
                }
                //define dependencies when JWT is used instead of normal token
                \Yii::$container->clear('public_key'); //remove old definition
                \Yii::$container->set('public_key', $this->storageMap['public_key']);
                \Yii::$container->set('OAuth2\Storage\PublicKeyInterface', $this->storageMap['public_key']);

                \Yii::$container->clear('access_token'); //remove old definition
                \Yii::$container->set('access_token', $this->storageMap['access_token']);
            }

            foreach (array_keys($this->storageMap) as $name) {
                $storages[$name] = \Yii::$container->get($name);
            }

            $grantTypes = [];
            foreach ($this->grantTypes as $name => $options) {
                if (!isset($storages[$name]) || empty($options['class'])) {
                    throw new InvalidConfigException('Invalid grant types configuration.');
                }

                $class = $options['class'];
                unset($options['class']);

                $reflection = new \ReflectionClass($class);
                $config = array_merge([0 => $storages[$name]], [$options]);

                $instance = $reflection->newInstanceArgs($config);
                $grantTypes[$name] = $instance;
            }

            $server = \Yii::$container->get(Server::className(), [
                $this,
                $storages,
                [
                    'use_jwt_access_tokens' => $this->useJwtToken,
                    'token_param_name' => $this->tokenParamName,
                    'access_lifetime' => $this->tokenAccessLifetime,
                    /** add more ... */
                ],
                $grantTypes
            ]);

            $this->set('server', $server);
        }

        return $this->get('server');
    }

    public function getRequest()
    {
        if (!ArrayHelper::keyExists('request', $this->getComponents())) {
            $this->set('request', Request::createFromGlobals());
        }
        return $this->get('request');
    }

    public function getResponse()
    {
        if (!ArrayHelper::keyExists('request', $this->getComponents())) {
            $this->set('response', new Response());
        }
        return $this->get('response');
    }

    /**
     * Register translations for this module
     *
     * @return void
     * @throws InvalidConfigException
     */
    public function registerTranslations()
    {
        if (!isset(Yii::$app->get('i18n')->translations['modules/oauth2/*'])) {
            Yii::$app->get('i18n')->translations['modules/oauth2/*'] = [
                'class'    => PhpMessageSource::class,
                'basePath' => __DIR__ . '/messages',
            ];
        }
    }

    /**
     * Translate module message
     *
     * @param string $category
     * @param string $message
     * @param array $params
     * @param string $language
     * @return string
     */
    public static function t($category, $message, $params = [], $language = null)
    {
        return Yii::t('modules/oauth2/' . $category, $message, $params, $language);
    }
}
