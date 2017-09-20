<?php

namespace Amcms\Quad;

class Filters {

    private $encoding = 'UTF-8';

    private $filters = [
        'filterEquals'              => ['is', 'eq', 'equals', 'equalto', 'isequal', 'isequalto'],
        'filterNot'                 => ['ne', 'neq', 'not', 'isnot', 'isnt', 'notequals', 'notequalto'],
        'filterGreaterThan'         => ['gt', 'isgt', 'greaterthan', 'isgreaterthan'],
        'filterGreaterThanOrEquals' => ['gte', 'isgte', 'ge', 'eg', 'equalorgreaterthan', 'greaterthanorequalto'],
        'filterLowerThan'           => ['lt', 'islt', 'lessthan', 'lowerthan', 'islessthan', 'islowerthan'],
        'filterLowerThanOrEquals'   => ['lte', 'islte', 'le', 'el', 'lessthanorequalto', 'equaltoorlessthan'],
        'filterContains'            => ['contains'],
        'filterContainsNot'         => ['containsnot'],
        'filterIn'                  => ['in', 'inarray', 'in_array'],
        'filterNotIn'               => ['notin', '!in', '!inarray', 'notinarray', '!in_array'],
        'filterEmpty'               => ['empty'],
        'filterNotEmpty'            => ['!empty', 'notempty'],

        'modifierAbs'               => ['abs'],
        'modifierAdd'               => ['add', 'incr', 'increment', 'plus'],
        'modifierAppend'            => ['after', 'append'],
        'modifierCapitalize'        => ['capitalize', 'cap'],
        'modifierCeil'              => ['ceil'],
        'modifierDateFormat'        => ['dateformat', 'date_format', 'dateFormat', 'formatDate', 'format_date', 'date'],
        'modifierDivide'            => ['div', 'divide'],
        'modifierEllipsis'          => ['ellipsis'],
        'modifierEscape'            => ['e', 'esc', 'escape'],
        'modifierFloor'             => ['floor'],
        'modifierHtmlDecode'        => ['html_decode', 'decode_html', 'html_entity_decode'],
        'modifierHtmlEntities'      => ['htmlent', 'htmlentities'],
        'modifierHtmlSpecialChars'  => ['htmlspecial', 'htmlspecchars', 'htmlspecialchars', 'hsc'],
        'modifierLimit'             => ['limit'],
        'modifierLowerCase'         => ['lcase', 'lowercase', 'strtolower', 'tolower', 'lower'],
        'modifierLTrim'             => ['ltrim'],
        'modifierModulus'           => ['mod', 'modulus'],
        'modifierMoneyFormat'       => ['moneyformat', 'money_format'],
        'modifierMultiply'          => ['mpe', 'multiply'],
        'modifierNl2Br'             => ['nl2br'],
        'modifierNumberFormat'      => ['numberformat', 'number_format', 'numberFormat'],
        'modifierPrepend'           => ['before', 'prepend'],
        'modifierReplace'           => ['replace'],
        'modifierReverseString'     => ['reverse', 'strrev'],
        'modifierRound'             => ['round'],
        'modifierRTrim'             => ['rtrim'],
        'modifierSpamProtect'       => ['spam_protect', 'spamprotect'],
        'modifierStrip'             => ['strip'],
        'modifierStripString'       => ['stripString', 'stripstring', 'stripstr', 'strip_string', 'strip_str'],
        'modifierStripTags'         => ['striptags', 'stripTags', 'notags', 'strip_tags'],
        'modifierStrToTime'         => ['strtotime', 'totime'],
        'modifierSubtract'          => ['subtract', 'decr', 'decrement', 'minus'],
        'modifierTrim'              => ['trim'],
        'modifierUcfirst'           => ['ucfirst'],
        'modifierUcwords'           => ['ucwords'],
        'modifierUpperCase'         => ['ucase', 'uppercase', 'strtoupper', 'toupper', 'upper'],
        'modifierUrlDecode'         => ['urldecode', 'url_decode', 'decode_url'],
        'modifierUrlEncode'         => ['urlencode', 'url_encode', 'encode_url'],
        'modifierWordWrap'          => ['wordwrap'],
        'modifierWordWrapCut'       => ['wordwrapcut'],

        'getterThen'                => ['then'],
        'getterElse'                => ['else'],
        'getterIfEmpty'             => ['default', 'ifempty', 'isempty'],
        'getterIfNotEmpty'          => ['ifnotempty', 'isnotempty'],
        'getterLength'              => ['length', 'len', 'strlen'],
        'getterFirst'               => ['first'],
        'getterLast'                => ['last'],
    ];

    public function __construct($api) {
        foreach ($this->filters as $method => $names) {
            foreach ($names as $name) {
                $api->registerFilter($name, [$this, $method]);
            }
        }
    }

    public function setEncoding($encoding) {
        $this->encoding = $encoding;
    }

    public function filterEquals($input, $parameter) {
        return ($input == $parameter);
    }

    public function filterNot($input, $parameter) {
        return ($input != $parameter);
    }

    public function filterGreaterThan($input, $parameter) {
        return ($input > $parameter);
    }

    public function filterGreaterThanOrEquals($input, $parameter) {
        return ($input >= $parameter);
    }

    public function filterLowerThan($input, $parameter) {
        return ($input < $parameter);
    }

    public function filterLowerThanOrEquals($input, $parameter) {
        return ($input <= $parameter);
    }

    public function filterContains($input, $parameter) {
        return (stripos($input, $parameter) !== false);
    }

    public function filterContainsNot($input, $parameter) {
        return (stripos($input, $parameter) === false);
    }

    public function filterIn($input, $parameter) {
        if (!is_array($parameter)) {
            $parameter = explode(',', $parameter);
        }

        return (in_array($input, $parameter));
    }

    public function filterNotIn($input, $parameter) {
        if (!is_array($parameter)) {
            $parameter = explode(',', $parameter);
        }

        return (!in_array($input, $parameter));
    }

    public function filterEmpty($input) {
        return empty($input);
    }

    public function filterNotEmpty($input) {
        return !empty($input);
    }

    public function modifierAppend($input, $parameter) {
        if (is_scalar($input) && is_scalar($parameter)) {
            $input .= $parameter;
        }

        return $input;
    }

    public function modifierPrepend($input, $parameter) {
        if (is_scalar($input) && is_scalar($parameter)) {
            $input = $parameter . $input;
        }

        return $input;
    }

    public function modifierLowerCase($input) {
        return mb_strtolower($input, $this->encoding);
    }

    public function modifierUpperCase($input) {
        return mb_strtoupper($input, $this->encoding);
    }

    public function modifierUcwords($input) {
        return mb_convert_case($input, MB_CASE_TITLE, $this->encoding);
    }

    public function modifierUcfirst($input) {
        return mb_strtoupper(mb_substr($input, 0, 1)) . mb_substr($input, 1);
    }

    public function modifierHtmlEntities($input, $parameter) {
        if (is_scalar($input)) {
            return htmlentities($input, ENT_QUOTES, $this->encoding, ($parameter == 'true'));
        }

        return $input;
    }

    public function modifierHtmlSpecialChars($input) {
        if (is_scalar($input)) {
            return htmlspecialchars($input, ENT_QUOTES, $this->encoding);
        }
    }

    public function modifierEscape($input) {
        if (is_scalar($input)) {
            $input = htmlspecialchars($input, ENT_QUOTES, $this->encoding, false);
        }

        return $input;
    }

    public function modifierStrip($input) {
        return preg_replace('/\s+/', ' ', $input);
    }

    public function modifierStripString($input, $parameter) {
        return str_replace($parameter, '', $input);
    }

    public function modifierReplace($input, $parameters) {
        if (!is_array($parameters)) {
            $parameters = explode('==', $parameters);
        }

        if (count($parameters) == 2) {
            $input = str_replace($parameters[0], $parameters[1], $input);
        }

        return $input;
    }

    public function modifierStripTags($input, $parameter) {
        if (is_scalar($input)) {
            if (!empty($parameter)) {
                $input = strip_tags($input, $parameter);
            } else {
                $input = strip_tags($input);
            }
        }

        return $input;
    }

    public function modifierReverseString($input) {
        if (is_scalar($input)) {
            preg_match_all('/./us', $input, $symbols);
            return join('', array_reverse($symbols[0]));
        }

        return $input;
    }

    public function modifierWordWrap($input, $parameter) {
        $at = $parameter > 0 ? intval($parameter) : 70;
        return wordwrap($input, $at, "<br />\n", 0);
    }

    public function modifierWordWrapCut($input, $parameter) {
        $at = $parameter > 0 ? intval($parameter) : 70;
        return wordwrap($input, $at, "<br />\n", 1);
    }

    public function modifierLimit($input, $parameter) {
        $limit = $parameter > 0 ? intval($parameter) : 100;
        $str = html_entity_decode($input, ENT_COMPAT, $this->encoding);
        return mb_substr($str, 0, $limit);
    }

    public function modifierEllipsis($input, $parameter) {
        $limit  = $parameter > 0 ? intval($parameter) : 100;
        $output = html_entity_decode($input, ENT_COMPAT, $this->encoding);
        $length = mb_strlen($output, $this->encoding);

        if ($limit > $length) {
            return $input;
        }

        $breakpoint = mb_strpos($output, ' ', $limit, $this->encoding);

        if ($breakpoint !== false) {
            if ($breakpoint < $length - 1) {
                return mb_substr($output, 0, $breakpoint, $this->encoding) . '...';
            }
        }

        return $input;
    }

    public function modifierNl2Br($input) {
        return nl2br($input);
    }

    public function modifierStrToTime($input) {
        if (is_string($input)) {
            return strtotime($input);
        }

        return $input;
    }

    public function modifierAdd($input, $parameter) {
        if ($parameter === null) {
            $parameter = 1;
        }

        if (is_numeric($input)) {
            $input += $parameter;
        }

        return $input;
    }

    public function modifierSubtract($input, $parameter) {
        if ($parameter === null) {
            $parameter = 1;
        }

        if (is_numeric($input)) {
            $input -= $parameter;
        }

        return $input;
    }

    public function modifierMultiply($input, $parameter) {
        if (is_numeric($input)) {
            $input *= $parameter;
        }

        return $input;
    }

    public function modifierDivide($input, $parameter) {
        if (is_numeric($input)) {
            $input /= $parameter;
        }

        return $input;
    }

    public function modifierModulus($input, $parameter) {
        if ($parameter === null) {
            $parameter = 2;
        }

        if (is_numeric($parameter)) {
            $input %= $parameter;
        }

        return $input;
    }

    public function modifierUrlEncode($input) {
        return urlencode($input);
    }

    public function modifierUrlDecode($input) {
        return urldecode($input);
    }

    public function modifierCapitalize($input) {
        return mb_strtoupper(mb_substr($input, 0, 1)) . mb_strtolower(mb_substr($input, 1, null));
    }

    public function modifierTrim($input, $parameter) {
        if (is_string($input)) {
            if ($parameter !== null) {
                return trim($input, $parameter);
            } else {
                return trim($input);
            }
        }

        return $input;
    }

    public function modifierDateFormat($input, $parameter) {
        if (is_numeric($input) && $parameter !== null) {
            $input = date($input, $parameter);
        }

        return $input;
    }

    public function modifierNumberFormat($input, $parameter) {
        if (is_number($input)) {
            $p = explode('||', $parameter);

            if (count($p) == 3) {
                return number_format($input, $p[0], $p[1], $p[2]);
            } else {
                return number_format($input, $p[0]);
            }
        }

        return $input;
    }

    public function modifierRound($input) {
        return round($input);
    }

    public function modifierFloor($input) {
        return floor($input);
    }

    public function modifierCeil($input) {
        return ceil($input);
    }

    public function modifierHtmlDecode($input) {
        return html_entity_decode($input, ENT_QUOTES, $this->encoding);
    }

    public function modifierSpamProtect($input) {
        return str_replace(['@', '.'], ['&#64;', '&#46;'], $input);
    }

    public function modifierMoneyFormat($input, $parameter) {
        if (is_numeric($input)) {
            $input = money_format($parameter, $input);
        }

        return $input;
    }

    public function modifierLTrim($input, $parameter) {
        if ($parameter !== null) {
            return ltrim($input, $parameter);
        }

        return ltrim($input);
    }

    public function modifierRTrim($input, $parameter) {
        if ($parameter !== null) {
            return rtrim($input, $parameter);
        }

        return rtrim($input);
    }

    public function modifierAbs($input) {
        return abs($input);
    }

    public function getterThen($input, $parameter) {
        return $input == true ? $parameter : null;
    }

    public function getterElse($input, $parameter) {
        return $input == false ? $parameter : null;
    }

    public function getterIfEmpty($input, $parameter) {
        return empty($input) ? $parameter : $input;
    }

    public function getterIfNotEmpty($input, $parameter) {
        return !empty($input) ? $parameter : $input;
    }

    public function getterLength($input) {
        return mb_strlen($input, $this->encoding);
    }

    public function getterFirst($input) {
        if (is_array($input)) {
            return reset($input);
        }

        return $input;
    }

    public function getterLast($input) {
        if (is_array($input)) {
            return end($input);
        }

        return $input;
    }

}