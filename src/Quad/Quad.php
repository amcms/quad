<?php

namespace Amcms\Quad;

class Quad {

    private $translator;

    private $snippets = [];

    private $filters = [];

    private $values = [];

    private $placeholders = [];

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

    /**
     * Loads template contents, if $template is filename,
     * or removes binding if it present
     * 
     * @param  string $template Template name/content
     * @return string
     */
    public function loadTemplate($template) {
        if (strpos($template, '@') === 0) {
            $binding = trim(substr($template, 1, strpos($template, ' ') - 1), ': ');

            switch ($binding) {
                case 'CODE': {
                    $template = substr($template, 7);
                    break;
                }

                default: {
                    throw new \Exception("Unknown binding '" . $binding . "'", 1);
                }
            }
        } else {
            $filename = $this->getOption('templates') . '/' . $template;
            $template = @file_get_contents($filename);

            if ($template === false) {
                throw new \Exception("Cannot read template '" . $filename . "'", 1);
            }
        }

        return $template;
    }

    /**
     * Compiles template and/or returns filename of compiled php file
     * 
     * @param  string $template Template content
     * @return string
     */
    private function compile($template) {
        $hash  = hash('sha256', $template) . '.php';
        $parts = [substr($hash, 0, 1), substr($hash, 1, 1)];
        $cache = $this->getOption('cache');
        $file  = $cache . '/' . implode('/', $parts) . '/' . $hash;

        if (!file_exists($file)) {
            $path = $cache;

            foreach ($parts as $part) {
                $path .= '/' . $part;

                if (!file_exists($path)) {
                    if (@mkdir($path, 0700) === false ) {
                        throw new \Exception("Cannot create directory '" . $path . "'", 1);
                    }
                }
            }

            $output = $this->translator->parse($template);

            if (@file_put_contents($file, $output) === false) {
                throw new \Exception("Cannot save compiled template '" . $file . "'", 1);
            }
        } elseif (!is_readable($file)) {
            throw new \Exception("Compiled template '" . $file . "' exists but not readable", 1);
        }

        return $file;
    }

    /**
     * @param  string $filename Full filename of compiled template
     * @param  array  $values   Array of values
     * @return string
     */
    public function renderCompiledTemplate($filename, $values = []) {
        $this->values[] = $values;
        $api = $this;

        ob_start();
        include($filename);
        $output = ob_get_contents();
        ob_end_clean();

        array_pop($this->values);

        return $output;
    }

    public function renderTemplate($name, $params = []) {
        $content  = $this->loadTemplate($name);
        $compiled = $this->compile($content);
        return $this->renderCompiledTemplate($compiled, $params);
    }

    /**
     * Render chunk
     * 
     * @param  string $name
     * @param  array  $params
     * @return string
     */
    public function parseChunk($name, $params = []) {
        return $this->renderTemplate('partials/' . $name . '.tpl', $params);
    }

    public function clearCache() {

    }

    /**
     * Runs snippet
     * 
     * @param  string  $name
     * @param  array   $params
     * @param  boolean $cached
     * @return mixed
     */
    public function runSnippet($name, $params = [], $cached = true) {
        if (!array_key_exists($name, $this->snippets)) {
            throw new \Exception("Snippet '$name' not registered!", 1);
        }

        $function = $this->snippets[$name];
        $input = $function($input, $params);

        return $input;
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function setPlaceholder($name, $value) {
        $this->placeholders[$name] = $value;
    }

    /**
     * If placeholder not exists, method should return null
     * 
     * @param  string $name
     * @return mixed|null
     */
    public function getPlaceholder($name) {
        if (array_key_exists($name, $this->placeholders)) {
            return $this->placeholders[$name];
        }

        for ($i = count($this->values) - 1; $i >= 0; $i++) {
            if (isset($this->values[$i][$name])) {
                return $this->values[$i][$name];
            }
        }

        return null;
    }

    /**
     * @param  string $name Document field name
     * @return string
     */
    public function getField($name) {
        return $name;
    }

    /**
     * @param  string $name Option name
     * @return string
     */
    public function getConfig($name) {
        return $name;
    }

    /**
     * @param  integer $id Identificator of the document
     * @return string
     */
    public function makeUrl($id) {
        return $id;
    }

    /**
     * @param  string $input   Input value
     * @param  array  $filters Array of pairs filter_name => filter_value
     * @return string
     */
    public function applyFilters($input, $filters = []) {
        foreach ($filters as $filter => $value) {
            if (!array_key_exists($filter, $this->filters)) {
                continue;
            }

            $function = $this->filters[$filter];
            $input = $function($input, $value);

            if ($input === null) {
                break;
            }
        }

        return $input;
    }

    public function registerFilter($name, $function) {
        $this->filters[$name] = $function;
    }

    public function registerSnippet($name, $function) {
        $this->snippets[$name] = $function;
    }

}

