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
 * @version 1.00
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
define ('ALPHDB_VERSION', 1.00);

/**
 * The default path all data will be stored in.
 */
define ('ALPHDB_DEFAULT_PATH', './alphdb-data/');

/**
 * The default file extension for AlphDB files.
 */
define ('ALPHDB_DEFAULT_EXT', '.adb');

/**
 * Default extension for AlphaDB Table files.
 * This must be different to ALPHDB_DEFAULT_EXT
 */
define ('ALPHDB_DEFAULT_EXT2', '.atbl');

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
        
        if (file_exists($this->file)) {
            if (is_readable($this->file)) {
                $db_base = json_decode(file_get_contents($this->file));
                if ($this->authenticate($db_base)) {
                    $this->db_base = $db_base;
                    return $this->loaded = true;
                }
                throw new Exception('Could not logon to database. User/Password wrong.');
                return false;
            } 
            throw new Exception('Database could not be read. Unreadable.');
            return false;
        }
        
        if (!is_dir($this->db_path)) {
            if (!mkdir($this->db_path)) {
                throw new Exception('Database could not be created. Directory could not be written.');
            }
        }
        $new = json_encode(array ('name' => $this->db_crypt_name, 'user' => $this->db_nick, 'pass' => $this->db_pass, 'alphdb_version' => ALPHDB_VERSION));
        if (file_put_contents($this->file, $new)) {
            return $this->load();
        }
        throw new Exception('Database could not be created. Unwriteable.');
        return false;
    }
    
    /**
     * @method boolean Checks if $db has access to our database.
     */
    private function authenticate ($db) {
        return (($this->db_nick == $db->user) && ($this->db_pass == $db->pass));
    }
    
    /**
     * @method boolean Checks if AlphDB is loaded.
     */
    public function is_loaded () {
        return $this->loaded;
    }
    
    /**
     * @method object Selects a table. Note that this is stackable, use ::fetch() to retrieve it afterwards.
     */
    public function select ($table, $passive = false) {
        if (!$this->loaded)
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
     * @method object Sort your ::select() call. E.g: $db->select("my_table")->where(array("column1", "value1"))
     */
    public function where ($condition) {
        if (!$this->loaded)
            return $this;
        if ($this->selected_last == -1)
            return $this;
        $new = array();
        $table = $this->fetch();
        foreach ($table->rows as $row) {
            foreach ($row as $column) {
                foreach ($condition as $criteria) {
                    $crit = $criteria[0];
                    if (@$column->{$crit} == $criteria[1])
                        $new[] = $row;
                }
            }
        }
        $table->rows = array_unique($new);
        return $this->selected[$this->selected_last] = $table;
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
        return $this->loaded ? @$this->selected[($table === null ? $this->selected_last : $table)] : $this;
    }
    
    /**
     * @method object Creates a table. Parameter1 is the tablename, the rest columns or an array. ::create('users', 'strID', 'strPass')
     */
    public function create () {
        if (!$this->loaded)
            return $this;
        $args = func_get_args();
        if ($args < 2) {
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
        if (!$this->loaded)
            return $this;
        $table = $this->mkTbl($table);
        $tbd = $this->db_path . $table . ALPHDB_DEFAULT_EXT2;
        $this->select($table, true);
        $tb = $this->selected[$table];
        $new = array();
        foreach ($tb->struct as $column)
            $new[] = array_key_exists($column, $row) ? array($column => $row[$column]) : array($column => null);
        $tb->rows[] = $new;
        //$tb->rows = array_unique($tb->rows);
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
        if (!$this->loaded)
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
        if (!$this->loaded)
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
        if (!$this->loaded)
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
        if (!$this->loaded)
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