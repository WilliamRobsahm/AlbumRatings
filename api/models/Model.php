<?php
require_once(PROJECT_ROOT_PATH . "/common/QueryGenerator.php");
class Model {
    protected $connection = null;

    public function __construct() {
        try {
            $this->connection = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DATABASE_NAME);
            
            if ( mysqli_connect_errno()) {
                throw new Exception("Could not connect to database.");   
            }
        }
        catch (Exception $e) {
            throw new Exception($e->getMessage());   
        }
    }

    public function apply_params(array|object $params) 
    {
        $reflection = new ReflectionClass(get_class($this));

        foreach ($params as $key => $value) {

            try {
                $prop = $reflection->getProperty($key);
                $attributes = $prop->getAttributes(DataColumn::class);
                if(count($attributes) > 0) {
                    $this->{$key} = $value;
                }
            }
            catch(Exception $e) { }
        }
    }

    public function get_primary_key_value() 
    {
        $property = get_property_with_attr($this, PrimaryKey::class);

        if($property) {
            try {
                return $property->getValue($this);
            }
            catch(Exception $e) { }
        }
        return null;
    }

    public function get_primary_key_field() 
    {
        $property = get_property_with_attr($this, PrimaryKey::class);
        return $property ? $property->getName() : null;
    }

    public function get_all_columns() 
    {
        $reflector = new ReflectionClass(get_class($this));
        $properties = $reflector->getProperties();

        // Filter out any non-column properties
        $col_props = array_filter($properties, fn(ReflectionProperty $prop) => 
            count($prop->getAttributes(DataColumn::class)) > 0);

        return array_map(fn(ReflectionProperty $prop) => 
            ($prop->getName()), $col_props);
    }

    /**
     * Get object values as array
     * !!! DOES NOT INCLUDE PRIMARY KEY VALUE
     */
    public function get_update_values(): array 
    {
        $properties = $this->get_column_properties();

        $values = [];
        $primary_key_field = $this->get_primary_key_field();
        
        foreach($properties as $prop) {
            if($prop->getName() !== $primary_key_field) {
                $values[] = $prop->getValue($this);
            }
        }

        return $values;
    }

    /**
     * Get object fields as array
     * !!! DOES NOT INCLUDE PRIMARY KEY FIELD
     */
    public function get_update_fields(): array 
    {
        $properties = $this->get_column_properties();

        $fields = [];
        $primary_key_field = $this->get_primary_key_field();
        
        foreach($properties as $prop) {
            if($prop->getName() !== $primary_key_field) {
                $fields[] = $prop->getName();
            }
        }

        return $fields;
    }

    private function get_column_properties() 
    {
        $reflection = new ReflectionClass(get_class($this));
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        // Filter out any non-column properties
        return array_filter($properties, fn(ReflectionProperty $prop) => 
            count($prop->getAttributes(DataColumn::class)) > 0);
    }

    protected function select(string $query = "", ?string $types = null, $values = []) {
        try {
            $stmt = $this->execute_statement($query, $types, $values);
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);				
            $stmt->close();
            return $result;
        } 
        catch(Exception $e) {
            throw New Exception( $e->getMessage() );
        }
    }

    private function execute_statement(string $query = "", ?string $types = null, $values = []): mysqli_stmt
    {
        try {
            $stmt = $this->connection->prepare($query);

            if($stmt === false) {
                throw New Exception("Unable to do prepared statement: " . $query);
            }
            if($types !== null && count($values) > 0) {
                $stmt->bind_param($types, ...$values);
            }
            $stmt->execute();
            return $stmt;
        } 
        catch(Exception $e) {
            throw New Exception( $e->getMessage() );
        }	
    }

    protected function save(string $table_name, Model $object): mysqli_stmt {

        $primary_key = $object->get_primary_key_value();

        if(!$primary_key) {
            return $this->insert($table_name, $object);
        }
        else {
            return $this->update($table_name, $object);
        }
    }

    protected function insert(string $table_name, Model $object): mysqli_stmt 
    {
        $sql = QueryGenerator::generate_insert_query($table_name, $object);
        echo $sql;

        // Construct an array with values to bind
        $insert_values = $object->get_update_values();
        $types = str_repeat('s', count($insert_values));

        $stmt = $this->execute_statement($sql, $types, $insert_values);
        return $stmt;
    }

    protected function update(string $table_name, Model $object): mysqli_stmt 
    {
        $sql = QueryGenerator::generate_update_query($table_name, $object);

        // Construct an array with values to bind
        $update_values = $object->get_update_values();
        $update_values[] = $object->get_primary_key_value();

        $types = str_repeat('s', count($update_values));

        $stmt = $this->execute_statement($sql, $types, $update_values);
        return $stmt;
    }
}

?>