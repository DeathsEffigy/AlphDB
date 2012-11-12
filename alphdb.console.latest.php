<?php
/**~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 * AlphConsole
 * This is the raw console file.
 * Please, configure ALPHDB_FILE after your
 * needs. Also note that the .cmd file must
 * have correct paths (for php and the console
 * file (this file)).
 * If these are all okay, enjoy the console.
 * Once again keep in mind that this was not
 * coded for publical use, so please keep these files
 * out of your public repository.
 * 
 * @version 1.01
 * @author Fabian Schneider <Fabi.Schn@gmail.com>
 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 */

/**
 * Path to your AlphDB Class file.
 */
define ('ALPHDB_FILE', './alphdb.class.latest.php');

/**
 * Your console's newline command.
 */
define ('ALPHDB_NEW_LINE', "\n");

class AlphConsole {
    /**
     * @var int Number of arguments supplied to the console.
     */
    private $argc;
    
    /**
     * @var array An array of all arguments supplied to the console.
     */
    private $argv;
    
    /**
     * @var object The AlphDB handle.
     */
    private $alphdb;
    
    /**
     * @var float Console Version
     */
    private $version = 1.01;
    
    /**
     * @method void The classes constructor.
     */
    public function AlphConsole($argc, $argv) {
        require_once ALPHDB_FILE;
        $this->alphdb = false;
        $this->argc = $argc;
        $this->argv = $argv;
        $n = ALPHDB_NEW_LINE;
        $nl = "> ";
        print "Welcome to AlphConsole v{$this->version}.$n$n";
        print $nl;
    }
    
    /**
     * @method boolean The main part of the console. Listens and acts.
     */
    public function listen() {
        $stdin = fopen('php://stdin', 'r');
        $n = ALPHDB_NEW_LINE;
        $nl = "> ";
        $nc = ">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>$n$nl";
        while (1) {
            if (!$this->alphdb) {
                print "Please, enter a database you wish to connect to.$n$nl";
                print "If the database does not already exist, it will automatically be created.{$n}{$nl}Please, follow this pattern:$n";
                print "connect <database> <user> <password> [<path=ALPHDB_DEFAULT_PATH>]$n";
                print "connect my_db fabian123 321naibaf$n$n";
                print $nl;
            }
            while (!$this->alphdb) {
                $input = trim(fgets($stdin));
                $args = explode(' ', $input);
                if (@$args[0] == 'exit') {
                    fclose($stdin);
                    return false;
                }
                if (sizeof($args) < 4) {
                    print "$nl Invalid connection string. Please, try again.$n";
                    print $nc;
                } else {
                    try {
                        if (sizeof($args) > 4) {
                            $this->alphdb = new AlphDB($args[1], $args[2], $args[3], str_replace(array('"', "'"), array('', ''), $args[4]));
                        } else {
                            $this->alphdb = new AlphDB($args[1], $args[2], $args[3]);
                        }
                    } catch (Exception $e) {
                        $this->alphdb = false;
                    }
                    if ($this->alphdb) {
                        print "{$nl}Connected!$n";
                        print "{$nl}Type 'help' to see a list of all commands available.$n$n$nl";
                    } else {
                        $this->alphdb = false;
                        print "{$nl}Connection failed. Try again.$n$n$nl";
                    }
                }
            }
            
            $input = trim(fgets($stdin));
            $args = explode(' ', $input);
            if (sizeof($args) < 1) {
                continue;
            }
            
            switch ($args[0]) {
                case 'help': {
                    print "> All Commands:$n";
                    print "   - create <table> <column1> [<column2> <column3> ...]$n";
                    print "     Creates a new <tabble> with all <columns>.$n";
                    print "   - insert <table> <column> <value> ...$n";
                    print "     Inserts a row consisting of <column> <value> into <table>.$n";
                    print "   - remove <table> <column> <value> ...$n";
                    print "     Removes all existing rows from <table> by the <column> <value> criteria.$n";
                    print "   - truncate <table>$n";
                    print "     Completely removes all rows from <table>.$n";
                    print "   - drop <table>$n";
                    print "     Removes an entire table.$n";
                    print "   - select <table>$n";
                    print "     Selects <table>. Note that this does not return anything.$n";
                    print "   - where <column1> <value> <column2> <value> ...$n";
                    print "     Adds a criteria to your selection.$n";
                    print "   - hasRows$n";
                    print "     Returns the amount of rows, or 0.$n";
                    print "   - fetch$n";
                    print "     Retrieves the data from ::select and ::where and prints it.$n";
                    print "   - delete$n";
                    print "     Deletes the currently selected database and all of its tables.$n";
                    print "   - disconnect$n";
                    print "     Disengages your current connection.$n";
                    print "   - exit$n";
                    print "     Disconnects you and exits the console.$n";
                    print $nl;
                } break;
                case 'exit': {
                    fclose($stdin);
                    return false;
                } break;
                case 'disconnect': {
                    print "> Disconnected.$n$n$nl";
                    $this->alphdb = false;
                } break;
                case 'create': {
                    $this->create($args);
                } break;
                case 'insert': {
                    $this->insert($args);
                } break;
                case 'remove': {
                    $this->remove($args);
                } break;
                case 'truncate': {
                    $this->truncate($args);
                } break;
                case 'drop': {
                    $this->drop($args);
                } break;
                case 'select': {
                    $this->select($args);
                } break;
                case 'where': {
                    $this->where($args);
                } break;
                case 'hasRows': {
                    $this->hasRows($args);
                } break;
                case 'fetch': {
                    $this->fetch($args);
                } break;
                case 'delete': {
                    $this->delete($args);
                } break;
                default: {
                    print "> Unknown Command. Type 'help' for more.$n";
                    print $nl;
                } break;
            }
        }
        fclose($stdin);
    }
    
    /**
     * @method void The ::create() handler.
     */
    private function create ($args) {
        $n = ALPHDB_NEW_LINE;
        $nl = "> ";
        $nc = ">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>$n$nl";
        if (sizeof($args) < 3) {
            print "> Invalid arguments. Type 'help' for more.$n$nl";
        } else {
            array_shift($args);
            $table = $args[0];
            array_shift($args);
            $exception = false;
            try {
                $this->alphdb->create($table, $args);
            } catch (Exception $e) {
                $exception = true;
                print "> Error: " . $e->getMessage() . $n . $nl;
            }
            if (!$exception) {
                print "> Created '$table'.$n$n$nl";
            }
        }
    }
    
    /**
     * @method void The ::insert() handler.
     */
    private function insert ($args) {
        $n = ALPHDB_NEW_LINE;
        $nl = "> ";
        $nc = ">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>$n$nl";
        if (sizeof($args) < 3 || (sizeof($args) % 2) != 0) {
            print "> Invalid arguments. Type 'help' for more.$n$nl";
        } else {
            array_shift($args);
            $table = $args[0];
            array_shift($args);
            $exception = false;
            $new = array();
            for ($i = 0; $i < sizeof($args); $i++) {
                $new[$args[$i]] = $args[$i + 1];
                $i++;
            }
            try {
                $this->alphdb->insert($table, $new);
            } catch (Exception $e) {
                $exception = true;
                print "> Error: " . $e->getMessage() . $n . $nl;
            }
            if (!$exception) {
                print "> Inserted.$n$n$nl";
            }
        }
    }
    
    /**
     * @method void The ::remove() handler.
     */
    private function remove ($args) {
        $n = ALPHDB_NEW_LINE;
        $nl = "> ";
        $nc = ">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>$n$nl";
        if (sizeof($args) < 3 || (sizeof($args) % 2) != 0) {
            print "> Invalid arguments. Type 'help' for more.$n$nl";
        } else {
            array_shift($args);
            $table = $args[0];
            array_shift($args);
            $exception = false;
            $criteria = array();
            for ($i = 0; $i < sizeof($args); $i++) {
                $criteria[] = array($args[$i], $args[$i + 1]);
                $i++;
            }
            try {
                $this->alphdb->remove($table, $criteria);
            } catch (Exception $e) {
                $exception = true;
                print "> Error: " . $e->getMessage() . $n . $nl;
            }
            if (!$exception) {
                print "> Removed.$n$n$nl";
            }
        }
    }
    
    /**
     * @method void The ::truncate() handler.
     */
    private function truncate ($args) {
        $n = ALPHDB_NEW_LINE;
        $nl = "> ";
        $nc = ">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>$n$nl";
        if (sizeof($args) < 2) {
            print "> Invalid arguments. Type 'help' for more.$n$nl";
        } else {
            $table = $args[1];
            $exception = false;
            try {
                $this->alphdb->truncate($table);
            } catch (Exception $e) {
                $exception = true;
                print "> Error: " . $e->getMessage() . $n . $nl;
            }
            if (!$exception) {
                print "> Truncated '$table'.$n$n$nl";
            }
        }
    }
    
    /**
     * @method void The ::drop() handler.
     */
    private function drop ($args) {
        $n = ALPHDB_NEW_LINE;
        $nl = "> ";
        $nc = ">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>$n$nl";
        if (sizeof($args) < 2) {
            print "> Invalid arguments. Type 'help' for more.$n$nl";
        } else {
            $table = $args[1];
            $exception = false;
            try {
                $this->alphdb->drop($table);
            } catch (Exception $e) {
                $exception = true;
                print "> Error: " . $e->getMessage() . $n . $nl;
            }
            if (!$exception) {
                print "> Dropped '$table'.$n$n$nl";
            }
        }
    }
    
    /**
     * @method void The ::select() handler.
     */
    private function select ($args) {
        $n = ALPHDB_NEW_LINE;
        $nl = "> ";
        $nc = ">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>$n$nl";
        if (sizeof($args) < 2) {
            print "> Invalid arguments. Type 'help' for more.$n$nl";
        } else {
            $table = $args[1];
            $exception = false;
            try {
                $this->alphdb->select($table);
            } catch (Exception $e) {
                $exception = true;
                print "> Error: " . $e->getMessage() . $n . $nl;
            }
            if (!$exception) {
                print "> '$table' is now selected.$n$n$nl";
            }
        }
    }
    
    /**
     * @method void The ::where() handler.
     */
    private function where ($args) {
        $n = ALPHDB_NEW_LINE;
        $nl = "> ";
        $nc = ">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>$n$nl";
        if (sizeof($args) < 3 || (sizeof($args) % 2) != 1) {
            print "> Invalid arguments. Type 'help' for more.$n$nl";
        } else {
            array_shift($args);
            $exception = false;
            $criteria = array();
            for ($i = 0; $i < sizeof($args); $i++) {
                $criteria[] = array($args[$i], $args[$i + 1]);
                $i++;
            }
            try {
                $this->alphdb->where($criteria);
            } catch (Exception $e) {
                $exception = true;
                print "> Error: " . $e->getMessage() . $n . $nl;
            }
            if (!$exception) {
                print "> Reselected by where-criterias.$n$n$nl";
            }
        }
    }
    
    /**
     * @method void The ::hasRows() handler.
     */
    private function hasRows ($args) {
        $n = ALPHDB_NEW_LINE;
        $nl = "> ";
        $nc = ">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>$n$nl";
        print "> Rows: " . $this->alphdb->hasRows() . "$n$n$nl";
    }
    
    /**
     * @method void The ::fetch() handler.
     */
    private function fetch ($args) {
        $n = ALPHDB_NEW_LINE;
        $nl = "> ";
        $nc = ">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>$n$nl";
        print "> Fetching selection..$n$n";
        $result = $this->alphdb->fetch();
        @print_r($result);
        print "$n$n> Fetched.$n$n$nl";
    }
    
    /**
     * @method void the ::delete() handler.
     */
    private function delete ($args) {
        $n = ALPHDB_NEW_LINE;
        $nl = "> ";
        $nc = ">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>$n$nl";
        $this->alphdb->delete();
        $this->alphdb = false;
        print "> Disconnected.$n";
        print "> Deleted.$n$n$nl";
    }
}

$console = new AlphConsole($argc, $argv);
$console->listen();
?>