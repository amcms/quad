<?php

    namespace Amcms\Quad;

    use \Nette\Utils\Tokenizer;

    class UnexpectedTokenException extends \Exception {

        /**
         * Information about expected tokens
         * @var array of array
         */
        private $expectedTokens;

        /**
         * Information about token that not expected
         * @var array
         */
        private $unexpectedToken;

        public function __construct($expected, $unexpected) {
            $this->expectedTokens = $expected;
            $this->unexpectedToken = $unexpected;
            \Exception::__construct("UnexpectedToken '" . $unexpected[Tokenizer::VALUE] . "' at offset " . $unexpected[Tokenizer::OFFSET]);
        }

        /**
         * @return array
         */
        public function getExpectedTokens() {
            return $this->expectedTokens;
        }

        /**
         * Returns offset of unexpected token
         * @return int
         */
        public function getOffset() {
            return $this->unexpectedToken[Tokenizer::OFFSET];
        }

        /**
         * Returns value of unexpected token
         * @return string
         */
        public function getToken() {
            return $this->unexpectedToken[Tokenizer::VALUE];
        }

    }

