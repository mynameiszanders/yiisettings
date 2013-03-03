<?php

    // Import the Settings extension class, it is a requirement since this class extends it.
    Yii::import('application.extensions.Settings.*');

    /**
     * Database Settings Extension for Yii Framework
     *
     * Description...
     *
     * @author  Zander Baldwin <mynameiszanders@gmail.com>
     * @license MIT/X11 <http://j.mp/mit-license>
     */
    class DbSettings extends Settings implements ISettings
    {

        const ERROR_INVALID_DB_COMPONENT    = 8;
        const ERROR_INVALID_DB_TABLE        = 9;

        /**
         * @var string $dbComponent
         */
        protected $dbComponent;

        /**
         * @var string $tableName
         */
        protected $tableName = '{{settings}}';

        /**
         * @var boolean $createTable
         */
        protected $createTable = false;


        /**
         * Set: DB Component
         *
         * @access public
         * @param string $id
         * @return void
         */
        public function setDbComponent($id)
        {
            $this->dbComponent = $id;
            $dbComponent = $this->dbComponent();
            if(!is_object($dbComponent) || !is_a($dbComponent, 'CDbConnection')) {
                $this->dbComponent = null;
                throw new CException(
                    Yii::t(
                        'settingsext',
                        'The database component identifier string provided in the configuration ({{component}}) is invalid.',
                        array(
                            '{{component}}' => $id,
                        )
                    ),
                    self::ERROR_INVALID_DB_COMPONENT
                );
            }
        }


        /**
         * Set: Table Name
         *
         * @access public
         * @param string $table
         * @return void
         */
        public function setTableName($tableName)
        {
            $this->tableName = $tableName;
        }


        /**
         * Set: Create Table
         *
         * @access public
         * @param boolean $create
         * @return void
         */
        public function setCreateTable($create)
        {
            $this->createTable = (bool) $create;
        }


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
            if($this->createTable) {
                if(!$this->createTable()) {
                    throw new CException(
                        Yii::t(
                            'settingsext',
                            'Invalid table specified in the application configuration.'
                        ),
                        self::ERROR_INVALID_DB_TABLE
                    );
                }
            }
        }


        /**
         * Set Database Setting
         *
         * @access public
         * @param string $name
         * @param mixed $value
         * @return boolean
         */
        public function set($name, $value)
        {
            // Create a static variables to house the database query commands. We may be calling it several times per
            // request, but it only needs to be defined once.
            static $create;
            static $update;
            // Obviously, if an invalid setting has been referenced, there is no point in continuing. Let the user know
            // about this by letting Yii display an exception message.
            if(!($setting = $this->split($name))) {
                throw new CException(
                    Yii::t(
                        'settingsext',
                        'Invalid setting name.',
                        array()
                    ),
                    self::ERROR_INVALID_NAME
                );
            }
            // If the database component could not be loaded there is no point in continuing either.
            if(is_null($this->dbComponent)) {
                throw new CException(
                    Yii::t(
                        'settingsext',
                        'The database component identifier string provided in the configuration is invalid.'
                    ),
                    self::ERROR_INVALID_DB_COMPONENT
                );
            }

            // Before we can set it, load the category settings into memory so that we can determine whether we are
            // creating the setting or simply updating it.
            $this->load($setting->category);
            // If the setting already exists, we need to update it. Make sure the database query command has been
            // defined and assign the update command to the $command variable (that's the handle we shall be using).
            if(isset($this->settings[$setting->category][$setting->name])) {
                if(is_null($update)) {
                    $sql = 'UPDATE `' . $this->tableName . '` SET `value` = :value WHERE `category` = :category AND `name` = :name;';
                    $update = $this->dbComponent()->createCommand($sql);
                }
                $command = $update;
            }
            // If the setting does not already exist, we need to create it. Make sure the database query command has
            // been defined and assign the create command to the $command variable (that's the handle we shall be
            // using).
            else {
                if(is_null($create)) {
                    $sql = 'INSERT INTO `' . $this->tableName . '` (`category`, `name`, `value`) VALUES (:category, :name, :value);';
                    $create = $this->dbComponent()->createCommand($sql);
                }
                $command = $create;
            }
            // Execute the query to save the setting value to the database.
            $result = $command->execute(array(
                ':category' => $setting->category,
                ':name' => $setting->name,
                ':value' => serialize($value),
            ));
            // If the setting was successfully entered into the database, save the new value into memory and cache the
            // category for subsequent requests.
            if($result) {
                $this->settings[$setting->category][$setting->name] = $value;
                $this->cache($setting->category);
            }
            return $result;
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
            // Create a static variable to house the database query command. We may be calling it several times per
            // request, but it only needs to be defined once.
            static $command;
            // Obviously, if an invalid setting has been referenced, there is no point in continuing. Let the user know
            // about this by letting Yii display an exception message.
            if(!($setting = $this->split($name))) {
                throw new CException(
                    Yii::t(
                        'settingsext',
                        'Invalid setting name',
                        array()
                    ),
                    self::ERROR_INVALID_NAME
                );
            }
            // If the database component could not be loaded there is no point in continuing either.
            if(is_null($this->dbComponent)) {
                throw new CException(
                    Yii::t(
                        'settingsext',
                        'The database component identifier string provided in the configuration is invalid.'
                    ),
                    self::ERROR_INVALID_DB_COMPONENT
                );
            }
            if(is_null($command)) {
                $sql = 'DELETE FROM `'.$this->tableName.'`
                        WHERE
                            `'.$this->tableName.'`.`name` = :name
                            AND
                            `'.$this->tableName.'`.`category` = :category
                        LIMIT 1;';
                $command = $this->dbComponent()->createCommand($sql);
            }
            // First of all, load the category into memory so that we can use it.
            $this->load($setting->category);
            // Execute the database query result, deleting the setting in question.
            $deleted = $command->execute(array(
                ':category' => $setting->category,
                ':name' => $setting->name,
            ));
            // If the setting was successfully deleted from the database, delete it from memory and recache the category
            // so that it isn't able to be used again on subsequent requests.
            if($deleted) {
                unset($this->settings[$setting->category][$setting->name]);
                $this->cache($setting->category);
            }
            // Return the database query result.
            return $deleted;
        }


        /**
         * Load Category from Database
         *
         * @access public
         * @param string $category
         * @return boolean
         */
        public function load($category)
        {
            // Create a static variable to house the database query command. We may be calling it several times per
            // request, but it only needs to be defined once.
            static $command;
            // Obviously, if an invalid setting has been referenced, there is no point in continuing. Let the user know
            // about this by letting Yii display an exception message.
            if(!$this->split($category)) {
                throw new CException(
                    Yii::t(
                        'settingsext',
                        'Invalid category name',
                        array()
                    ),
                    self::ERROR_INVALID_NAME
                );
            }
            // If the database component could not be loaded there is no point in continuing either.
            if(is_null($this->dbComponent)) {
                throw new CException(
                    Yii::t(
                        'settingsext',
                        'The database component identifier string provided in the configuration is invalid.'
                    ),
                    self::ERROR_INVALID_DB_COMPONENT
                );
            }
            if(is_null($command)) {
                $sql = 'SELECT `category`, `name`, `value`
                        FROM `' . $this->tableName . '`
                        WHERE `' . $this->tableName . '`.`category` = :category;';
                $command = $this->dbComponent()->createCommand($sql);
            }
            // Firstly, does the settings category already exist in memory, and if it doesn't, can it be loaded from the
            // cache. If it cannot be loaded from cache, then we should load the category settings from the database.
            if(!isset($this->settings[$category]) && !$this->loadFromCache($category)) {
                // Perform a query against the database, passing the category as a parameter.
                $results = $command->query(array(
                    ':category' => $category,
                ));
                // If no settings under that category exist in the database, return false.
                if($results->count() == 0) {
                    return false;
                }
                // Initialise a temporary array to store the settings we fetch from the database.
                $settings = array();
                // Iterate throught the database results, adding each setting to the temporary array and unserialising
                // its value.
                while($result = $results->read()) {
                    $settings[$result['name']] = unserialize($result['value']);
                }
                // Add the settings into memory, in the settings class property under an element named after the
                // category they belong to.
                $this->settings[$category] = $settings;
                // Now that the settings have been fetched from the database, cache the results for subsequent requests.
                $this->cache($category);
            }
            return true;
        }


        /**
         * Get Database Component
         *
         * Return the component of the Yii application identified by the string set in `dbComponent`.
         *
         * @access protected
         * @return object|null
         */
        protected function dbComponent()
        {
            return Yii::app()->getComponent($this->dbComponent);
        }


        /**
         * Create Database Table
         *
         * @access protected
         * @return void
         */
        protected function createTable()
        {
            // Create a static variable to house the result of the database query in.
            static $result;
            // If the table name is not a string, we cannot use it.
            if(!is_string($this->tableName)) {
                $result = false;
            }
            // If the result is null it means that the database query has yet to be run, so we should do it now.
            // If the result is not null it means that the database query has already run, so we can skip this bit.
            if(is_null($result)) {
                // Make sure that a valid database connection exists before we attempt to run any queries.
                if(!is_null($this->dbComponent)) {
                    $sql = 'CREATE TABLE IF NOT EXISTS `' . $this->tableName . '` (
                                `id`    INT UNSIGNED NOT NULL AUTO_INCREMENT,
                                `name`  VARCHAR(64) NOT NULL,
                                `category` VARCHAR(255) NOT NULL,
                                `value` TEXT,
                                PRIMARY KEY (`id`),
                                UNIQUE  KEY `identifier`(`name`, `category`)
                            )
                            ENGINE = InnoDB
                            AUTO_INCREMENT = 1
                            DEFAULT CHARSET = utf8;';
                    $command = $this->dbComponent()->createCommand($sql);
                    $result = true;
                    try {
                        $command->execute();
                    }
                    // If an exception was thrown it means there was an error with the SQL syntax, and the table was not
                    // created. Save the result of this for subsequence calls in the same request.
                    catch(Exception $e) {
                        $result = false;
                    }
                }
                // If no database connection exists, we can't even run the query to obtain the result. Set to false
                // (there is nothing we can do).
                else {
                    $result = false;
                }
            }
            // We are at the end of this method the result variable has been set and we don't care how. Just return it.
            return $result;
        }

    }