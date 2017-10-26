<?php

namespace MogileFs;

class Response
{
    private $raw;
    private $status;
    private $data;

    public function __construct(string $response)
    {
        $this->raw = $response;
        $this->parse();
    }

    private function parse()
    {
        $words = explode(' ', $this->raw);
        $this->status = $words[0];
        if (isset($words[1])) {
            parse_str(trim($words[1]), $result);
            $this->data = $result;
        }
    }

    public function getRaw()
    {
        return $this->raw;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function isSuccess()
    {
        return $this->status === Connection::SUCCESS;
    }

    public function isError()
    {
        return $this->status === Connection::ERROR;
    }
}
