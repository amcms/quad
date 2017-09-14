<?php

namespace Amcms\Quad\Controls;

use Amcms\Quad\Translator;

class ForControl extends Control {

    public function getTag() {
        return 'for';
    }

    public function parse() {
        $this->currentTag = $this->getTag();

        $this->iterator->nextAll(Translator::T_WHITESPACE);
        $this->iterator->expect(Translator::T_QUOTE);

        $item  = $this->iterator->joinAll(Translator::T_STRING);

        $this->iterator->expect(Translator::T_QUOTE);
        $this->iterator->nextAll(Translator::T_WHITESPACE);
        $this->iterator->expectString('in');
        $this->iterator->nextAll(Translator::T_WHITESPACE);
        $this->iterator->nextToken(Translator::T_QUOTE);

        $source = $this->translator->parseInstruction([Translator::T_QUOTE, Translator::T_WHITESPACE, Translator::T_CONTROL_END], true);

        $this->iterator->nextToken(Translator::T_QUOTE);
        $this->iterator->nextAll(Translator::T_WHITESPACE);
        $this->iterator->expect(Translator::T_CONTROL_END);

        $body = $this->parseBody(['endfor']);

        if ($this->currentTag != 'endfor') {
            throw new \Exception("Expected tag 'endfor'");
        }

        $this->iterator->nextAll(Translator::T_WHITESPACE);
        $this->iterator->expect(Translator::T_CONTROL_END);

        $index     = $item . '_index';
        $iteration = $item . '_iteration';
        $first     = $item . '_first';
        $last      = $item . '_last';
        $odd       = $item . '_odd';
        $length    = $item . '_length';

        $output  = "\$$index = 0;\n";
        $output .= "\$source = $source;\n";
        $output .= "\$$length = count(\$source);";
        $output .= "\$api->setPlaceholder('$length', \$$length);\n";
        $output .= "foreach (\$source as \$$item) {\n";
        $output .= "\$api->setPlaceholder('$index', \$$index);\n";
        $output .= "\$api->setPlaceholder('$iteration', \$$index + 1);\n";
        $output .= "\$api->setPlaceholder('$first', (int)(\$$index == 0));\n";
        $output .= "\$api->setPlaceholder('$last', (int)(\$$index + 1 == \$$length));\n";
        $output .= "\$api->setPlaceholder('$odd', (int)(\$$index % 2 == 0));\n";
        $output .= "\$api->setPlaceholder('$item', \$$item);\n";
        $output .= $body;
        $output .= "\$$index++;\n";
        $output .= "}\n";
        
        return $output;
    }

}