<?php

namespace Amcms\Quad\Controls;

class IfControl extends Control {

    public function parse() {
        var_dump($this->iterator->nextUntil(\Amcms\Quad\Translator::T_CONTROL_END));
    }

}