<?php

namespace Amcms\Quad\Controls;

use Amcms\Quad\Translator;

class SwitchControl extends Control {

    private $states = [
        'case'    => 'endcase',
        'default' => 'enddefault',
    ];

    public function getTag() {
        return 'switch';
    }

    public function parse() {
        $this->iterator->nextAll(Translator::T_WHITESPACE);
        $this->iterator->nextToken(Translator::T_QUOTE);

        $test = $this->translator->parseInstruction([Translator::T_QUOTE, Translator::T_WHITESPACE, Translator::T_CONTROL_END], true);

        $this->iterator->nextToken(Translator::T_QUOTE);
        $this->iterator->nextAll(Translator::T_WHITESPACE);
        $this->iterator->expect(Translator::T_CONTROL_END);

        $cases = [];

        $this->currentTag = $this->getTag();

        while ($this->iterator->isNext()) {
            $this->iterator->nextAll(Translator::T_WHITESPACE);
            $this->currentTag = $this->iterator->expect(Translator::T_CONTROL_START);
            $control = $this->translator->parseControlTag($this->currentTag);

            if ($control == 'endswitch') {
                break;
            }

            if (!array_key_exists($control, $this->states)) {
                throw new \Exception("Unexpected control tag '" . $this->currentTag . "', expected '" . implode("','", array_keys($this->states)) ."'");
            }

            $this->iterator->nextAll(Translator::T_WHITESPACE);

            if ($control == 'case') {
                $this->iterator->nextToken(Translator::T_QUOTE);
                $value = $this->translator->parseInstruction([Translator::T_QUOTE, Translator::T_WHITESPACE, Translator::T_CONTROL_END], true);
                $this->iterator->nextToken(Translator::T_QUOTE);
            } else {
                $value = null;
            }

            $this->iterator->nextAll(Translator::T_WHITESPACE);
            $this->iterator->expect(Translator::T_CONTROL_END);

            $body = $this->parseBody([$this->states[$control]]);

            $this->iterator->nextAll(Translator::T_WHITESPACE);
            $this->iterator->expect(Translator::T_CONTROL_END);

            if ($this->currentTag != $this->states[$control]) {
                throw new \Exception("Unexpected control tag '" . $this->currentTag . "', expected '" . $this->states[$control] . "'");
            }

            $cases[] = [
                'type'  => $control,
                'value' => $value,
                'body'  => $body,
            ];
        }

        if ($control != 'endswitch') {
            throw new \Exception("Unexpected control tag '$control', expected 'endswitch'");
        } else {
            $this->iterator->nextAll(Translator::T_WHITESPACE);
            $this->iterator->expect(Translator::T_CONTROL_END);
        }

        return $this->build($test, $cases);
    }

    public function build($test, $cases) {
        $output = "switch ($test) {\n";

        foreach ($cases as $case) {
            if ($case['type'] == 'case') {
                $output .= "case " . $case['value'] . ": {\n";
            } else {
                $output .= "default: {\n";
            }

            $output .= $case['body'];
            $output .= "break;\n";
            $output .= "}\n";
        }

        $output .= "}\n";

        return $output;
    }

}