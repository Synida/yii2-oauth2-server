<?php

namespace filsh\yii2\oauth2server\filters;

use filsh\yii2\oauth2server\Module;
use OAuth2\Response;
use Yii;
use yii\base\ActionEvent;
use yii\base\Behavior;
use yii\base\Controller;
use filsh\yii2\oauth2server\exceptions\HttpException;
use yii\base\InvalidConfigException;
use yii\di\NotInstantiableException;

/**
 * Class ErrorToExceptionFilter
 * @package filsh\yii2\oauth2server\filters
 */
class ErrorToExceptionFilter extends Behavior
{
    /**
     * @inheritdoc
     */
    public function events()
    {
        return [Controller::EVENT_AFTER_ACTION => 'afterAction'];
    }

    /**
     * @param ActionEvent $event
     * @return boolean
     * @throws HttpException when the request method is not allowed.
     * @throws InvalidConfigException when the server is not configured properly
     * @throws \ReflectionException
     * @throws NotInstantiableException
     */
    public function afterAction($event)
    {
        $server = Yii::$app->getModule('oauth2');
        if (!$server instanceof Module) {
            throw new InvalidConfigException('Invalid oauth2 configuration.');
        }

        $response = $server->getServer()->getResponse();

        $optional = $event->action->controller->getBehavior('authenticator')->optional;
        $currentAction = $event->action->id;
        $isValid = true;

        if (!in_array($currentAction, $optional, false)) {
            if ($response !== null) {
                $isValid = $response->isInformational() || $response->isSuccessful() || $response->isRedirection();
            }

            if (!$isValid) {
                throw new HttpException(
                    $response->getStatusCode(),
                    $this->getErrorMessage($response),
                    $response->getParameter('error_uri')
                );
            }
        }

        return true;
    }

    /**
     * @param Response $response
     * @return mixed
     */
    protected function getErrorMessage(Response $response)
    {
        return $response->getParameter('error');
    }
}
