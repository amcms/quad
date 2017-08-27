<?php

namespace Amcms\Quad;

class Quad {

    const SNIPPET     = 1;
    const CHUNK       = 2;
    const PLACEHOLDER = 3;
    const DOCFIELD    = 4;
    const SETTING     = 5;

    private $translator;

    private $functions = [];

    private $modifiers = [];

    public function __construct($options) {
        $this->translator = new Translator;

        foreach ($options as $option => $value) {
            $this->setOption($option, $value);
        }
    }

    public function setOption($option, $value) {
        $this->options[$option] = $value;
    }

    public function getOption($option) {
        return isset($this->options[$option]) ? $this->options[$option] : null;
    }

    public function render($filename) {
        $hash  = hash('sha256', $filename) . '.php';
        $parts = [substr($hash, 0, 1), substr($hash, 1, 1)];
        $path  = $this->getOption('cache');

        foreach ($parts as $part) {
            $path .= '/' . $part;

            if (!file_exists($path)) {
                if (@mkdir($path, 0700) === false ) {
                    throw new \Exception("Cannot create directory '" . $path . "'", 1);
                }
            }
        }

        $compiled = $path . $hash;

        if (file_exists($compiled)) {
            if (!is_readable($compiled)) {
                throw new \Exception("Compiled template '" . $compiled . "' for template '" . $filename . "' exists but not readable", 1);
            }

            return $this->renderCompiled($compiled);
        } else {
            $filename = $this->getOption('templates') . '/' . $filename;
            $template = @file_get_contents($filename);

            if ($template === false) {
                throw new \Exception("Cannot read template '" . $filename . "'", 1);
            }

            $output = $this->translator->parse($template);

            if (@file_put_contents($compiled, $output) === false) {
                throw new \Exception("Cannot save compiled template '" . $compiled . "'", 1);
            }
        }

        return $this->renderCompiled($compiled);
    }

    public function renderCompiled($filename) {
        return include($filename);
    }

    /**
     * Calls registered function
     * @param  $args
     * @return mixed
     */
    public function call(...$args) {
        if (!isset($args[0])) {
            throw new \Exception("Type of instruction undefined", 1);
        }

        if (!isset($this->functions[ $args[0] ])) {
            throw new \Exception("Function for type " . $args[0] . " is not defined", 1);
        }

        $func = $this->functions[ $args[0] ];

        switch ($args[0]) {
            case self::SNIPPET: {
                if (count($args) == 4) {
                    return call_user_func($func, $args[1], $args[2], $args[3]);
                } else {
                    return call_user_func($func, $args[1], [], $args[2]);
                }
            }

            case self::CHUNK: {
                return call_user_func($func, $args[1], isset($args[1]) ? $args[1] : []);
            }

            case self::SETTING:
            case self::DOCFIELD:
            case self::PLACEHOLDER: {
                return call_user_func($func, $args[1]);
            }
        }
    }

    public function clearCache() {

    }

    /**
     * Register function for call from template
     * You MUST register all types of functions for correct parser work
     * @example
     * $quad->registerFunction(\Amcms\Quad\Quad::SNIPPET, function($name, $cached, $parameters) {...});
     * @param  uint $type Type of function (SNIPPET, CHUNK, etc)
     * @param  callable $function
     * @return bool
     */
    public function registerFunction($type, $function) {
        if (isset($this->functions[$type])) {
            throw new \Exception("Function for type " . $type . " already registered", 1);
        }

        $this->functions[$type] = $function;
    }

    /**
     * Register variable modifier (filter)
     * @example 
     * $quad->registerModifier('ellipsis', function($value, $parameters) {...});
     * and then in template:
     * [+value:ellipsis=`10`+]
     * @param  string $name Name of the modifier
     * @param  callable $function
     * @return bool
     */
    public function registerModifier($name, $function) {

    }

}

