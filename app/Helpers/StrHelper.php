<?php

namespace App\Helpers;

class StrHelper
{
    protected $string;

    private function __construct(string $string)
    {
        $this->string = $string;
        return $this;
    }

    public static function make(string $string): StrHelper
    {
        return new static(string: $string);
    }

    public function get()
    {
        return $this->string;
    }

    public function removeWhitespace()
    {
        $this->string = preg_replace('/\s+/', '', $this->string);
        return $this;
    }

    public  function toLowerCase()
    {
        $this->string = strtolower($this->string);
        return $this;
    }

    public function limit(int  $limit = 20)
    {
        $this->string = substr($this->string, 0,  $limit);
        return $this;
    }
}
