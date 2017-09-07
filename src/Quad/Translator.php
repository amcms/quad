<?php

namespace Amcms\Quad;

use \Nette\Utils\Tokenizer;

class Translator {

    const T_QUAD_BRACKET_START = 1;     const T_QUAD_BRACKET_END = 2;
    const T_SNIPPET_START      = 3;     const T_SNIPPET_END      = 4;
    const T_NC_SNIPPET_START   = 5;     const T_NC_SNIPPET_END   = 6;
    const T_FIELD_START        = 7;     const T_FIELD_END        = 8;
    const T_PLACEHOLDER_START  = 9;     const T_PLACEHOLDER_END  = 10;
    const T_CONFIG_START       = 11;    const T_CONFIG_END       = 12;
    const T_CHUNK_START        = 13;    const T_CHUNK_END        = 14;
    const T_LINK_START         = 15;    const T_LINK_END         = 16;
    const T_QUESTION           = 17;    const T_AMPERSAND        = 18;
    const T_QUOTE              = 19;    const T_EQUAL            = 20;
    const T_BINDING            = 21;    const T_COMMA            = 22;
    const T_COLON              = 23;    const T_NEGATION         = 24;
    const T_ROUND_OPEN         = 25;    const T_ROUND_CLOSE      = 26;
    const T_DOT                = 27;    const T_MINUS            = 28;
    const T_CONTROL_START      = 29;    const T_CONTROL_END      = 30;
    const T_WHITESPACE         = 1000;  const T_STRING           = 1001;
    const T_ANYTHING           = 2000;

    const MODE_PHP  = 1;
    const MODE_MODX = 2;

    /**
     * Token symbols, used for error message only
     */
    private $symbols = [
        self::T_CONTROL_START     => '{%',     self::T_CONTROL_END     => '%}',
        self::T_SNIPPET_START     => '[[',     self::T_SNIPPET_END     => ']]',
        self::T_NC_SNIPPET_START  => '[!',     self::T_NC_SNIPPET_END  => '!]',
        self::T_FIELD_START       => '[*',     self::T_FIELD_END       => '*]',
        self::T_PLACEHOLDER_START => '[+',     self::T_PLACEHOLDER_END => '+]',
        self::T_CONFIG_START      => '[(',     self::T_CONFIG_END      => ')]',
        self::T_CHUNK_START       => '{{',     self::T_CHUNK_END       => '}}',
        self::T_LINK_START        => '[~',     self::T_LINK_END        => '~]',
        self::T_QUESTION          => '?',      self::T_AMPERSAND       => '&',
        self::T_BINDING           => '@',      self::T_QUOTE           => '`',
        self::T_EQUAL             => '=',      self::T_WHITESPACE      => 'space',
        self::T_STRING            => 'word',   self::T_ANYTHING        => 'any',
        self::T_COLON             => ':',      self::T_NEGATION        => '!',
        self::T_ROUND_OPEN        => '(',      self::T_ROUND_CLOSE     => ')',
        self::T_DOT               => '.',      self::T_MINUS           => '-',
    ];

    /**
     * Relation of opening and closing braces
     */
    private $brackets = [
        self::T_SNIPPET_START     => self::T_SNIPPET_END,
        self::T_NC_SNIPPET_START  => self::T_NC_SNIPPET_END,
        self::T_CHUNK_START       => self::T_CHUNK_END,
        self::T_FIELD_START       => self::T_FIELD_END,
        self::T_PLACEHOLDER_START => self::T_PLACEHOLDER_END,
        self::T_CONFIG_START      => self::T_CONFIG_END,
        self::T_LINK_START        => self::T_LINK_END,
    ];

    /**
     * Translation instructions
     */
    private $instructions = [
        self::MODE_MODX => [
            self::T_SNIPPET_START     => '[[%s]]',
            self::T_NC_SNIPPET_START  => '[!%s!]',
            self::T_CHUNK_START       => '{{%s}}',
            self::T_FIELD_START       => '[*%s*]',
            self::T_PLACEHOLDER_START => '[+%s+]',
            self::T_CONFIG_START      => '[(%s)]',
            self::T_LINK_START        => '[~%s~]',
            'params_start'            => '? ',
            'params_delimiter'        => ' ',
            'params_end'              => '',
        ],
        self::MODE_PHP => [
            self::T_SNIPPET_START     => '$api->runSnippet(%s)',
            self::T_NC_SNIPPET_START  => '$api->runSnippet(%s, false)',
            self::T_CHUNK_START       => '$api->parseChunk(%s)',
            self::T_FIELD_START       => '$api->getField(%s)',
            self::T_PLACEHOLDER_START => '$api->getPlaceholder(%s)',
            self::T_CONFIG_START      => '$api->getConfig(%s)',
            self::T_LINK_START        => '$api->makeUrl(%s)',
            'params_start'            => ', [',
            'params_delimiter'        => ', ',
            'params_end'              => ']',
        ],
    ];

    /**
     * @var \Nette\Utils\Tokenizer
     */
    private $tokenizer;

    /**
     * @var Iterator
     */
    private $iterator;

    /**
     * Current translation mode, php or modx string
     * @var integer
     */
    private $mode = self::MODE_PHP;

    /**
     * Nesting level of instructions
     * @var integer
     */
    private $level = 0;

    /**
     * List of registered control tags
     * @var array
     */
    private $controls = [];

    public function __construct() {
        $this->tokenizer = new Tokenizer([
            self::T_CONTROL_START     => '\{%',   self::T_CONTROL_END     => '%\}',
            self::T_SNIPPET_START     => '\[\[',  self::T_SNIPPET_END     => '\]\]',
            self::T_NC_SNIPPET_START  => '\[\!',  self::T_NC_SNIPPET_END  => '\!\]',
            self::T_FIELD_START       => '\[\*',  self::T_FIELD_END       => '\*\]',
            self::T_PLACEHOLDER_START => '\[\+',  self::T_PLACEHOLDER_END => '\+\]',
            self::T_CONFIG_START      => '\[\(',  self::T_CONFIG_END      => '\)\]',
            self::T_CHUNK_START       => '\{\{',  self::T_CHUNK_END       => '\}\}',
            self::T_LINK_START        => '\[\~',  self::T_LINK_END        => '\~\]',
            self::T_ROUND_OPEN        => '\(',    self::T_ROUND_CLOSE     => '\)',
            self::T_QUESTION          => '\?',    self::T_AMPERSAND       => '\&',
            self::T_BINDING           => '@',     self::T_QUOTE           => '`',
            self::T_EQUAL             => '=',     self::T_COLON           => ':',
            self::T_NEGATION          => '\!',    self::T_DOT             => '\.',
            self::T_MINUS             => '-',
            self::T_STRING            => '\\w+',  self::T_WHITESPACE      => '[\\s\\n\\r]+',
            self::T_ANYTHING          => '.',
        ]);

        $this->registerControl('if', Controls\IfControl::class);
//        $this->registerControl('for', Controls\ForControl::class);
//        $this->registerControl('switch', Controls\SwitchControl::class);
    }

    /**
     * Start point, makes compiled template
     * 
     * @param  string $input Source template
     * @return string
     */
    public function parse($input) {
        $this->iterator = new TokenIterator($this->tokenizer->tokenize($input));
        $this->level = 0;
        $this->source = $input;

        try {
            return $this->parseString($inside = false);
        } catch (UnexpectedTokenException $e) {
            $tokens = $e->getExpectedTokens();
            $tokens = array_reverse($tokens);
            $offset = $e->getOffset();
            $output = [];
            $last   = count($tokens) - 1;

            foreach ($tokens as $i => $token) {
                if ($i > 0) {
                    $output[] = $i == $last ? "' or '" : "', '";
                }

                $output[] = $this->symbols[$token];
            }

            return "Unexpected token '" . $e->getToken() . "' at offset " . $offset . " near '" . 
                str_replace(["\n", "\r"], ['\\n', '\\r'], substr($input, $offset, 10)) . 
                "...', expected '" . implode($output) . "'!";
        }
    }

    /**
     * Parse all instructions on same level
     * 
     * @param  boolean $inside Is it inline template?
     * @return string
     */
    private function parseString($inside = true, $endTag = []) {
        $result = [];

        $this->level++;

        while ($this->iterator->isNext()) {
            if ($this->level > 1 ) {
                foreach ($endTag as $tag) {
                    if ($this->iterator->isNext($tag)) {
                        $this->level--;
                        return implode($this->mode == self::MODE_MODX ? '' : ' . ', $result);
                    }
                }
            }

            $value = '';
            $token = $this->iterator->nextToken();

            switch ($token[Tokenizer::TYPE]) {
                case self::T_CONTROL_START: {
                    $value = $this->parseControl();
                    break;
                }

                case self::T_SNIPPET_START:
                case self::T_NC_SNIPPET_START:
                case self::T_CHUNK_START: {
                    $value = $this->parseSnippet($token[Tokenizer::TYPE], $this->brackets[ $token[Tokenizer::TYPE] ]);
                    break;
                }

                case self::T_CONFIG_START:
                case self::T_PLACEHOLDER_START:
                case self::T_FIELD_START: {
                    $value = $this->parseVariable($token[Tokenizer::TYPE], $this->brackets[ $token[Tokenizer::TYPE] ]);
                    break;
                }

                case self::T_LINK_START: {
                    $value = $this->parseMakeUrl();
                    break;
                }

                case self::T_BINDING: {
                    if ($this->iterator->isNext(
                        self::T_SNIPPET_START,
                        self::T_NC_SNIPPET_START,
                        self::T_CHUNK_START,
                        self::T_CONFIG_START,
                        self::T_PLACEHOLDER_START,
                        self::T_FIELD_START
                    )) {
                        $openTag  = $this->iterator->nextToken();
                        $closeTag = $this->brackets[ $openTag[Tokenizer::TYPE] ];
                        $value    = $openTag[Tokenizer::VALUE] . $this->iterator->joinUntil($closeTag) . $this->iterator->nextValue();

                        if ($this->mode == self::MODE_PHP) {
                            $value = "'" . $this->escape($value) . "'";
                        }
                        break;
                    }
                }

                default: {
                    $value = $token[Tokenizer::VALUE] . $this->iterator->joinUntil(
                        self::T_CONTROL_START,     self::T_CONTROL_END, 
                        self::T_SNIPPET_START,     self::T_SNIPPET_END, 
                        self::T_NC_SNIPPET_START,  self::T_NC_SNIPPET_END, 
                        self::T_FIELD_START,       self::T_FIELD_END, 
                        self::T_PLACEHOLDER_START, self::T_PLACEHOLDER_END,
                        self::T_CONFIG_START,      self::T_CONFIG_END,
                        self::T_CHUNK_START,       self::T_CHUNK_END, 
                        self::T_LINK_START,        self::T_LINK_END,
                        self::T_ROUND_OPEN,        self::T_ROUND_CLOSE,
                        self::T_QUOTE,             self::T_COLON,
                        self::T_QUESTION,          self::T_AMPERSAND,
                        self::T_BINDING,           self::T_DOT
                    );

                    if ($this->mode == self::MODE_PHP) {
                        $value = "'" . $this->escape($value) . "'";
                    }
                }
            }

            if ($value != '') {
                $result[] = $value;
            }
        }

        if ($this->level > 1) {
            throw new \Exception('Unexpected end of template', 1);
        }

        return '<?php echo ' . implode(', ', $result) . ';';
    }

    /**
     * Make URL
     * 
     * @return string
     */
    private function parseMakeUrl() {
        $input = $this->parseString($inside = true, $endTag = [self::T_LINK_END]);
        $this->iterator->expect(self::T_LINK_END);
        return sprintf($this->instructions[$this->mode][self::T_LINK_START], $input);
    }

    /**
     * Get placeholder, document field or setting value
     * 
     * @param  int $openTag  Type of opening tag
     * @param  int $closeTag Type of closing tag
     * @return string
     */
    private function parseVariable($openTag, $closeTag) {
        $name = $this->parseString($inside = true, $endTag = [$closeTag, self::T_COLON, self::T_BINDING, self::T_DOT]);

        // Parsing instruction [+key.key1.key2+]
        // for nesting arrays
        if ($this->iterator->isNext(self::T_DOT)) {
            $name = [$name];

            while ($this->iterator->isNext(self::T_DOT)) {
                $this->iterator->nextToken();
                $name[] = $this->parseString($inside = true, $endTag = [$closeTag, self::T_COLON, self::T_BINDING, self::T_DOT]);
            }
        }

        // Parsing instruction [*field@binding(value)*]
        // only for document fields
        if ($openTag == self::T_FIELD_START && $this->iterator->isNext(self::T_BINDING)) {
            $binding = [];

            $this->iterator->expect(self::T_BINDING);
            $binding['name'] = $this->iterator->expect(self::T_STRING);

            if ($this->iterator->isNext(self::T_ROUND_OPEN)) {
                $this->iterator->nextToken();
                $binding['value'] = $this->parseString($inside = true, $endTag = [self::T_ROUND_CLOSE]);
                $this->iterator->expect(self::T_ROUND_CLOSE);
            } else {
                $binding['value'] = null;
            }
        }

        // Parsing instruction [+field:filter1:filter2=`val`+]
        if ($this->iterator->isNext(self::T_COLON)) {
            $filters = $this->parseFilters();
        }

        $this->iterator->expect($closeTag);

        if (is_array($name)) {
            if ($this->mode == self::MODE_PHP) {
                $name = '[' . implode(', ', $name) . ']';
            } else {
                $name = implode('.', $name);
            }
        }

        $output = $name;

        if (!empty($binding)) {
            if ($this->mode == self::MODE_PHP) {
                $output .= ", '" . $binding['name'] . "', " . ($binding['value'] !== null ? $binding['value'] : 'null');
            } else {
                $output .= '@' . $binding['name'] . ($binding['value'] !== null ? '(' . $binding['value'] . ')' : '');
            }
        }

        if (!empty($filters) && $this->mode == self::MODE_MODX) {
            foreach ($filters as $filter) {
                $output .= ':' . $filter['name'];

                if ($filter['value'] !== null) {
                    $output .= '=`' . $filter['value'] . '`';
                }
            }
        }

        $output = sprintf($this->instructions[$this->mode][$openTag], $output);

        if (!empty($filters) && $this->mode == self::MODE_PHP) {
            $chain = [];

            foreach ($filters as $filter) {
                $chain[] = "'" . $filter['name'] . "', " . ($filter['value'] !== null ? $filter['value'] : 'null');
            }

            $output = '$api->applyFilters(' . $output . ', [[' . implode('], [', $chain) . ']])';
        }

        return $output;
    }

    private function parseFilter() {
        $this->iterator->expect(self::T_COLON);
        $name = $this->iterator->nextValue(self::T_NEGATION);
        $name .= $this->iterator->expect(self::T_STRING);

        if ($this->iterator->isNext(self::T_EQUAL)) {
            $this->iterator->nextToken(self::T_EQUAL);
            $this->iterator->expect(self::T_QUOTE);
            $value = $this->parseString($inside = true, $endTag = [self::T_QUOTE]);
            $this->iterator->expect(self::T_QUOTE);
        } else {
            $value = null;
        }

        return compact('name', 'value');
    }

    private function parseFilters() {
        $filters = [];

        do {
            if (!$this->iterator->isNext(self::T_COLON)) {
                break;
            } 
            $filters[] = $this->parseFilter();
        } while ($this->iterator->isNext());

        return $filters;
    }

    /**
     * Parse one parameter of snippet, chunk
     * 
     * @return string
     */
    private function parseSnippetParameter() {
        $name = $this->iterator->nextValue(self::T_STRING);
        $this->iterator->nextToken(self::T_WHITESPACE);
        $this->iterator->expect(self::T_EQUAL);
        $this->iterator->nextToken(self::T_WHITESPACE);
        $this->iterator->expect(self::T_QUOTE);

        $value = '';

        if ($this->iterator->isNext(self::T_QUOTE)) {
            $this->iterator->nextToken();
        } else {
            if ($this->iterator->isNext(self::T_BINDING)) {
                $this->mode = self::MODE_MODX;
                $value = "'" . $this->escape($this->parseString($inside = true, $endTag = [self::T_QUOTE])) . "'";
                $this->mode = self::MODE_PHP;
            } else {
                $value = $this->parseString($inside = true, $endTag = [self::T_QUOTE]);
            }
        }

        $this->iterator->expect(self::T_QUOTE);
        $this->iterator->nextAll(self::T_WHITESPACE);

        if ($this->mode == self::MODE_MODX) {
            return '&' . $this->escape($name) . '=`' . $value . '`';
        } else {
            if ($value == '') {
                $value = "''";
            }

            return "'" . $name . "' => " . $value;
        }
    }

    /**
     * Parse snippet, chunk
     * 
     * @param  int $openTag  Type of opening tag
     * @param  int $closeTag Type of closing tag
     * @return string
     */
    private function parseSnippet($openTag, $closeTag) {
        $isComment = false;

        if ($this->iterator->isNext(self::T_MINUS)) {
            $this->iterator->nextToken();
            $isComment = true;
        }

        $result = [
            'name'   => $this->parseString($inside = true, $endTag = [$closeTag, self::T_QUESTION, self::T_WHITESPACE, self::T_AMPERSAND, self::T_COLON]),
            'type'   => $openTag,
            'cached' => $closeTag == self::T_SNIPPET_START,
            'params' => [],
        ];

        if ($this->iterator->isNext(self::T_COLON)) {
            $filters = $this->parseFilters();
        }

        if ($this->iterator->isNext(self::T_QUESTION)) {
            $this->iterator->nextAll(self::T_QUESTION, self::T_WHITESPACE);

            $i = 0;

            do {
                if ($this->iterator->isNext($closeTag)) {
                    break;
                }

                // ampersand before first parameter can be omitted
                if ($i++) {
                    $this->iterator->expect(self::T_AMPERSAND);
                } else {
                    $this->iterator->nextToken(self::T_AMPERSAND);
                }

                $result['params'][] = $this->parseSnippetParameter();
            } while ($this->iterator->isNext());
        }

        $this->iterator->expect($closeTag);

        if ($isComment) {
            return "''";
        }

        $output = $result['name'];
        
        if (!empty($filters) && $this->mode == self::MODE_MODX) {
            foreach ($filters as $filter) {
                $output .= ':' . $filter['name'];

                if ($filter['value'] !== null) {
                    $output .= '=`' . $filter['value'] . '`';
                }
            }
        }

        $output .= $this->instructions[$this->mode]['params_start'] . implode($this->instructions[$this->mode]['params_delimiter'], $result['params'])
                 . $this->instructions[$this->mode]['params_end'];

        $output = sprintf($this->instructions[$this->mode][ $result['type'] ], $output);

        if (!empty($filters) && $this->mode == self::MODE_PHP) {
            $chain = [];

            foreach ($filters as $filter) {
                $chain[] = "'" . $filter['name'] . "', " . ($filter['value'] !== null ? $filter['value'] : 'null');
            }

            $output = '$api->applyFilters(' . $output . ', [[' . implode('], [', $chain) . ']])';
        }

        return $output;
    }

    /**
     * Escapes string
     * 
     * @param  string $string Input
     * @return string
     */
    private function escape($string) {
        return str_replace("'", "\\'", $string);
    }

    public function registerControl($name, $class) {
        $this->controls[$name] = $class;
    }

    private function parseControl() {
        if ($this->mode == self::MODE_MODX) {
            throw new \Exception("Control tags not allowed in inline templates");
        }

        if ($this->level > 1) {
            throw new \Exception("Control tags not allowed in nested instructions");
        }

        $this->iterator->nextAll(self::T_WHITESPACE);
        $name = $this->iterator->nextValue(self::T_STRING);
        $this->iterator->nextAll(self::T_WHITESPACE);

        if (!array_key_exists($name, $this->controls)) {
            throw new \Exception("Unknown control tag '$name'");
        }

        if (is_string($this->controls[$name])) {
            $this->controls[$name] = new $this->controls[$name]($this->iterator);
        }

        return $this->controls[$name]->parse();
    }

}

