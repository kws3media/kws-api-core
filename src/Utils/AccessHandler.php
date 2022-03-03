<?php

namespace Kws3\ApiCore\Utils;

use \Kws3\ApiCore\Exceptions\HTTPException;
use \Kws3\ApiCore\Loader;

class AccessHandler extends \Prefab
{
    public static function isAllowed($currentAction, $accessList)
    {
        $identity = Loader::getIdentity();

        $found = false;
        $authenticated = !empty($identity->user);
        $inactiveKey = $identity->inactiveKey;
        $expiredKey = $identity->expiredKey;

        foreach ($accessList as $k => $v) {
            if ($k === $currentAction) {
                $found = true;

                if ($v === true) {
                    return true;
                }

                if ($expiredKey) {
                    self::expiredKey();

                    return false;
                }

                if ($inactiveKey) {
                    self::inactiveKey();

                    return false;
                }

                if (is_array($v)) {
                    if (!empty($identity->context) && in_array($identity->context, $v, true)) {
                        return true;
                    }
                } else {
                    if (!empty($identity->context) && ($identity->context === $v)) {
                        return true;
                    }
                }
            }
        }

        if ($expiredKey) {
            self::expiredKey();

            return false;
        }

        if ($inactiveKey) {
            self::inactiveKey();

            return false;
        }

        //if current action has no access list, only allow authenticated users
        return !$found && $authenticated;
    }

    public static function expiredKey()
    {
        throw new HTTPException(
            'Expired',
            419,
            [
                'dev' => 'Session Expired',
                'internalCode' => '',
                'more' => 'Will need to renew session either with a refresh token, or logging in again'
            ]
        );
    }

    public static function inactiveKey()
    {
        throw new HTTPException(
            'Conflict.',
            409,
            [
                'dev' => 'Logged in on another device',
                'internalCode' => '',
                'more' => 'Multiple logins from same account are not permitted'
            ]
        );
    }
}
