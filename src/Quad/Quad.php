<?php

namespace Amcms\Quad;

class Quad {

    protected $translator;

    protected $snippets = [];

    protected $filters = [];

    protected $values = [];

    protected $placeholders = [];

    protected $sources = [];

    protected $defaultSource;

    public function __construct($options = []) {
        $this->translator = new Translator;

        foreach ($options as $option => $value) {
            $this->setOption($option, $value);
        }

        new Filters($this);
    }

    public function setOption($option, $value) {
        $this->options[$option] = $value;
    }

    public function getOption($option) {
        return isset($this->options[$option]) ? $this->options[$option] : null;
    }

    /**
     * Loads template contents
     *
     * @param  string $template Template name
     * @param  string|null $source Source of template
     * @return string
     */
    public function loadTemplate($template, $source = null) {
        if (empty($this->defaultSource)) {
            $this->defaultSource = reset($this->sources);
        }

        if ($source !== null) {
            $source = strtoupper($source);

            if (isset($this->sources[$source])) {
                $source = $this->sources[$source];
            } else {
                $source = null;
            }
        }

        if (empty($source)) {
            $source = $this->defaultSource;
        }
        
        if (strpos($template, '@') === 0 && preg_match('/@(\w+)[:\s]{1}\s*(.+)$/s', $template, $matches)) {
            if (!isset($this->sources[$matches[1]])) {
                throw new Exceptions\UnknownBindingException("Unknown binding '" . $matches[1] . "', you must provide source for this binding!");
            }

            $source   = $this->sources[$matches[1]];
            $template = $matches[2];
        }

        return call_user_func([$source, 'load'], $template);
    }

    public function getCompiledTemplateName($template) {
        $cache = $this->getOption('cache');

        $hash  = hash('sha256', $template) . '.php';
        $parts = [substr($hash, 0, 1), substr($hash, 1, 1)];

        return realpath($cache) . '/' . implode('/', $parts) . '/' . $hash;
    }

    public function createDirectories($file) {
        $file  = str_replace(DIRECTORY_SEPARATOR, '/', $file);
        $parts = explode('/', pathinfo($file, PATHINFO_DIRNAME));
        $path  = array_shift($parts);

        foreach ($parts as $part) {
            $path .= '/' . $part;

            if (!file_exists($path)) {
                if (@mkdir($path, 0700) === false ) {
                    throw new Exceptions\FileSaveException("Cannot create directory '" . $path . "'");
                }
            }
        }
    }

    /**
     * Compiles template and/or returns filename of compiled php file
     *
     * @param  string $template Template content
     * @return string
     */
    public function compile($template) {
        $cache = $this->getOption('cache');

        if ($cache === false) {
            return $this->translator->parse($template);
        }

        $file = $this->getCompiledTemplateName($template);

        if (!file_exists($file)) {
            $this->createDirectories($file);
            $output = $this->translator->parse($template);

            if (@file_put_contents($file, $output) === false) {
                throw new Exceptions\FileSaveException("Cannot save compiled template '" . $file . "'");
            }
        } elseif (is_dir($file) || !is_readable($file)) {
            throw new Exceptions\FileNotFoundException("Compiled template '" . $file . "' exists but not readable");
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

        if ($this->getOption('cache') !== false) {
            include($filename);
        } else {
            eval(preg_replace('/<\?php\s*(.+)$/s', '$1', $filename));
        }

        $output = ob_get_contents();
        ob_end_clean();

        array_pop($this->values);

        return $output;
    }

    public function renderTemplate($name, $params = [], $source = null) {
        $content  = $this->loadTemplate($name, $source);
        $compiled = $this->compile($content);
        return $this->renderCompiledTemplate($compiled, $params);
    }

    public function addSource($source, $default = false)
    {
        $name = get_class($source);
        $name = explode('\\', $name);
        $name = array_pop($name);
        $name = strtoupper(str_replace('Source', '', $name));

        $this->sources[$name] = $source;

        if ($default) {
            $this->defaultSource = $source;
        }
    }

    /**
     * Render chunk
     *
     * @param  string $name
     * @param  array  $params
     * @return string
     */
    public function parseChunk($name, $params = []) {
        return $this->renderTemplate($name, $params, 'CHUNK');
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
        $input = $function($params, $cached);

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
     * Removes placeholder from set
     *
     * @param string $name Name of the placeholder to unset
     */
    public function unsetPlaceholder($name) {
        if (array_key_exists($name, $this->placeholders)) {
            unset($this->palceholders[$name]);
        }
    }

    /**
     * Returns value of key, that presents with $path
     *
     * @param  array $source
     * @param  array $path Array of keys for search in $source
     * @return mixed|null
     */
    private function getArrayAttribute($source, $path) {
        foreach ($path as $key) {
            if (!isset($source[$key])) {
                return null;
            }

            $source = $source[$key];
        }

        return $source;
    }

    /**
     * If placeholder not exists, method should return null
     *
     * @param  string|array $name
     * @return mixed|null
     */
    public function getPlaceholder($name) {
        if (!is_array($name)) {
            $name = [$name];
        }

        for ($i = count($this->values) - 1; $i >= 0; $i--) {
            $value = $this->getArrayAttribute($this->values[$i], $name);

            if ($value !== null) {
                return $value;
            }
        }

        if ($value === null) {
            $value = $this->getArrayAttribute($this->placeholders, $name);
        }

        return $value;
    }

    /**
     * Returns value of field of current document.
     * If $binding is not null, value must be fetched
     * for document from $binding and $binding_arg.
     * For example,
     * [*pagetitle@parent*] - from parent document,
     * [*pagetitle@uparent(2)*] - from 2-level parent, etc.
     *
     * @param  string $name Document field name
     * @param  string $binding Name of binding
     * @param  string $binding_arg Binding argument
     * @return string
     */
    public function getField($name, $binding = null, $binding_arg = null) {
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
     * @param string $name
     * @return mixed
     */
    public function getLang($name) {
        return $name;
    }

    /**
     * @param  string $input   Input value
     * @param  array  $filters Array of pairs filter_name => filter_parameter
     * @return string
     */
    public function applyFilters($input, $filters = []) {
        $value = $input;

        foreach ($filters as $filter) {
            if (!array_key_exists($filter[0], $this->filters)) {
                continue;
            }

            if ($filter[0] == 'then' && $value === false) {
                continue;
            }

            if ($filter[0] == 'else' && ($value === true || $input === true)) {
                continue;
            }

            $function = $this->filters[ $filter[0] ];

            $input = $value;
            $value = call_user_func_array($function, [$input, $filter[1]]);
        }

        return $value;
    }

    public function registerFilter($name, $function) {
        $this->filters[$name] = $function;
    }

    public function registerSnippet($name, $function) {
        $this->snippets[$name] = $function;
    }

}

