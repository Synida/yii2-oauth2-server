<?php

namespace filsh\yii2\oauth2server\filters;

use OAuth2\Response;
use Yii;
use yii\base\Controller;
use filsh\yii2\oauth2server\Module;
use filsh\yii2\oauth2server\exceptions\HttpException;

/**
 * Class ErrorToExceptionFilter
 * @package filsh\yii2\oauth2server\filters
 */
class ErrorToExceptionFilter extends \yii\base\Behavior
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
     */
    public function afterAction($event)
    {
        $response = Yii::$app->getModule('oauth2')->getServer()->getResponse();
        $optional = $event->action->controller->getBehavior('authenticator')->optional;
        $currentAction = $event->action->id;
        $isValid = true;
        if (!in_array($currentAction, $optional)) {
            if ($response !== null) {
                $isValid = $response->isInformational() || $response->isSuccessful() || $response->isRedirection();
            }
            if (!$isValid) {
                throw new HttpException($response->getStatusCode(), $this->getErrorMessage($response),
                    $response->getParameter('error_uri'));
            }
        }
        return $isValid;
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
