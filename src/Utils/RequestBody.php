<?php
namespace ApiCore\Utils;

use ApiCore\Exceptions\BaseHTTPException as HTTPException;

class RequestBody extends \Prefab
{
    protected $app    = null;
    protected $request_type = null;
    protected $body_content_type = null;
    protected $raw_body = null;
    protected $parsed_body = null;

    public function __construct()
    {
        $this->app     = \Base::instance();

    }

    public function parse()
    {
        $this->request_type      = $this->app->get('VERB');
        $this->raw_body          = $this->app->get('BODY');
        $this->post_body         = $this->app->get('POST');

        $headers = $this->app->get('HEADERS');
        $this->body_content_type = '';
        if (is_array($headers) && isset($headers['Content-Type'])) {
            $this->body_content_type = $this->parseContentType($headers['Content-Type']);
        }

        // Only attempt to parse raw request body on POST and PUT
        if ($this->request_type == "POST" || $this->request_type == "PUT") {
            // Support for:
            // - JSON
            // - application/x-www-form-urlencoded
            // - multipart/form-data
            switch ($this->body_content_type) {
                case 'application/json':
                    $this->parsed_body = json_decode($this->raw_body, true);
                    break;
                default:
                    $this->parsed_body = $this->post_body;
                    // This condition will catch where proper PUT is used instead of emulated PUT via POST
                    if (empty($this->parsed_body) && $this->request_type == "PUT") {
                        if (strpos($this->body_content_type, 'application/x-www-form-urlencoded') !== false) {
                            parse_str($this->raw_body, $this->parsed_body);
                        } elseif (strpos($this->body_content_type, 'multipart/form-data') !== false) {
                            $this->parsed_body = $this->parseFormData();
                        }
                    }
                    break;
            }

            if (!is_array($this->parsed_body)) {
                throw new HTTPException(
                    'Bad Request.',
                    400,
                    array(
                        'dev' => 'The request body sent to the server could not be parsed.',
                        'internalCode' => '',
                        'more' => $this->getJsonError()
                    )
                );
            }
        }

        if(K_ENV == K_ENV_LOCAL){
            $c = dbg()->userData("RequestBody")->title("Payload");
            $c->table("Payload", [
                ['Data' => $this->parsed_body]
            ]);
        }

        return $this->parsed_body;
    }

    protected function parseContentType($type)
    {
        $t = explode(';', $type);
        $t = strtolower(trim($t[0]));
        $t_parts = explode('/', $t);
        $str = $t_parts[0] . '/';
        if($t_parts[1]){
            if(Tools::contains($t_parts[1], '+')){
                if(Tools::contains($t_parts[1], 'json')){
                    $str .= 'json';
                }else{
                    $str .= $t_parts[1];
                }
            }else{
                $str .= $t_parts[1];
            }
        }

        return $str;
    }

    protected function parseFormData()
    {
        $data = array();

        // Fetch each part
        $boundary = substr($this->raw_body, 0, strpos($this->raw_body, "\r\n"));
        $parts = array_slice(explode($boundary, $this->raw_body), 1);

        foreach ($parts as $part) {
            // If this is the last part, break
            if ($part == "--\r\n")
                break;

            // Separate content from headers
            $part = ltrim($part, "\r\n");
            list($raw_headers, $body) = explode("\r\n\r\n", $part, 2);

            // Parse the headers list
            $raw_headers = explode("\r\n", $raw_headers);
            $headers = array();
            foreach ($raw_headers as $header) {
                list($name, $value) = explode(':', $header, 2);
                $headers[strtolower($name)] = ltrim($value, ' ');
            }

            // Parse the Content-Disposition to get the field name, etc.
            if (isset($headers['content-disposition'])) {
                $filename = null;
                preg_match(
                    '/^(.+); *name="([^"]+)"(; *filename="([^"]+)")?/',
                    $headers['content-disposition'],
                    $matches
                );
                list(, $type, $name) = $matches;
                // isset($matches[4]) and $filename = $matches[4];
                // Handle your fields here
                switch ($name) {
                    // this is a file upload
                    // case 'userfile':
                    //      file_put_contents($filename, $body);
                    //      break;
                    // default for all other files is to populate $data
                    default:
                         $data[$name] = substr($body, 0, strlen($body) - 2);
                         break;
                }
            }
        }

        return $data;
    }

    protected function getJsonError()
    {
        $json_error = "";

        switch (json_last_error()) {
            case JSON_ERROR_DEPTH:
                $json_error = 'JSON - Maximum stack depth exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $json_error = 'JSON - Underflow or the modes mismatch';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $json_error = 'JSON - Unexpected control character found';
                break;
            case JSON_ERROR_SYNTAX:
                $json_error = 'JSON - Syntax error';
                break;
            case JSON_ERROR_UTF8:
                $json_error = 'JSON - Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
            default:
                $json_error = 'JSON - Unknown error';
                break;
        }

        return $json_error;
    }
}