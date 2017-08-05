<?php
/**
 * Created by PhpStorm.
 * User: lunguandrei
 * Date: 03.08.17
 * Time: 9:44
 */

abstract class Abstract_Validator {
    public $error;

    public function __construct($value)
    {
        if (!$this->validate($value)) {
            $this->error = $this->getError();
        }
    }

    abstract protected function validate($value);

    abstract protected function getError();

}

class Digit_Validator extends Abstract_Validator {

    protected function validate($value)
    {
        if (preg_match('/^[0-9]*$/', $value)) {
            return true;
        } else {
            return false;
        }
    }

    protected function getError()
    {
        return "This value is not a digit!";
    }
}

class Ssn_Validator extends Abstract_Validator {

    protected function validate($value)
    {
        if (preg_match('/^[1-9]{1}((1[0-9]{3})|(20(0[0-9]{1}|1[0-7])))(0[1-9]|1[0-2])(0[1-9]|
        [1-2][0-9]|3[0-1])[0-9]{4}$/', $value)) {
            if(checkdate())
                $sum=0;
            for ($i = 0; $i < strlen($value)-1; $i++)
            {

                $sum += $value[$i]*($i+1);
            }
            $res = $sum % 11;
            $lastDigit = ($res == 10) ? 1 : $res;
            if($lastDigit == $value[12]) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    protected function getError()
    {
        return "This value is not a social security number!";
    }
}

class Sequence_Validator extends Abstract_Validator {

    protected function validate($value)
    {
        $res=0;
        $arr = [1, 1, 1];
        while($res < $value){
            $res = $arr[count($arr)-2] + $arr[count($arr)-3];
            $arr[] = $res;
            if($res == $value){
                return true;
            }
        }
        return false;
    }

    public function getError()
    {
        return "This value is not in the sequence!";
    }
}

class Bundleprice_Validator extends Abstract_Validator {

    protected function validate($value)
    {
        $servername = "localhost";
        $username = "root";
        $password = "root";
        $dbname = "test";

        // Create connection
        $conn = new mysqli($servername, $username, $password, $dbname);
        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $sql = "SELECT 
            b.id, b.bundleName, (SUM(p.price)-(SUM(p.price)*b.discount/100)) as bundlePrice
            FROM
                products AS p
                    LEFT JOIN
                bundle_products AS bp ON p.id = bp.productId
                    LEFT JOIN
                bundles AS b ON b.id = bp.bundleId
            GROUP BY b.id
            HAVING b.bundleName='".$value."';
            ";
        $result = $conn->query($sql);
        $results = [];

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $results[] = $row;
            }
        }
        $conn->close();

        if(count($results) > 0 && $results[0]['bundlePrice'] < 100) {
            return true;
        }

        return false;
    }

    protected function getError()
    {
        return "This bundle price is not lower when 100!";
    }
}


class Validation {

    private $validation;
    private $validationErrors;

    public function validate($value, $rules)
    {
        //Rules can be: digit, ssn, sequence, bundleprice
        foreach ($rules as $rule) {
            $validator = ucfirst($rule) . '_Validator';
            if (class_exists($validator)) {
                $this->validation = new $validator($value);
                if ($this->validation->error) {
                    $this->validationErrors[$rule] = $this->validation->error;
                }
            }
        }

        return $this->validationErrors;
    }
}
?>