<?php
/**~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 * AlphDB
 * This is an emulated database working on any server where
 * php has read/write access. Basically it was written for
 * alpha tests whenever a real db connection could not be
 * established, yet, for whatever reason(s).
 * You should not use this for publically accessible
 * projects, this is not secure!
 * Please, also note that this class is still
 * very experiamental and may sometimes behave
 * weirdly for I have not sufficiently tested it.
 * Anyways, I hope it's helpful.
 * Oh, btw. This is a stackable class. :)
 *
 * @version 1.02
 * @author Fabian Schneider <Fabi.Schn@gmail.com>
 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 */

/**
 * Define our public constant.
 */
define ('ALPHDB_LOADED', true);

/**
 * AlphDB Version of this File
 */
define ('ALPHDB_VERSION', 1.02);

/**
 * The default path all data will be stored in.
 */
define ('ALPHDB_DEFAULT_PATH', './alphdb-data/');

/**
 * The default file extension for AlphDB files.
 */
define ('ALPHDB_DEFAULT_EXT', '.adb');

/**
 * Default extension for AlphDB Table files.
 * This must be different to ALPHDB_DEFAULT_EXT
 */
define ('ALPHDB_DEFAULT_EXT2', '.atbl');

/**
 * Default extension for AlphDB User files.
 * This must be unique, again.
 */
define ('ALPHDB_DEFAULT_EXT3', '.aus');

/**
 * Default extension for AlphDB's Group files.
 * As always, this must be unique.
 */
define ('ALPHDB_DEFAULT_EXT4', '.agrp');

/**
 * This is the default group that is automatically
 * assigned to users that aren't part of a
 * set group.
 */
define ('ALPHDB_DEFAULT_GROUP', 'casual');

/**
 * Our constant for read-access.
 */
define ('ALPHDB_READ', 1);

/**
 * Constant for write-access.
 */
define ('ALPHDB_WRITE', 2);

/**
 * Remove's access-constant.
 */
define ('ALPHDB_REMOVE', 3);

/**
 * Our constant for drop-access.
 */
define ('ALPHDB_DROP', 4);

/**
 * Delete's access-constant.
 */
define ('ALPHDB_DELETE', 5);

/**
 * Constant for grant-access.
 */
define ('ALPHDB_GRANT', 6);

/**
 * Our constant for revoke-access.
 */
define ('ALPHDB_REVOKE', 7);

/**
 * Constant for adding users-access.
 */
define ('ALPHDB_USER_ADD', 8);

/**
 * Removing User-access's constant.
 */
define ('ALPHDB_USER_REMOVE', 9);

class AlphDB {
    /**
     * @var boolean Nothing will work without this. See ::load()
     */
    private $loaded = false;
    
    /**
     * @var string The path to our data directory. Defaultly ALPHDB_DEFAULT_PATH
     */
    private $path;
    
    /**
     * @var string Path to our database directory. See ::load()
     */
    private $db_path;
    
    /**
     * @var string Path to the database file.
     */
    private $file;
    
    /**
     * @var string The Database's name.
     */
    private $db_name;
    
    /**
     * @var string Crypted string of the Database's Name.
     */
    private $db_crypt_name;
    
    /**
     * @var string Crypted Nickname used to logon to the database.
     */
    private $db_nick;
    
    /**
     * @var string Crypted Password used to logon to the database.
     */
    private $db_pass;
    
    /**
     * @var object The database's object. See ::load()
     */
    private $db_base;
    
    /**
     * @var array Array containing all loaded tables. See ::select()
     */
    private $selected = array();
    
    /**
     * @var string The name of the last table loaded. See ::select() and ::fetch()
     */
    private $selected_last = -1;
    
    /**
     * @var string The path to the user file (for logins).
     */
    private $user_path;
    
    /**
     * @var string Path to the user directory.
     */
    private $users_path;
    
    /**
     * @var string The path to the group directory.
     */
    private $groups_path;
    
    /**
     * @var array An array filled with privileges of current user.
     */
    private $user_privileges = array(
                                     ALPHDB_READ => false,
                                     ALPHDB_WRITE => false,
                                     ALPHDB_REMOVE => false,
                                     ALPHDB_DROP => false,
                                     ALPHDB_DELETE => false,
                                     ALPHDB_GRANT => false,
                                     ALPHDB_REVOKE => false,
                                     ALPHDB_USER_ADD => false,
                                     ALPHDB_USER_REMOVE => false
                                    );
    
    
    /**
     * @method void The constructor will automatically trigger ::load()
     */
    public function AlphDB ($db_name, $db_nick = null, $db_pass = null, $path = ALPHDB_DEFAULT_PATH) {
        $this->path = $path;
        $this->db_name = $db_name;
        $this->db_crypt_name = md5($this->db_name);
        $this->db_nick = md5($db_nick);
        $this->db_pass = md5($db_pass);
        
        $this->load();
    }
    
    /**
     * @method boolean Loads or creates a database, depending on whether or not it exists.
     */
    private function load () {
        $this->db_path = $this->path . $this->db_crypt_name . DIRECTORY_SEPARATOR;
        $this->file = $this->db_path . $this->db_crypt_name . ALPHDB_DEFAULT_EXT;
        $this->users_path = $this->db_path . 'users' . DIRECTORY_SEPARATOR;
        $this->groups_path => $this->db_path . 'groups' . DIRECTORY_SEPARATOR;
        
        if (file_exists($this->file)) {
            if (is_readable($this->file)) {
                $db_base = json_decode(file_get_contents($this->file));
                if ($this->authenticate($db_base)) {
                    $this->grant_rights(true);
                    $this->user_path = $this->db_base = $db_base;
                    return $this->loaded = true;
                } else {
                    $this->user_path = $this->db_path  . 'users' . DIRECTORY_SEPARATOR . $this->db_nick . ALPHDB_DEFAULT_EXT3;
                    if (file_exists($this->user_path)) {
                        if (is_readable($this->user_path)) {
                            $auth = json_decode(file_get_contents($this->user_path));
                            if ($this->authenticate($auth)) {
                                $this->grant_rights($auth);
                                return $this->loaded = true;
                            }
                        }
                        throw new Exception('Could not read User file.');
                    }
                }
                throw new Exception('Could not logon to database. User/Password wrong.');
            } 
            throw new Exception('Database could not be read. Unreadable.');
        }
        
        if (!is_dir($this->db_path)) {
            if (!mkdir($this->db_path) || !mkdir($this->groups_path) || !mkdir($this->users_path)) {
                throw new Exception('Database could not be created. Directories could not be written.');
            }
        }
        $new = json_encode(array ('name' => $this->db_crypt_name, 'user' => $this->db_nick, 'pass' => $this->db_pass, 'alphdb_version' => ALPHDB_VERSION));
        if (file_put_contents($this->file, $new)) {
            return $this->load();
        }
        throw new Exception('Database could not be created. Unwriteable.');
    }
    
    /**
     * @method boolean Checks if $db has access to our database.
     */
    private function authenticate ($db) {
        return (($this->db_nick == $db->user) && ($this->db_pass == $db->pass));
    }
    
    /**
     * @method object Creates $id with $pass and $access. $access can be either a group as string or an array of rights.
     */
    public function addUser ($id, $pass, $access) {
        if (!$this->loaded || !$this->hasRight(ALPHDB_USER_ADD))
            return $this;
        $hid = md5($id);
        $hpass = md5($pass);
        $file = $this->users_path . $hid . ALPHDB_DEFAULT_EXT3;
        if (file_exists($file))
            throw new Exception("User '$id' does already exist in current database.");
        $haccess = array();
        if (!is_array($access))
            $group = $this->loadGroup($access);
        foreach ($this->user_privileges as $priv => $access) {
            if (is_array($access)) {
                $haccess[$priv] = empty($access[$priv]) ? false : $access[$priv];
            } else {
                $haccess[$priv] = empty($group->rights->{$priv}) ? false : $group->rights->{$priv};
            }
        }
        $user = array('user' => $hid, 'pass' => $hpass, 'group' => (!is_array($access) ? $access : ALPHDB_DEFAULT_GROUP), 'rights' => $haccess);
        if (!file_put_contents($file, json_encode($user)))
            throw new Exception("User '$id' could not be created. Unwriteable.");
        return $this;
    }
    
    /**
     * @method object Removes a user with $id.
     */
    public function removeUser ($id) {
        if (!$this->loaded || !$this->hasRight(ALPHDB_USER_REMOVE))
            return $this;
        $hid = md5($id);
        $file = $this->users_path . $hid . ALPHDB_DEFAULT_EXT3;
        if (!file_exists($file))
            throw new Exception("User $id does not exist in the first place.");
        if (!unlink($file))
            throw new Exception("User $id could not be deleted. File could not be unlinked.");
        return $this;
    }
    
    /**
     * @method object Returns Group $id as an object.
     */
    public function loadGroup ($id) {
        if (!$this->loaded)
            return $this;
        $hid = md5($id);
        $file = $this->groups_path . $hid . ALPHDB_DEFAULT_EXT4;
        if (!file_exists($file))
            throw new Exception("Group $id does not exist in currently selected database.");
        if (!$group = file_get_contents($file))
            throw new Exception("Could not read group $id.");
        return json_decode($group);
    }
    
    /**
     * @method object Grants rights to current user (does not write!).
     */
    private function grant_rights ($auth) {
        if ($auth === true) {
            foreach ($this->user_privileges as $priv => $access) {
                $this->user_privileges[$priv] = true;
            }
        } else {
            foreach ($this->user_privileges as $priv => $access) {
                $this->user_privileges[$priv] = (bool) @$auth->rights->{$priv};
            }
        }
        return $this;
    }
    
    /**
     * @method boolean Checks if current user has $rights.
     */
    public function hasRight ($type) {
        return @$this->user_privileges[$type];
    }
    
    /**
     * @method boolean See ::hasRight, accepts multiple parameters, however.
     */
    public function hasRights () {
        $rights = func_get_args();
        foreach ($rights as $right) {
            if (@!$this->user_privileges[$right])
                return false;
        }
        return true;
    }
    
    /**
     * @method mixed See ::hasRight(), this is private and throws exceptions.
     */
    private function _hasRight ($type) {
        if (@!$this->user_privileges[$type]) {
            throw new Exception("Action requires #$type-Rights.");
        }
        return true;
    }
    
    /**
     * @method mixed See ::_hasRight, accepts multiple parameters, however.
     */
    private function _hasRights () {
        $rights = func_get_args();
        foreach ($rights as $right) {
            if (@!$this->user_privileges[$right])
                throw new Exception("Action requires #$right-Rights.");
        }
        return true;
    }
    
    /**
     * @method boolean Checks if AlphDB is loaded.
     */
    public function is_loaded () {
        return $this->loaded;
    }
    
    /**
     * @method boolean Synonym for ::is_loaded()
     */
    public function isLoaded () {
        return $this->loaded;
    }
    
    /**
     * @method object Selects a table. Note that this is stackable, use ::fetch() to retrieve it afterwards.
     */
    public function select ($table, $passive = false) {
        if (!$this->loaded || !$this->hasRight(ALPHDB_READ))
            return $this;
        $table = $this->mkTbl($table);
        $tb = $this->db_path . $table . ALPHDB_DEFAULT_EXT2;
        if (file_exists($tb)) {
            if (is_readable($tb)) {
                if (!$passive)
                    $this->selected_last = $table;
                $this->selected[$table] = json_decode(file_get_contents($tb));
                return $this;
            }
            throw new Exception("'$table' is unreadable.");
            return $this;
        }
        throw new Exception("'$table' does not exist.");
        return $this;
    }
    
    /**
     * @method object Sort your ::select() call. E.g: $db->select("my_table")->where(array("column1", "value1")) Note that you may also use parameters without arrays. Such as: $db->select("my_table")->where("my_column", "my_value"); Note that this uses ORs. Use consecutive ::where()s for ANDs.
     */
    public function where () {
        if (!$this->loaded || $this->selected_last == -1 || !$this->hasRight(ALPHDB_READ))
            return $this;
        $new = array();
        $table = $this->fetch();
        $args = func_get_args();
        if (count($args) > 1) {
            $condition = array();
            for ($n = 0; $n < count($args); $n+=2) {
                $condition[] = array($args[$n], $args[$n+1]);
            }
        } else if (count($args) == 1) {
            if (is_array($args[0])) {
                $condition = $args[0];
            } else {
                return $this->whereConst($args[0]);
            }
        } else {
            return null;
        }
        foreach ($table->rows as $row) {
            foreach ($row as $column) {
                foreach ($condition as $criteria) {
                    $crit = $criteria[0];
                    if (@$column->{$crit} == $criteria[1])
                        $new[] = $row;
                }
            }
        }
        $table->rows = $new;
        $this->selected[$this->selected_last] = $table;
        return $this;
    }
    
    /**
     * @method object Sort your ::select() call by $constraints. Note: this also uses ORs. Make consecutive ::wereConst()s for ANDs.
     */
    public function whereConst ($constraints) {
        if (!$this->loaded || $this->selected_last == -1 || !$this->hasRight(ALPHDB_READ))
            return $this;
        $new = array();
        $table = $this->fetch();
        $temp = explode(';', $constraints);
        $matches = array();
        foreach ($temp as $condition) {
            preg_match('/(.+)[\t| ]+(IN|<|=|>|!)[\t| ]+([0-9]+|\[.+\]|.+)/', $condition, $matches[]);
        }
        foreach ($matches as $match) {
            if ($match[2] == 'IN') {
                preg_match('/(?:([0-9]+|".+"|\'.+\'))/', substr($match[3], 1, -1), $tempm);
                print_r($tempm);
            }
        }
        foreach ($table->rows as $row) {
            foreach ($row as $column) {
                
            }
        }
        return $this;
    }
    
    /**
     * @method int Returns the number of rows. 0 if there are none, useful after ::where()
     */
    public function hasRows () {
        if (!$this->loaded || $this->selected_last == -1 || !$this->hasRight(ALPHDB_READ))
            return $this;
        $table = $this->fetch();
        return count($table->rows);
    }
    
    /**
     * @method string Makes sure that $table contains valid characters.
     */
    private function mkTbl ($table) {
        return preg_replace('/[^a-z0-9_\-]/i', '', $table);
    }
    
    /**
     * @method object Returns the last fetched table or $table. Cannot be called after ::select()
     */
    public function fetch ($table = null) {
        if (!$this->hasRight(ALPHDB_READ))
            return $this;
        return $this->loaded ? @$this->selected[($table === null ? $this->selected_last : $table)] : $this;
    }
    
    /**
     * @method boolean Checks whether $version is supported. (i.e. equal or older)
     */
    public function checkVersion ($version) {
        return $version <= ALPHDB_VERSION;
    }
    
    /**
     * @method object Creates a table. Parameter1 is the tablename, the rest columns or an array. ::create('users', 'strID', 'strPass')
     */
    public function create () {
        if (!$this->loaded || !$this->hasRight(ALPHDB_WRITE))
            return $this;
        $args = func_get_args();
        if (count($args) < 2) {
            throw new Exception('Tables must consist of at least one column.');
            return $this;
        }
        $table = $this->mkTbl($args[0]);
        array_shift($args);
        $tb = $this->db_path . $table . ALPHDB_DEFAULT_EXT2;
        if (!file_exists($tb)) {
            $new = array('name' => $table, 'struct' => $args, 'rows' => array());
            if (is_array($args[0])) {
                $new['struct'] = $args[0];
            }
            if (!file_put_contents($tb, json_encode($new))) {
                throw new Exception("'$table' could not be created. Unwriteable.");
            }
            return $this;
        }
        throw new Exception("'$table' does already exist.");
        return $this;
    }
    
    /**
     * @method object Inserts $row into $table. Note that this does allow duplicates!
     */
    public function insert ($table, $row) {
        if (!$this->loaded || !$this->hasRight(ALPHDB_WRITE))
            return $this;
        $table = $this->mkTbl($table);
        $tbd = $this->db_path . $table . ALPHDB_DEFAULT_EXT2;
        $this->select($table, true);
        $tb = $this->selected[$table];
        $new = array();
        foreach ($tb->struct as $column)
            $new[] = array_key_exists($column, $row) ? array($column => $row[$column]) : array($column => null);
        $tb->rows[] = $new;
        $new = json_encode($tb);
        if (!file_put_contents($tbd, $new))
            throw new Exception("Failed writing '$table' while inserting a row.");
        else
            $this->select($table, true);
        return $this;
    }
    
    /**
     * @method object Truncates (removes all rows from) $table.
     */
    public function truncate ($table) {
        if (!$this->loaded || !$this->hasRight(ALPHDB_REMOVE))
            return $this;
        $table = $this->mkTbl($table);
        $tbd = $this->db_path . $table . ALPHDB_DEFAULT_EXT2;
        $this->select($table, true);
        $tb = $this->selected[$table];
        $tb->rows = array();
        if (!file_put_contents($tbd, json_encode($tb)))
            throw new Exception("Failed writing '$table' while truncating it.");
        else
            $this->select($table, true);
        return $this;
    }
    
    /**
     * @method object Removes all rows fitting $critera (array(array('Column', 'value'), array('Column2', 'value'))). Note that criterias are ORs.
     */
    public function remove ($table, $criteria) {
        if (!$this->loaded || !$this->hasRight(ALPHDB_REMOVE))
            return $this;
        $table = $this->mkTbl($table);
        $tbd = $this->db_path . $table . ALPHDB_DEFAULT_EXT2;
        $this->select($table, true);
        $tb = $this->selected[$table];
        $n = 0;
        foreach ($tb->rows as $row) {
            foreach ($row as $column) {
                foreach ($criteria as $c) {
                    $crit = $c[0];
                    if (@$column->{$crit} == $c[1]) {
                        unset($tb->rows[$n]);
                        continue;
                    }
                }
            }
            $n++;
        }
        if (!file_put_contents($tbd, json_encode($tb)))
            throw new Exception("Failed writing '$table' while removing rows.");
        else
            $this->select($table, true);
        return $this;
    }
    
    /**
     * @method object Completely drops (deletes) $table.
     */
    public function drop ($table) {
        if (!$this->loaded || !$this->hasRight(ALPHDB_DROP))
            return $this;
        $table = $this->mkTbl($table);
        $tb = $this->db_path . $table . ALPHDB_DEFAULT_EXT2;
        if (file_exists($tb))
            if (!unlink($tb))
                throw new Exception("Could not drop '$table'. Inunlinkable.");
        return $this;
    }
    
    /**
     * @method object Deletes the database itself.
     */
    public function delete () {
        if (!$this->loaded || !$this->hasRight(ALPHDB_DELETE))
            return $this;
        $dir = $this->db_path;
        $this->rrmdir($dir);
        $this->loaded = false;
    }
    
    /**
     * @method object Remove an entire $dir recursively.
     */
    private function rrmdir ($dir) {
        foreach (glob($dir . '/*') as $file) {
            if(is_dir($file))
                $this->rrmdir($file);
            else
                unlink($file);
        }
        rmdir($dir);
        return $this;
    }
}
?>