<?php
namespace wu\utils;

class Converter
{
    public static function toPhp(array $js, $cr = "\n", $level = 0)
    {
        $php = '';
        $gap = '  ';
        $off = str_repeat($gap, $level);

        foreach ($js as $key => $value) {
            $type = gettype($value);
            if ($type === 'array') {
                $childs = self::toPhp($value, $cr, $level + 1);
                if ($childs !== '') {
                    $php .= $off . '"' . $key . '"=>[' . $cr . $childs . $off . '],' . $cr;
                }
            } else {
                $php .= $off . '"' . $key . '"=>' . self::typing($value) . ',' . $cr;
            }

        }
        return $php;
    }

    private static function typing($value)
    {
        if (is_numeric($value)) {
            return $value;
        }

        if ($value === 'true' || $value === 'false') {
            return $value;
        }

        return '"' . $value . '"';
    }

}
