<?php

class dbmanage {

    private $servername = "localhost";
    private $database = "konkurs_bot";
    private $username = "root";
    private $password = "smCTj1P5FXsYK";

    protected function create_db_connection() {
        // Create connection
        $conn = mysqli_connect($this->servername, $this->username, $this->password, $this->database);
        if (!$conn) {
            die("Connection failed: " . mysqli_connect_error());
        }
        return $conn;
    }

    public function insert_record($tablename, array $params) {
        $columns = implode(', ', array_keys($params));
        $values = "'" . implode('\', \'', array_values($params)) . "'";
        $sql = "INSERT INTO $tablename ($columns) VALUES ($values)";
        $conn = $this->create_db_connection();
        $result = mysqli_query($conn, $sql);
        mysqli_close($conn);

        return $result;
    }

    public function get_records($tablename) {
        $sql = "SELECT * FROM $tablename";
        $conn = $this->create_db_connection();
        $result = mysqli_query($conn, $sql);
        if($result)
        {
            $rows = mysqli_num_rows($result); // количество полученных строк
            echo "<table><tr><th>Id</th><th>username</th><th>referertoken</th><th>referer</th><th>selftoken</th><th>date</th></tr>";
            for ($i = 0 ; $i < $rows ; ++$i)
            {
                $row = mysqli_fetch_row($result);
                echo "<tr>";
                for ($j = 0 ; $j < 6 ; ++$j) echo "<td>$row[$j]</td>";
                echo "</tr>";
            }
            echo "</table>";

            // очищаем результат
            mysqli_free_result($result);
        }

        mysqli_close($conn);
    }
}