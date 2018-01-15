<?php

namespace Amcms\Quad\Sources;

class FileSource extends Source
{
    public function load($source)
    {
        if (!preg_match('/\.tpl$/', $source)) {
            $source .= '.tpl';
        }

        $fullname = rtrim($this->getOption('templates'), '/') . '/' . $source;

        if (!is_dir($fullname) && is_readable($fullname)) {
            return file_get_contents($fullname);
        } else {
            throw new \Amcms\Quad\Exceptions\FileNotFoundException("Cannot read template '" . $fullname . "'");
        }
    }
}
