<?php

namespace app\components;

use Yii;
use yii\filters\auth\HttpBearerAuth;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use UnexpectedValueException;
use app\helpers\Constants;

class JwtBearerAuth extends HttpBearerAuth
{
    public $role;

    public function authenticate($user, $request, $response)
    {
        $appRoles = Constants::appRoles();
        $authHeader = $request->getHeaders()->get('Authorization');

        if (!$authHeader) {
            return null;
        }

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            return null;
        }

        try {
            $payload = JWT::decode($token, new Key(Yii::$app->params['jwt']['key'], 'HS256'));
            $payload = json_decode(json_encode($payload), true);
            if ($payload['exp'] < time()) {
                return null;
            }
        } catch (LogicException $e) {
            return null;
        } catch (UnexpectedValueException $e) {
            return null;
        }

        Yii::$app->session->set('username', $payload['user']['username']);
        Yii::$app->session->set('userid', $payload['user']['id']);
        Yii::$app->session->set('dept', $payload['user']['dept']);

        if (isset($payload['user']['roles'])) {
            $roles = $payload['user']['roles'];

            if (!array_intersect($appRoles, array_keys($roles))) {
                return null;
            }

            if (!in_array($this->role, array_keys($roles))) {
                return null;
            }

            Yii::$app->session->set('role', $roles);
        } else {
            Yii::$app->session->set('role', null);
        }

        // if(!isset($payload['user']['roles'][Yii::$app->params['codeApp']])) {
        //     Yii::$app->session->set('role', null);
        // } else {
        //     Yii::$app->session->set('role', $payload['user']['roles'][Yii::$app->params['codeApp']]);
        // }
        
        return $payload;
    }

    public function challenge($response)
    {
        $response->getHeaders()->set('WWW-Authenticate', 'Bearer');
        $response->setStatusCode(401);
    }
}