<?php

namespace MogileFs;

class Exception extends \RuntimeException
{
    protected $response;

    /**
     * @return Response
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public static function error(Response $response)
    {
        $data = $response->getData();
        $e = new Exception(trim(http_build_query($data), '='));
        $e->setResponse($response);
        return $e;
    }

    /**
     * @param mixed $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }
}
