<?php

namespace Amcms\Quad\Controls;

use Amcms\Quad\Translator;

class IfControl extends Control {

    private $states = [
        'if'     => ['endif', 'else', 'elseif'],
        'elseif' => ['endif', 'else', 'elseif'],
        'else'   => ['endif'],
        'endif'  => [],
    ];

    public function getTag() {
        return 'if';
    }

    public function parse() {
        $parts = [];

        $this->currentTag = $this->getTag();

        do {
            $this->iterator->nextAll(Translator::T_WHITESPACE);

            if ($this->currentTag == 'if' || $this->currentTag == 'elseif') {
                $this->iterator->nextToken(Translator::T_QUOTE);
            }

            $part = [
                'tag'       => $this->currentTag,
                'condition' => $this->translator->parseInstruction([Translator::T_QUOTE, Translator::T_WHITESPACE, Translator::T_CONTROL_END], true),
            ];

            if ($this->currentTag == 'if' || $this->currentTag == 'elseif') {
                $this->iterator->nextToken(Translator::T_QUOTE);
            }

            $this->iterator->nextAll(Translator::T_WHITESPACE);
            $this->iterator->expect(Translator::T_CONTROL_END);

            $prev = $this->currentTag;
            $part['body'] = $this->parseBody($this->states[$this->currentTag]);

            if (!in_array($this->currentTag, $this->states[$prev])) {
                throw new \Exception("Unexpected control tag '" . $this->currentTag . "', expected '" . implode("', '", $this->states[$prev] ) . "'");
            }

            $parts[] = $part;
        } while ($this->iterator->isNext() && $this->currentTag != 'endif');

        if ($this->currentTag != 'endif') {
            throw new \Exception("Expected tag 'endif'");
        } else {
            $this->iterator->nextAll(Translator::T_WHITESPACE);
            $this->iterator->expect(Translator::T_CONTROL_END);
        }

        return $this->build($parts);
    }

    public function build($parts) {
        $output = '';

        foreach ($parts as $part) {
            $output .= $part['tag'];

            if ($part['tag'] != 'else') {
                $output .= ' (' . $part['condition'] . ')';
            }

            $output .= " {\n" . $part['body'] . '}';
        }

        $output .= "\n";

        return $output;
    }

}