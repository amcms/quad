<?php

namespace Amcms\Quad\Controls;

use Amcms\Quad\Translator;
use Nette\Utils\Tokenizer;

class Control {

    protected $translator;

    protected $iterator;

    protected $currentTag;

    public function __construct($translator) {
        $this->translator = $translator;
        $this->currentTag = $this->getTag();

        $this->setIterator($translator->getIterator());
    }

    public function setIterator($iterator) {
        $this->iterator = $iterator;
    }

    public function getTag() {
        return '';
    }

    public function parse() {
        $this->iterator->nextUntil(Translator::T_CONTROL_END);
        $this->iterator->nextToken();

        return '';
    }

    protected function parseBody($endControl = []) {
        $result = '';

        $current = $this->iterator->currentToken();

        if ($current[Tokenizer::TYPE] == Translator::T_CONTROL_START) {
            $name = $this->translator->parseControlTag($current[Tokenizer::VALUE]);

            if (!in_array($name, $endControl)) {
                return $result;
            }
        }

        while ($this->iterator->isNext()) {
            if ($this->iterator->isNext(Translator::T_CONTROL_START)) {
                $token = $this->iterator->nextValue();
                $this->currentTag = $this->translator->parseControlTag($token);

                if (!in_array($this->currentTag, $endControl)) {
                    $result .= $this->translator->parseControl($token);
                } else {
                    return $result;
                }

                continue;
            }

            $result .= $this->translator->parseInstruction([Translator::T_CONTROL_START], false);
        }

        return $result;
    }

}