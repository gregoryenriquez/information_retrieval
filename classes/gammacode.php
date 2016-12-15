<?php

namespace classes;

class GammaCode
{
    public $codes;
    public $dec_codes;
    public $count;

    public function __construct()
    {
        $this->count = 0;
        $this->codes = array();
    }

    public function copy($gc_obj)
    {
        $this->codes = $gc_obj->codes;
        $this->dec_codes = $gc_obj->dec_codes;
        $this->count = $gc_obj->count;
    }

    public function mapCodes($delta_offsets) 
    {
        usort($delta_offsets, "self::cmp");
        for ($i = 0; $i < count($delta_offsets); $i++) {
            self::addCode($delta_offsets[$i]["value"]);
        }
    }

    public function addCode($delta_offset)
    {
        $code = str_repeat("0", strlen(decbin($delta_offset))-1) . decbin($delta_offset);
        $this->codes[$delta_offset] = $code;
        $this->dec_codes[$code] = $delta_offset;
        $this->count++;
        return $this->codes[$delta_offset];
    }

    public function getDeltaOffset($code) 
    {
        return $this->dec_codes[$code];
    }

    public function getCode($delta)
    {
        return $this->codes[$delta];
    }

    public static function bstr2bin($str) 
    {
        return pack("H*", bin2hex($str));
    }

    public static function bin2bstr($bin)
    {
        return hex2bin(unpack("H*", hex2bin($bin))[1]);
    }

    public function cmp($offset1, $offset2)
    {
        if ($offset1["count"] == $offset2["count"]) {
            return 0;
        }
        return ($offset1["count"] > $offset2["count"]) ? -1 : 1;
    }
}