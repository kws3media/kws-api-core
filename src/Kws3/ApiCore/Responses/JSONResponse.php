<?php
namespace Kws3\ApiCore\Responses;

class JSONResponse extends Response
{
    protected $snake = true;
    protected $envelope = true;

    public function __construct()
    {
        parent::__construct();
    }

    public function send($records, $error = false)
    {

        $success = $error == true ? parent::ERROR : parent::SUCCESS;

        $metadata = $this->app->get('METADATAPROVIDER');
        $metadata->setStatus($success);
        $metadata->setCount(count($records));

        // Convert to snake_case if the flag is set
        if (!$this->snake) {
            $records = $this->arrayKeysToCamel($records);
        }

        $etag = md5(serialize($records));
        $this->sendHeader('E-Tag: '.$etag);

        $this->sendHeader('Content-Type: application/json; '. 'charset='.$this->app->get('ENCODING'));

        if(!$error && $this->app->get('VERB') == 'POST'){
            $this->sendHeader("HTTP/1.0 201 Created");
        }

        $message = array();
        $message['_meta'] = $metadata->getArray();

        // Handle 0 record responses, or assign the records
        if ($metadata->getCount() === 0) {
            // This is required to make the response JSON return an empty JS object.
            // Without this, the JSON return an empty array: [] instead of {}
            $message['records'] = new \stdClass();
        } else {
            $message['records'] = $records;
        }

        if (!$this->head) {
            $response = json_encode($message);
            if (!defined('AUTOMATED_TESTING')) {

                echo $response;

                if(K_ENV == K_ENV_LOCAL){
                    if($error){
                        dbg()->error($message['records']);
                    }else{
                        $c = dbg()->userData("Response")->title("Response");
                        $c->table("Response Object", [
                            [ 'Key' => '_meta', 'Data' => $message['_meta']],
                            [ 'Key' => 'records', 'Data' => $message['records']],
                        ]);
                    }
                }
            } else {
                $this->app->set('APP_RESPONSE', $response);
            }
        }

        dbg()->requestProcessed();
        return true;

    }

    public function convertSnakeCase($snake)
    {
        $this->snake = (bool) $snake;

        return $this;
    }

    public function useEnvelope($envelope)
    {
        $this->envelope = (bool) $envelope;

        return $this;
    }
}
