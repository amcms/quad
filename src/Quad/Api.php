<?php

    namespace Amcms\Quad;

    interface Api {

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

        public function setPlaceholder($name, $value);

        /**
         * Returns placeholder value.
         * If placeholder not exists, method should return null
         * 
         * @param  string $name
         * @return mixed|null
         */
        public function getPlaceholder($name);

        public function getField($name);

        public function getConfig($name);

        public function makeUrl($id);

        public function applyFilter($input, $name, $value);

    }

