<?php

    // Import the Settings extension class, it is a requirement since this class extends it.
    Yii::import('application.extensions.Settings.*');

    /**
     * Configuration Settings Extension for Yii Framework
     *
     * Description...
     *
     * @author  Zander Baldwin <mynameiszanders@gmail.com>
     * @license MIT/X11 <http://j.mp/mit-license>
     */
    class ConfigSettings extends Settings implements ISettings
    {

        /**
         * Initialisation Method
         *
         * @access public
         * @return void
         */
        public function init()
        {
            // Run the initialisation method from the parent Settings class first.
            parent::init();
        }


        /**
         * Set Setting
         *
         * @access public
         * @param string $name
         * @param mixed $value
         * @return boolean
         */
        public function set($name, $value)
        {
            if(!$this->split($name)) {
                throw new CException(
                    Yii::t(
                        'settingsext',
                        'Invalid setting name passed to "{{method}}". Setting identifiers must be a valid PHP label for '
                        . 'both the setting name and category, concatenated with a full-stop. The category is optional.',
                        array(
                            '{{method}}' => __METHOD__,
                        )
                    ),
                    self::ERROR_INVALID_NAME
                );
            }
            throw new CException(
                Yii::t(
                    'settingsext',
                    'Unable to set value to setting "{{setting}}". File-based settings are read-only.',
                    array(
                        '{{setting}}' => $name,
                    )
                ),
                self::ERROR_READ_ONLY
            );
        }

        /**
         * Delete Setting
         *
         * @access public
         * @param string $name
         * @return boolean
         */
        public function delete($name)
        {
            if(!$this->split($name)) {
                throw new CException(
                    Yii::t(
                        'settingsext',
                        'Invalid setting name passed to "{{method}}". Setting identifiers must be a valid PHP label for '
                        . 'both the setting name and category, concatenated with a full-stop. The category is optional.',
                        array(
                            '{{method}}' => __METHOD__,
                        )
                    ),
                    self::ERROR_INVALID_NAME
                );
            }
            throw new CException(
                Yii::t(
                    'settingsext',
                    'Unable to delete setting "{{setting}}". File-based settings are read-only.',
                    array(
                        '{{setting}}' => $name,
                    )
                ),
                self::ERROR_READ_ONLY
            );
        }

        /**
         * Load Category
         *
         * @access protected
         * @param string $category
         * @return boolean
         */
        protected function load($category)
        {
            if(!$this->split($category)) {
                throw new CException(
                    Yii::t(
                        'settingsext',
                        'Unable to load settings from an invalid category name, "{{category}}".',
                        array(
                            '{{category}}' => $category,
                        )
                    ),
                    self::ERROR_INVALID_CATEGORY
                );
            }
            if(!isset($this->settings[$category]) && !$this->loadFromCache($category)) {
                // Determine the path to the configuration file that holds the settings for this category.
                $category_file = Yii::getPathOfAlias('application.config')
                    . DIRECTORY_SEPARATOR
                    . str_replace('.', DIRECTORY_SEPARATOR, $category)
                    . '.php';
                // If the file does not exist, or it isn't readable, return false as we can't access the settings.
                if(!file_exists($category_file) || !is_readable($category_file)) {
                    return false;
                }
                // Include the configuration file in an attempt to load the settings.
                $settings = require $category_file;
                if(!is_array($settings)) {
                    throw new CException(
                        Yii::t(
                            'settingsext',
                            'The category specified, "{{category}}", does not exist.',
                            array(
                                '{{category}}' => $category,
                            )
                        ),
                        self::ERROR_NON_EXISTENT_CATEGORY
                    );
                }
                // We managed to get an array returned from the configuration file. Add it to the settings property.
                $this->settings[$category] = $settings;
                // Now that settings for this category have been retrieved, save them to cache for subsequent requests.
                $this->cache($category);
            }
            return true;
        }

    }