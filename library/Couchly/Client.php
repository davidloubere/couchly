<?php
/**
 * TODO:
 * - request()
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
        $ret = null;

        $client = new Zend_Http_Client();
        $response = $client->setUri($this->_computeUri($id))
            ->request('GET');
        
        if ($response->getStatus() === self::STATUS_GET_SUCCESS)
        {
            $ret = json_decode($response->getBody());
        }

        return $ret;
    }

    public function post($doc, $view=null)
    {
        $client = new Zend_Http_Client();
        $response = $client->setUri($this->_computeUri($view))
            ->setRawData(json_encode($doc), 'application/json')
            ->request('POST');
        $body = json_decode($response->getBody());
        if ($response->getStatus() !== self::STATUS_POST_SUCCESS)
        {
            throw new Couchly_Exception($body->error.': '.$body->reason);
        }
        return $body;
    }
    
    public function put($id, $doc)
    {
        $client = new Zend_Http_Client();
        $response = $client->setUri($this->_computeUri($id))
            ->setRawData(json_encode($doc), 'text/json')
            ->request('PUT');
        if ($response->getStatus() !== self::STATUS_PUT_SUCCESS)
        {
            $body = json_decode($response->getBody());
            throw new Couchly_Exception($body->error.': '.$body->reason);
        }
    }
    
    public function delete($id, $rev)
    {
        $client = new Zend_Http_Client();
        $response = $client->setUri($this->_computeUri($id.'?rev='.$rev))
            ->request('DELETE');
        if ($response->getStatus() !== self::STATUS_DELETE_SUCCESS)
        {
            $body = json_decode($response->getBody());
            throw new Couchly_Exception($body->error.': '.$body->reason);
        }
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