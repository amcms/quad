<?php

namespace Amcms\Quad\Sources;

class Source
{
    private $quad;
    private $options = [];

    public function setOption($option, $value)
    {
        $this->options[$option] = $value;
    }

    public function getOption($option)
    {
        return isset($this->options[$option]) ? $this->options[$option] : null;
    }

    public function __construct(\Amcms\Quad\Quad $quad, array $options = [])
    {
        $this->quad = $quad;

        foreach ($options as $option => $value) {
            $this->setOption($option, $value);
        }
    }

    public function load(string $source) {}
}
