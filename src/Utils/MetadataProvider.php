<?php

namespace Kws3\ApiCore\Utils;

use \Kws3\ApiCore\Loader;

class MetadataProvider extends \Prefab
{
    protected $offset = 0;
    protected $limit  = 20;
    protected $status = null;
    protected $count  = null;
    protected $total  = null;
    protected $links  = null;

    protected $customFields = [];

    public function __construct()
    {

        $GETS = Loader::get('GET');

        $limit = isset($GETS['limit']) ? intval($GETS['limit']) : $this->limit;
        $offset = isset($GETS['offset']) ? intval($GETS['offset']) : $this->offset;
        $this->setLimit($limit);
        $this->setOffset($offset);

        $this->links  = array();
    }

    /**
     * Attributes Getters
     */
    public function getStatus()
    {
        return $this->status;
    }

    public function getOffset()
    {
        return $this->offset;
    }

    public function getCount()
    {
        return $this->count;
    }

    public function getTotal()
    {
        return $this->total;
    }

    public function getArray()
    {
        $array = array();

        if (isset($this->status)) {
            $array["status"] = $this->status;
        }
        if (isset($this->offset)) {
            $array["offset"] = $this->offset;
        }
        if (isset($this->count)) {
            $array["count"] = $this->count;
            $array["limit"] = $this->limit;
        }
        if (isset($this->total)) {
            $array["total"] = $this->total;
        }

        $this->links = array_merge($this->links, $this->getDefaultLinks());
        if (!empty($this->links)) {
            $array["links"] = $this->links;
        }

        foreach ($this->customFields as $key => $value) {
            $array[$key] = $value;
        }

        return $array;
    }

    public function setCustomField($key, $val)
    {
        if (!in_array($key, ['status', 'offset', 'count', 'limit', 'total', 'links'])) {
            $this->customFields[$key] = $val;
            return true;
        }
        return false;
    }

    /**
     * Attributes Setters
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function setTotal($total)
    {
        $this->total = intval($total);
    }

    public function setCount($count)
    {
        $this->count = intval($count);
    }

    public function setOffset($offset)
    {
        $this->offset = intval($offset);
        if ($this->offset < 0) {
            $this->offset = 0;
        }
    }

    public function setLimit($limit)
    {
        $currentLimit = $this->limit;
        $this->limit = intval($limit);
        if ($this->limit < 1) {
            $this->limit = $currentLimit;
        }
    }

    public function getDefaultLinks()
    {
        $links = array();

        // $total and $count come from setters
        // $offset and $limit come from request query parameters
        if (isset($this->total) && $this->total > 1 && isset($this->count) &&
            isset($this->offset) && isset($this->limit)) {
            // Get params to build the links
            $params = $_GET;
            unset($params['_url'], $params['offset'], $params['limit']);

            // Get base link (without offset nor limit)
            $_uri = explode('?', Loader::get('PARAMS')[0]);
            $baseLink = $_uri[0];
            if (!empty($params)) {
                $baseLink .= '?' . urldecode(http_build_query($params)) . '&';
            } else {
                $baseLink .= '?';
            }

            // Get link pages info
            $page       = intval(floor($this->offset / $this->limit));
            $totalPages = intval(ceil($this->total / $this->limit));

            if ($page > 0) {
                // First page link
                $links['first'] = array(
                    'method' => 'GET',
                    'uri'    => $baseLink . 'offset=0&limit=' . $this->limit
                );

                // Previous page link
                $offset = ($page - 1) * $this->limit;
                $links['prev'] = array(
                    'method' => 'GET',
                    'uri'    => $baseLink . 'offset=' . $offset . '&limit=' . $this->limit
                );
            }

            if ($page < $totalPages - 1) {
                // Next page link
                $offset = ($page + 1) * $this->limit;
                $links['next'] = array(
                    'method' => 'GET',
                    'uri'    => $baseLink . 'offset=' . $offset . '&limit=' . $this->limit
                );

                // Last page link
                $offset = ($totalPages - 1) * $this->limit;
                $links['last'] = array(
                    'method' => 'GET',
                    'uri'    => $baseLink . 'offset=' . $offset . '&limit=' . $this->limit
                );
            }

        }

        return $links;
    }

    /**
     * Adds a new HATEOAS link to the datasource
     *
     * @param string $name     Link name
     * @param string $method   HTTP verb (GET,POST,etc.)
     * @param string $endpoint Request (sub)resouce path
     * @param array  $params   Query params
     */
    public function addLink($name, $method, $endpoint, $params = array())
    {
        $uriParts = explode('/', $_GET['_url']);
        $version = $uriParts[1];
        $uri = '/' . $version . '/' . $endpoint;
        $query = urldecode(http_build_query($params));
        if (!empty($query)) {
            $uri .= '?' . $query;
        }
        $this->links[$name] = array(
            'method' => $method,
            'uri'    => $uri,
        );
    }

}
