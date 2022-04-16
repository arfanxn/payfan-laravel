<?php

namespace App\Helpers;

use Illuminate\Support\Str;

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

    public static function random(
        int $len = 16,
        string $chars = "aAbBcCdDeEfFgGhHiIjJkKlLmMnNoOpPqQrRsStTuUvVwWxXyYzZ0123456789",
        bool $returnInstance = false
    ) {
        $chars = str_split(self::make(str_shuffle($chars))->removeWhitespace()->result());
        $randStr = "";
        for ($i = 1; $i <= $len; $i++) {
            $charIndex =  mt_rand(0, count($chars) - 1);
            $randStr .= random_int(0, 2147483646) % 2  == 0 ?
                strtoupper($chars[$charIndex]) : strtolower($chars[$charIndex]);
        };

        return !$returnInstance ? $randStr : self::make($randStr);
    }

    public function get()
    {
        return $this->string;
    }

    public function result()
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

    public  function toUpperCase()
    {
        $this->string = strtoupper($this->string);
        return $this;
    }

    public function limit(int  $limit = 20)
    {
        $this->string = substr($this->string, 0,  $limit);
        return $this;
    }

    public function toUSD($currencySymbol = false)
    {
        $amount =  is_string($this->string) ?  floatval($this->string) : $this->string;
        $formatedAmount = number_format($amount, 2, ".", ",");
        $this->string =  $currencySymbol ? "$" . $formatedAmount  : $formatedAmount;

        return $this;
    }
}
