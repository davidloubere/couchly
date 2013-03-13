<?php
namespace Couchly;

class Client
{
    const
        STATUS_GET_SUCCESS = 200,
        STATUS_POST_SUCCESS = 200,
        STATUS_PUT_SUCCESS = 201,
        STATUS_DELETE_SUCCESS = 200;
    
    private
        $_dbName = null,
        $_host = null,
        $_port = null;

    public function __construct($dbName, $host='localhost', $port=5984)
    {
        $this->_dbName = $dbName;
        $this->_host = $host;
        $this->_port = $port;
    }

    public function get($id=null)
    {
        $doc = null;
        $response = $this->_request($this->_computeUri($id), 'GET');
        if ($response->getStatusCode() === self::STATUS_GET_SUCCESS)
        {
            $doc = json_decode($response->getBody());
        }
        return $doc;
    }

    public function post($doc, $extraUri=null)
    {
        $response = $this->_request($this->_computeUri($extraUri), 'POST', $doc, 'application/json');
        $body = json_decode($response->getBody());
        if ($response->getStatusCode() !== self::STATUS_POST_SUCCESS)
        {
            throw new Couchly_Exception($body->error.': '.$body->reason);
        }
        return $body;
    }
    
    public function put($id, $doc)
    {
        $response = $this->_request($this->_computeUri($id), 'PUT', $doc, 'application/json');
        if ($response->getStatusCode() !== self::STATUS_PUT_SUCCESS)
        {
            $body = json_decode($response->getBody());
            throw new Couchly_Exception($body->error.': '.$body->reason);
        }
    }
    
    public function delete($id, $rev)
    {
        $response = $this->_request($this->_computeUri($id.'?rev='.$rev), 'DELETE');
        if ($response->getStatusCode() !== self::STATUS_DELETE_SUCCESS)
        {
            $body = json_decode($response->getBody());
            throw new Couchly_Exception($body->error.': '.$body->reason);
        }
    }
    
    protected function _request($uri, $method, $data=null, $enctype=null)
    {
        $client = new \Zend\Http\Client();
        $client->setUri($uri);
        if (!is_null($data))
        {
            $client->setEncType($enctype);
            $client->setRawBody(json_encode($data));
        }
        $client->setMethod($method);
        return $client->send();
    }
    
    protected function _computeUri($extraUri=null)
    {
        $uri = 'http://'.$this->_host.':'.$this->_port.'/'.$this->_dbName;
        if (!is_null($extraUri))
        {
            $uri .= '/'.$extraUri;
        }
        return $uri;
    }
}