<?php

    /**
     * Settings Interface
     *
     * @author  Zander Baldwin <mynameiszanders@gmail.com>
     * @license MIT/X11 <http://j.mp/mit-license>
     * @version 0.3
     * @link    https://github.com/mynameiszanders/yiisettings
     */
    interface ISettings
    {

        /**
         * Get
         *
         * Description...
         *
         * @access public
         * @param string $name
         * @return mixed
         */
        public function get($name);

        /**
         * Set
         *
         * Description...
         *
         * @access public
         * @param string $name
         * @param mixed $value
         * @return void
         */
        public function set($name, $value);

        /**
         * Delete
         *
         * Description...
         *
         * @access public
         * @param string $name
         * @return boolean
         */
        public function delete($name);

    }