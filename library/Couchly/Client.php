<?php
/**
 * Couchly_Client
 * 
 * @link    http://github.com/davidloubere/couchly for the canonical source repository
 */
class Couchly_Client
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
        if ($response->getStatus() === self::STATUS_GET_SUCCESS)
        {
            $doc = json_decode($response->getBody());
        }
        return $doc;
    }

    public function post($doc, $extraUri=null)
    {
        $response = $this->_request($this->_computeUri($extraUri), 'POST', $doc, 'text/json');
        $body = json_decode($response->getBody());
        if ($response->getStatus() !== self::STATUS_POST_SUCCESS)
        {
            throw new Couchly_Exception($body->error.': '.$body->reason);
        }
        return $body;
    }
    
    public function put($id, $doc)
    {
        $response = $this->_request($this->_computeUri($id), 'PUT', $doc, 'text/json');
        if ($response->getStatus() !== self::STATUS_PUT_SUCCESS)
        {
            $body = json_decode($response->getBody());
            throw new Couchly_Exception($body->error.': '.$body->reason);
        }
    }
    
    public function delete($id, $rev)
    {
        $response = $this->_request($this->_computeUri($id.'?rev='.$rev), 'DELETE');
        if ($response->getStatus() !== self::STATUS_DELETE_SUCCESS)
        {
            $body = json_decode($response->getBody());
            throw new Couchly_Exception($body->error.': '.$body->reason);
        }
    }
    
    protected function _request($uri, $method, $data=null, $enctype=null)
    {
        $client = new Zend_Http_Client();
        $client->setUri($uri);
        if (!is_null($data))
        {
            $client->setRawData(json_encode($data), $enctype);
        }
        return $client->request($method);
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