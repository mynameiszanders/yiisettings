<?php

    // Import the Settings extension interface, it is a requirement since classes that extend this class implement it.
    Yii::import('application.extensions.Settings.*');

    /**
     * Settings Extension for Yii Framework
     *
     * A simple extension for setting and retrieving key/value pairs to use as application settings. Each class
     * implements the methods get(), set(), and delete() at a minimum. This abstract class allows each class extending
     * this to cache all key/value pair settings for subsequent requests.
     *
     * To use this extension, I suggest placing all files inside the directory given by the alias
     * "application.extensions.Settings", and then load the desired class as an application component. The following is
     * an example of this with all available options:
     *
     * 'settings' => array(
     *     'class'          => 'application.extensions.Settings.ConfigSettings',
     *     'cacheComponent' => 'cache',
     *     'cacheId'        => 'settingsCache',
     *     'cacheTimeout'   => 3600,
     * ),
     *
     * @author  Zander Baldwin <mynameiszanders@gmail.com>
     * @license MIT/X11 <http://j.mp/mit-license>
     * @version 0.3.1
     * @link    https://github.com/mynameiszanders/yiisettings
     */
    abstract class Settings extends CApplicationComponent
    {

        /**
         * Error Codes
         */
        const ERROR_INVALID_CACHE_COMPONENT = 1;
        const ERROR_INVALID_CACHE_ID        = 2;
        const ERROR_INVALID_NAME            = 3;
        const ERROR_INVALID_CATEGORY        = 4;
        const ERROR_NON_EXISTENT_NAME       = 5;
        const ERROR_NON_EXISTENT_CATEGORY   = 6;
        const ERROR_READ_ONLY               = 7;

        /**
         * Other Constants
         */
        const DEFAULT_SETTINGS_CATEGORY     = 'settings';
        const VALID_LABEL                   = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*';

        /**
         * @var array $settings
         * The class property to hold all the settings loaded into memory.
         */
        protected $settings = array();

        /**
         * @var string $cacheComponent
         * The identifier string that points to the application cache component.
         */
        protected $cacheComponent;

        /**
         * @var string $cacheId
         * The default string to prepend to all category cache IDs.
         */
        protected $cacheId = 'settings';

        /**
         * @var integer $cacheTimeout
         * The default amount of time (in seconds) that settings should be kept in cache for.
         */
        protected $cacheTimeout = 3600;


        /**
         * Set Value to Setting
         *
         * @abstract
         * @access public
         * @param string $name
         * @param mixed $value
         * @return boolean
         */
        public abstract function set($name, $value);

        /**
         * Delete Setting
         *
         * @abstract
         * @access public
         * @param string $name
         * @return boolean
         */
        public abstract function delete($name);


        /**
         * Load Category
         *
         * @abstract
         * @access protected
         * @param string $category
         * @return boolean
         */
        protected abstract function load($category);


        /**
         * Initialisation Method
         *
         * @access public
         * @return void
         */
        public function init()
        {
            // We don't really need to initialise anything, keep this method here just in case we need it in future.
            // All clases that extend this abstract class call this method before doing anything themselves.
        }


        /**
         * Get Setting
         *
         * @access public
         * @param string $name
         * @return mixed
         */
        public function get($name, $default = null)
        {
            // If the setting identifer passed to this method is invalid, throw an exception. Nothing else we can do.
            if(!($setting = $this->split($name))) {
                throw new CException(
                    Yii::t(
                        'settingsext',
                        'Invalid setting name passed to "{{method}}". Setting identifiers must be a valid PHP label for '
                        . 'both the setting category and name, concatenated with a full-stop. The category is optional.',
                        array(
                            '{{method}}' => __METHOD__,
                        )
                    ),
                    self::ERROR_INVALID_NAME
                );
            }
            // If the category that the specified setting belongs to could not be loaded, return the default value.
            if(!$this->load($setting->category)) {
                return $default;
            }
            // If the specified setting could not be located inside its category (which has just been loaded), return
            // the default value.
            if(!isset($this->settings[$setting->category][$setting->name])) {
                return $default;
            }
            // But since we have found it, return the setting value.
            return $this->settings[$setting->category][$setting->name];
        }


        /**
         * Set: Cache Component
         *
         * Set the cache component that this class should use by accepting its string identifier.
         *
         * @access public
         * @param string $id
         * @return void
         */
        public function setCacheComponent($id)
        {
            // Set the identifier string of the cache component this class should use to the class property.
            $this->cacheComponent = $id;
            // Load the cache component identified by the string we just set to the class property.
            $cacheComponent = $this->cacheComponent();
            // Make sure that the component loaded is an object, and is, or extends, the Yii Framework's CCache class.
            if(!is_object($cacheComponent) || !is_a($cacheComponent, 'CCache')) {
                // If the component loaded isn't valid, remove the string identifier for it and throw an exception.
                $this->cacheComponent = null;
                throw new CException(
                    Yii::t(
                        'settingsext',
                        'The cache component identifier string provided in the configuration ({{component}}) is invalid.',
                        array(
                            '{{component}}' => $id,
                        )
                    ),
                    self::ERROR_INVALID_CACHE_COMPONENT
                );
            }
        }


        /**
         * Set: Cache ID
         *
         * @access public
         * @param string $id
         * @return void
         */
        public function setCacheId($id)
        {
            if(!is_string($id) || !preg_match('/^' . self::VALID_LABEL . '$/', $id)) {
                throw new CException(
                    Yii::t(
                        'settingsext',
                        'The cache identifier string provided in the configuration ({{cacheid}}) is invalid.',
                        array(
                            '{{cacheid}}' => $id,
                        )
                    ),
                    self::ERROR_INVALID_CACHE_ID
                );
            }
            else {
                $this->cacheId = $id;
            }
        }


        /**
         * Set: Cache Timeout
         *
         * @access public
         * @param integer $timeout
         * @return void
         */
        public function setCacheTimeout($timeout)
        {
            if(is_int($timeout) && $timeout > 0) {
                $this->cacheTimeout = $timeout;
            }
            else {
                $this->cacheTimeout = 0;
            }
        }


        /**
         * Get Cache Component
         *
         * Return the component of the Yii application identified by the string set in `cacheComponent`.
         *
         * @access protected
         * @return object|null
         */
        protected function cacheComponent()
        {
            return Yii::app()->getComponent($this->cacheComponent);
        }


        /**
         * Save Category to Cache
         *
         * @access public
         * @param string category
         * @return boolean
         */
        protected function cache($category)
        {
            if(isset($this->settings[$category]) && !is_null($this->cacheComponent)) {
                return $this->cacheComponent()->set(
                    $this->cacheId . '.' . $category,
                    $this->settings[$category],
                    $this->cacheTimeout
                );
            }
            return false;
        }


        /**
         * Load Category from Cache
         *
         * @access public
         * @param string $category
         * @return boolean
         */
        protected function loadFromCache($category)
        {
            if(!is_null($this->cacheComponent)) {
                if(isset($this->settings[$category])) {
                    return true;
                }
                $cacheId = $this->cacheId . '.' . $category;
                $settings = $this->cacheComponent()->get($cacheId);
                if(is_array($settings)) {
                    $this->settings[$category] = $settings;
                    return true;
                }
            }
            return false;
        }


        /**
         * Split Setting Identifier
         *
         * @access protected
         * @param string $setting
         * @return null|object
         */
        protected function split($setting)
        {
            if(
                !is_string($setting)
             || !preg_match('/^((?:' . self::VALID_LABEL . '\.)*)(' . self::VALID_LABEL . ')$/', $setting, $matches)
            ) {
                return null;
            }
            return (object) array(
                'category' => $matches[1]
                    ? substr($matches[1], 0, -1)
                    : self::DEFAULT_SETTINGS_CATEGORY,
                'name' => $matches[2],
            );
        }

    }