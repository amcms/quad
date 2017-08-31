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
    const T_WHITESPACE         = 1000;  const T_STRING           = 1001;
    const T_ANYTHING           = 2000;

    /**
     * Token symbols, used for error message only
     */
    private $symbols = [
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
        'inline' => [
            self::T_SNIPPET_START     => '[[%s]]',
            self::T_NC_SNIPPET_START  => '[!%s!]',
            self::T_CHUNK_START       => '{{%s}}',
            self::T_FIELD_START       => '[*%s*]',
            self::T_PLACEHOLDER_START => '[+%s+]',
            self::T_CONFIG_START      => '[(%s)]',
            self::T_LINK_START        => '[~%s~]',
        ],
        'translated' => [
            self::T_SNIPPET_START     => '$api->runSnippet(%s, true)',
            self::T_NC_SNIPPET_START  => '$api->runSnippet(%s, false)',
            self::T_CHUNK_START       => '$api->parseChunk(%s)',
            self::T_FIELD_START       => '$api->getField(%s)',
            self::T_PLACEHOLDER_START => '$api->getPlaceholder(%s)',
            self::T_CONFIG_START      => '$api->getConfig(%s)',
            self::T_LINK_START        => '$api->makeUrl(%s)',
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
     * Is current token in inline template string?
     * @var boolean
     */
    private $inline = false;

    /**
     * Nesting level of instructions
     * @var integer
     */
    private $level = 0;

    public function __construct() {
        $this->tokenizer = new Tokenizer([
            self::T_SNIPPET_START     => '\[\[',  self::T_SNIPPET_END     => '\]\]',
            self::T_NC_SNIPPET_START  => '\[\!',  self::T_NC_SNIPPET_END  => '\!\]',
            self::T_FIELD_START       => '\[\*',  self::T_FIELD_END       => '\*\]',
            self::T_PLACEHOLDER_START => '\[\+',  self::T_PLACEHOLDER_END => '\+\]',
            self::T_CONFIG_START      => '\[\(',  self::T_CONFIG_END      => '\)\]',
            self::T_CHUNK_START       => '\{\{',  self::T_CHUNK_END       => '\}\}',
            self::T_LINK_START        => '\[\~',  self::T_LINK_END        => '\~\]',
            self::T_QUESTION          => '\?',    self::T_AMPERSAND       => '\&',
            self::T_BINDING           => '@',     self::T_QUOTE           => '`',
            self::T_EQUAL             => '=',     self::T_WHITESPACE      => '[\\s\\n\\r]+',
            self::T_STRING            => '\\w+',  self::T_ANYTHING        => '.',
        ]);
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
            return $this->parseString();
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

            return "Unexpected token '" . $e->getToken() . "' at offset " . $offset . 
                " near '" . substr($input, $offset, 10) . "...', expected '" . implode($output) . "'!";
        }
    }

    /**
     * Parse all instructions on same level
     * 
     * @param  boolean $inside Is it inline template?
     * @return string
     */
    private function parseString($inside = false) {
        $result = [];

        $this->level++;

        while ($this->iterator->isNext()) {
            if ($this->level > 1 && $this->iterator->isNext(
                self::T_SNIPPET_END, 
                self::T_NC_SNIPPET_END, 
                self::T_FIELD_END, 
                self::T_PLACEHOLDER_END,
                self::T_CONFIG_END,
                self::T_CHUNK_END, 
                self::T_LINK_END, 
                self::T_QUOTE
            )) {
                $this->level--;
                return implode($this->inline ? '' : ' . ', $result);
            }

            $token = $this->iterator->nextToken();

            switch ($token[Tokenizer::TYPE]) {
                case self::T_SNIPPET_START:
                case self::T_NC_SNIPPET_START:
                case self::T_CHUNK_START: {
                    $value = $this->parseSnippet($token[Tokenizer::TYPE], $this->brackets[ $token[Tokenizer::TYPE] ]);
                    break;
                }

                case self::T_PLACEHOLDER_START:
                case self::T_FIELD_START: {
                    $value = $this->parseVariable($token[Tokenizer::TYPE], $this->brackets[ $token[Tokenizer::TYPE] ]);
                    break;
                }

                case self::T_LINK_START: {
                    $value = $this->parseMakeUrl();
                    break;
                }

                default: {
                    $value = $token[Tokenizer::VALUE] . $this->iterator->joinUntil(
                        self::T_SNIPPET_START,     self::T_SNIPPET_END, 
                        self::T_NC_SNIPPET_START,  self::T_NC_SNIPPET_END, 
                        self::T_FIELD_START,       self::T_FIELD_END, 
                        self::T_PLACEHOLDER_START, self::T_PLACEHOLDER_END,
                        self::T_CONFIG_START,      self::T_CONFIG_END,
                        self::T_CHUNK_START,       self::T_CHUNK_END, 
                        self::T_LINK_START,        self::T_LINK_END,
                        self::T_QUOTE
                    );

                    if (!$this->inline) {
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

        return '<?php echo ' . implode(', ', $result) . '; ?>';
    }

    /**
     * Make URL
     * 
     * @return string
     */
    private function parseMakeUrl() {
        $input = $this->parseString(true);
        $this->iterator->expect(self::T_LINK_END);
        return sprintf($this->instructions[$this->inline ? 'inline' : 'translated'][self::T_LINK_START], $input);
    }

    /**
     * Get placeholder, document field or setting value
     * 
     * @param  int $openTag  Type of opening tag
     * @param  int $closeTag Type of closing tag
     * @return string
     */
    private function parseVariable($openTag, $closeTag) {
        $name = $this->parseString(true);
        $this->iterator->expect($closeTag);

        if ($this->inline) {
            return sprintf($this->instructions['inline'][$openTag], $name);
        } else {
            return sprintf($this->instructions['translated'][$openTag], $name);
        }
    }

    /**
     * Parse one parameter of snippet, chunk
     * 
     * @return string
     */
    private function parseSnippetParameter() {
        $this->iterator->expect(self::T_AMPERSAND);
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
                $this->inline = true;
                $value = "'" . $this->escape($this->parseString()) . "'";
                $this->inline = false;
            } else {
                $value = $this->parseString();
            }
        }

        $this->iterator->expect(self::T_QUOTE);
        $this->iterator->nextAll(self::T_WHITESPACE);

        if ($this->inline) {
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
        $result = [
            'name'   => $this->iterator->joinUntil(self::T_QUESTION, self::T_WHITESPACE, self::T_AMPERSAND, $closeTag),
            'type'   => $openTag,
            'cached' => $closeTag == self::T_SNIPPET_START,
            'params' => [],
        ];

        $this->iterator->nextAll(self::T_QUESTION, self::T_WHITESPACE);

        do {
            if ($this->iterator->isNext($closeTag)) {
                break;
            } 
            $result['params'][] = $this->parseSnippetParameter();
        } while ($this->iterator->isNext());

        $this->iterator->expect($closeTag);

        if ($this->inline) {
            return sprintf($this->instructions['inline'][ $result['type'] ], $result['name'] . (!empty($result['params']) ? '? ' . implode(' ', $result['params']) : '' ));
        } else {
            return sprintf($this->instructions['translated'][ $result['type'] ], "'" . $result['name'] . "'" . (!empty($result['params']) ? ', [' . implode(', ', $result['params']) . ']' : '' ));
        }
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

}

