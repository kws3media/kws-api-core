<?php
namespace Kws3\ApiCore\Utils;

use Kws3\ApiCore\Loader;

class Service extends \Prefab
{
    public static function call($method = '', $params = [], $async = true)
    {
        $app = \Base::instance();
        $wd = realpath(Loader::get('SERVICE_PATH'));
        $ds = DIRECTORY_SEPARATOR;

        $command = 'php ' . $wd . $ds . 'index.php' . ' /cli/' . $method;
        if (is_string($params) || is_numeric($params)) {
            $command .= ' ' . $params;
        } elseif(is_array($params)) {
            $command .= ' ' . implode(' ', $params);
        }

        if ($async) {
            $command .= ' > /dev/null 2>/dev/null &';
        }

        $output = [];

        exec($command, $output);

        if(K_ENV == K_ENV_LOCAL){
            $c = dbg()->userData("Commands")->title("Commands");
            $_command = str_replace([
                'start /b ',
                $wd . DIRECTORY_SEPARATOR,
                ' > /dev/null 2>/dev/null &'
            ], '', $command);
            $c->table($method, [
                [ 'Command' => $_command, 'Params' => $params, 'Async' => $async],
            ]);
        }

        return $output;
    }
}
