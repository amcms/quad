<?php

    namespace Amcms\Quad;

    interface Api {

        /**
         * @param \Amcms\Quad\Quad $parser
         */
        public function setParser($parser);

        /**
         * Runs snippet
         * 
         * @param  string  $name
         * @param  array   $params
         * @param  boolean $cached
         * @return mixed
         */
        public function runSnippet($name, $params = [], $cached = true);

        /**
         * Render chunk
         * 
         * @param  string $name
         * @param  array  $params
         * @return string
         */
        public function parseChunk($name, $params = []);

        /**
         * @param string $name
         * @param string $value
         */
        public function setPlaceholder($name, $value);

        /**
         * If placeholder not exists, method should return null
         * 
         * @param  string $name
         * @return mixed|null
         */
        public function getPlaceholder($name);

        /**
         * @param  string $name Document field name
         * @return string
         */
        public function getField($name);

        /**
         * @param  string $name Option name
         * @return string
         */
        public function getConfig($name);

        /**
         * @param  integer $id Identificator of the document
         * @return string
         */
        public function makeUrl($id);

        /**
         * @param  string $input   Input value
         * @param  array  $filters Array of pairs filter_name => filter_value
         * @return string
         */
        public function applyFilters($input, $filters = []);

    }

