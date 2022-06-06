<?php

declare(strict_types=1);

namespace App\TwigExtension;
use Twig\TwigFilter;

class CustomFilters extends \Twig_Extension {

    public function getFilters() {
        return array(
            new TwigFilter('base64_encode', array($this, 'base64_en')),
            new TwigFilter('my_number_class', array($this, 'my_number_class')),
            new TwigFilter('my_number_class_2colors', array($this, 'my_number_class_2colors')),
            new TwigFilter('my_integer_format', array($this, 'my_integer_format')),
            new TwigFilter('my_quantity_format', array($this, 'my_quantity_format')),
            new TwigFilter('my_decimal_format_1d', array($this, 'my_decimal_format_1d')),
            new TwigFilter('my_decimal_format_2d', array($this, 'my_decimal_format_2d')),
            new TwigFilter('my_percent_format', array($this, 'my_percent_format')),
            new TwigFilter('to_tws_symbol', array($this, 'to_tws_symbol')),
            new TwigFilter('masq', array($this, 'masq')),
        );
    }

    public function base64_en($input) {
       return base64_encode($input);
    }

    public function my_number_class($input) {
        $result = 'text-right';
        if (floatval($input) > 0) {
        } elseif (floatval($input) < 0) {
            $result = $result . ' text-danger';
        } else {
        }
        return $result;
    }

    public function my_number_class_2colors($input) {
        $result = 'text-right';
        if (floatval($input) > 0) {
            $result = $result . ' text-success';
        } elseif (floatval($input) < 0) {
            $result = $result . ' text-danger';
        } else {
        }
        return $result;
    }

    public function my_integer_format($input) {
        return number_format(floatval($input), 0, '.', ' ');
    }

    public function my_quantity_format($input) {
        $number = floatval($input);
        $result =  rtrim(rtrim(number_format($number, 4, '.', ' '), '0'), '.');
        return $result;
    }

    public function my_decimal_format_1d($input) {
        return number_format(floatval($input), 1, '.', ' ');
    }

    public function my_decimal_format_2d($input) {
        $number = floatval($input);
        return $number ? number_format($number, 2, '.', ' ') : '-';
    }

    public function my_percent_format($input) {
        return number_format(floatval($input) * 100, 1, '.', ' ') . '%';
    }

    public function to_tws_symbol($input) {
        $input = str_replace('-PR', '/P', $input);
        $input = str_replace('-B', '.B', $input);
        $input = str_replace(' B', '.B', $input);
        return $input;
    }

    public function masq($input) {
      for ($i = 2; $i < (strlen($input) - 2); $i++){
          $input[$i] = '*';
      }
      return $input;
    }

}
