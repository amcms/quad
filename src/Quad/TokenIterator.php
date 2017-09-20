<?php

    namespace Amcms\Quad;

    use \Nette\Utils\Tokenizer;

    class TokenIterator extends \Nette\Utils\TokenIterator {

        /**
         * Returns next expected token or throws exception.
         * @param  int|string  (optional) desired token type or value
         * @throws UnexpectedTokenException
         */
        public function expect(...$args) {
            if ($token = $this->scan($args, true, true)) {
                return $token[Tokenizer::VALUE];
            }

            $pos = $this->position + 1;

            while (($next = $this->tokens[$pos] ? $this->tokens[$pos] : null) && in_array($next[Tokenizer::TYPE], $this->ignored, true)) {
                $pos++;
            }

            throw new Exceptions\UnexpectedTokenException($args, $next);
        }

        public function expectString($string) {
            $token = $this->expect(Translator::T_STRING);

            if ($token != $string) {
                throw new \Exception("Missed expected token '$string'");
            }

            return $token;
        }

    }

