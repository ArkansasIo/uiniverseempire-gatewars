<?php
// Base::Chive.class.php
class SafeDbResult
{
    public int $num_rows = 0;
    public int $affected_rows = 0;

    public function fetch_object(): ?object
    {
        return null;
    }

    public function fetch_assoc(): ?array
    {
        return null;
    }

    public function fetch_array(): ?array
    {
        return null;
    }

    public function fetch_row(): ?array
    {
        return null;
    }

    public function free(): void
    {
    }
}

class SafeDbStatement
{
    public int $num_rows = 0;
    public int $affected_rows = 0;

    public function bind_param(...$args): bool
    {
        return true;
    }

    public function execute(): bool
    {
        return false;
    }

    public function get_result(): SafeDbResult
    {
        return new SafeDbResult();
    }

    public function store_result(): bool
    {
        return true;
    }

    public function close(): void
    {
    }

    public function __call(string $name, array $arguments)
    {
        return false;
    }
}

class SafeDbConnection
{
    public string $connect_error;
    public string $error;
    public int $insert_id = 0;

    public function __construct(string $message = "No database connection")
    {
        $this->connect_error = $message;
        $this->error = $message;
    }

    public function prepare($query): SafeDbStatement|false
    {
        return new SafeDbStatement();
    }

    public function query($query)
    {
        return false;
    }

    public function real_escape_string($string): string
    {
        return addslashes((string)$string);
    }

    public function begin_transaction(): bool
    {
        return false;
    }

    public function commit(): bool
    {
        return false;
    }

    public function rollback(): bool
    {
        return false;
    }

    public function close(): void
    {
    }

    public function __call(string $name, array $arguments)
    {
        return false;
    }
}

class Chive
{
    // General Info
    /**
     * Name of the class
     *
     * @var string|null $name
     */
    public ?string $name = null;
    
    /**
     * Database table prefix
     *
     * @var string|null $db_prefix
     */
    public ?string $db_prefix = null;

    /**
     * Database server location
     *
     * @var string|null $db_server
     */
    public ?string $db_server = null;

    /**
     * Database name
     *
     * @var string|null $db_name
     */
    public ?string $db_name = null;

    /**
     * Database username
     *
     * @var string|null $db_username
     */
    public ?string $db_username = null;

    /**
     * Database password
     *
     * @var string|null $db_password
     */
    public ?string $db_password = null;

    /**
     * MySQLi Resource link for database connections
     *
     * @var mixed $db_link
     */
    public $db_link = null;
    public int $queryCount = 0;

    /**
     * Constructor for Chive
     * @param string $name Name of the class
     *
     */
    public function __construct(string $name = "")
    {
        global $conf;
        $this->name = $name;
        $this->db_server = $conf['db_server'];
        $this->db_name = $conf['db_name'];
        $this->db_username = $conf['db_username'];
        $this->db_password = $conf['db_password'];
        $this->db_prefix = $conf['db_prefix'];
        
        Debug::printMsg(__CLASS__, __FUNCTION__, "Class created with <b>\$name</b> ".$this->name);
    }
    
    /**
     * Creates a MySQLi Resource link to $db_link
     *
     */
    public function connectToDB(): void
    {
        Debug::printMsg(__CLASS__, __FUNCTION__, "Connecting to DB...");

        if (!class_exists('mysqli') || !function_exists('mysqli_report')) {
            $this->db_link = new SafeDbConnection("MySQLi extension is not available in this PHP environment.");
            Debug::printMsg(__CLASS__, __FUNCTION__, "MySQLi extension is not available in this PHP environment.");
            return;
        }

        mysqli_report(MYSQLI_REPORT_OFF);

        try {
            $this->db_link = @new mysqli($this->db_server, $this->db_username, $this->db_password, $this->db_name);

            // If localhost socket resolution fails in container/dev envs, retry over TCP.
            if ($this->db_link && $this->db_link->connect_error && $this->db_server === "localhost") {
                $this->db_link = @new mysqli("127.0.0.1", $this->db_username, $this->db_password, $this->db_name);
            }
        } catch (Throwable $e) {
            $this->db_link = new SafeDbConnection("Couldn't connect to DB " . $e->getMessage());
            Debug::printMsg(__CLASS__, __FUNCTION__, "Couldn't connect to DB " . $e->getMessage());
            return;
        }

        if($this->db_link && !$this->db_link->connect_error)
        {
            Debug::printMsg(__CLASS__, __FUNCTION__, "Connected to database");
            return;
        }

        $error = $this->db_link ? $this->db_link->connect_error : "Unknown connection error";
        $this->db_link = new SafeDbConnection((string)$error);
        Debug::printMsg(__CLASS__, __FUNCTION__, "Couldn't connect to DB " . $error);
    }
    
    /**
     * Checks if a connection to the database server has been made
     *
     * @return bool
     */
    public function connected(): bool
    {
        if ($this->db_link === null) {
            return false;
        }

        if ($this->db_link instanceof SafeDbConnection) {
            return false;
        }

        return !$this->db_link->connect_error;
    }
    
    /**
     * Cleans $string for a MySQL statement
     *
     * @param string $string
     * @param int $quotes
     * @return string
     */
    public function clean_sql(string $string, int $quotes = 1): string
    {
        if(!$this->connected()) $this->connectToDB();

        if(!$this->connected()) {
            return $quotes ? "'" . addslashes($string) . "'" : $string;
        }
        
        // Quote if not integer
        if (!is_numeric($string) && $quotes)
        {
            $string = "'".$this->db_link->real_escape_string($string)."'";
        }
        return $string;
    }
    
    /**
     * Queries Database.
     * Returns true or false depending on success
     *
     * @param string $query
     * @return mixed
     */
    public function query(string $query)
    {
        if(!$this->connected()) $this->connectToDB();

        if(!$this->connected()) {
            Debug::printMsg(__CLASS__, __FUNCTION__, "Query aborted: no DB connection for query - \"" . $query . "\"");
            $this->queryCount++;
            return false;
        }
        
        $r = false;
        try {
            $r = $this->db_link->query($query);
        } catch (Throwable $e) {
            Debug::printMsg(__CLASS__, __FUNCTION__, "Query exception - <b>ERROR:</b> " . $e->getMessage() . " FROM QUERY - \"" . $query . "\"\n");
            $this->queryCount++;
            return false;
        }
        if($r)
        {
            Debug::printMsg(__CLASS__, __FUNCTION__, "Query successful: ".$query."\r\n");
            $this->queryCount++;
            return $r;
        }
        Debug::printMsg(__CLASS__, __FUNCTION__, "Query unsuccessful - <b>ERROR:</b> ".$this->db_link->error." FROM QUERY - \"".$query."\"\n");
        $this->queryCount++;
        return false;
    }
}

class page_gen {
    //
    // PRIVATE CLASS VARIABLES
    //
    private float $_start_time;
    private float $_stop_time;
    private float $_gen_time;
    
    //
    // USER DEFINED VARIABLES
    //
    public int $round_to;
    
    //
    // CLASS CONSTRUCTOR
    //
    public function __construct() {
        if (!isset($this->round_to)) {
            $this->round_to = 4;
        }
    }
    
    //
    // FIGURE OUT THE TIME AT THE BEGINNING OF THE PAGE
    //
    public function start(): void {
        $microstart = explode(' ', microtime());
        $this->_start_time = $microstart[0] + $microstart[1];
    }
    
    //
    // FIGURE OUT THE TIME AT THE END OF THE PAGE
    //
    public function stop(): void {
        $microstop = explode(' ', microtime());
        $this->_stop_time = $microstop[0] + $microstop[1];
    }
    
    //
    // CALCULATE THE DIFFERENCE BETWEEN THE BEGINNNG AND THE END AND RETURN THE VALUE
    //
    public function gen(): float {
        $this->_gen_time = round($this->_stop_time - $this->_start_time, $this->round_to);
        return $this->_gen_time; 
    }
} 
?>