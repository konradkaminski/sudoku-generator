<?php

class Encryption {

    private  $secretKey = "SudokuBoard1234!@#$"; 
 
    public  function safe_b64encode($string) {
        $data = base64_encode($string);
        $data = str_replace(array('+','/','='),array('-','_',''),$data);
        return $data;
    }
 
    public function safe_b64decode($string) {
        $data = str_replace(array('-','_'),array('+','/'),$string);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }
 
    public  function encode($value){ 
        if(!$value){return false;}
        $text = $value;
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->secretKey, $text, MCRYPT_MODE_ECB, $iv);
        return trim($this->safe_b64encode($crypttext)); 
    }
 
    public function decode($value){
        if(!$value){return false;}
        $crypttext = $this->safe_b64decode($value); 
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $decrypttext = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->secretKey, $crypttext, MCRYPT_MODE_ECB, $iv);
        return trim($decrypttext);
    }
}
        
        
class SudokuGenerator {
    
    private $size = null;
    private $factor = null;
    private $firstRow = null;
    private $table = array();
    
    public function __construct() {
        srand($this->make_seed());
    }
    
    private function make_seed()
    {
        list($usec, $sec) = explode(' ', microtime());
        return (float) $sec + ((float) $usec * 100000);
    }
    
    public function setSize($size = 3) {
        $this->size = $size;
        $this->factor = pow($size, 2);
    }
    
    public function getSize() {
        if($this->size == null) {
            $this->setSize();
        }
        return $this->size;
    }
    
    public function getFactor() {
        if($this->size == null) {
            $this->setSize();
        }
        return $this->factor;
    }

    private function genTable() {
        if($this->size == null) {
            $this->setSize();
        }
        $this->firstRow = range(1, $this->getFactor());
        shuffle($this->firstRow);
        $this->genRows();
        $this->shuffle();
    }
    
    function transpose($array) {
        array_unshift($array, null);
        return call_user_func_array('array_map', $array);
    }
    
    private function transformationMatrix($min, $max) {
        $out = array();
        $myMin= rand($min, $max);
        do {
            $myMax = rand($min, $max);
        } while($myMin == $myMax);
        $out = array($myMin, $myMax);
        return $out;
    }
    
    private function shuffle() {
        
        $randomNumber = rand(7, 20);
        // liczba operacji
        for($j = 0; $j < $randomNumber; $j++) {
            if(rand(0, 1) == 1) {
                // czy transponowac tabele
                $this->table = $this->transpose($this->table);
            }
            // dla kazdego kafelka
            for($i = 0; $i < $this->size; $i++) {
                
                $minRow = $i * $this->size;
                $maxRow = $minRow + $this->size - 1;
                $transform = $this->transformationMatrix($minRow, $maxRow);
                // zamiana wierszy
                $rowA = $this->table[$transform[0]];
                $rowB = $this->table[$transform[1]];
                $this->table[$transform[1]] = $rowA;
                $this->table[$transform[0]] = $rowB;
                
                if(rand(0, 1) == 1) {
                    // czy podmieniac liczby w wierszach
                    $minRow = $i * $this->size;
                    $maxRow = $minRow + $this->size - 1;
                    $transform = $this->transformationMatrix($minRow, $maxRow);
                    
                    $key = rand(0, 8);
                    
                    $keys = array();
                    
                    $tmpNumbers = array(
                    );
                    
                    $finish = true;
                    do {
                        $tmpNumbers[] = $this->table[$transform[0]][$key];
                        $secondNumber = $this->table[$transform[1]][$key];
                        
                        if(in_array($secondNumber, $tmpNumbers)) {
                            $finish = true;
                        } else {
                            $finish = false;
                        }
                        $keys[] = $key;
                        
                        $key = array_search($secondNumber, $this->table[$transform[0]]);
                        
                    } while($finish != true);
                    
                    if(count($keys) != 1 && count($keys) != $this->factor) {
                    
                        foreach($keys as $key) {
                            $nA = $this->table[$transform[0]][$key];
                            $nB = $this->table[$transform[1]][$key];
                            
                            $this->table[$transform[1]][$key] = $nA;
                            $this->table[$transform[0]][$key] = $nB;
                        }
                    }
                }
            }
        }       
    }
    
    private function genRows() {
        for($i = 0; $i < $this->getSize(); $i++) {
            for($j = 0; $j < $this->getSize(); $j++) {
                $slize = ($j * $this->getSize()) + $i;
                if($slize == 0) {
                    $this->table[] = $this->firstRow;
                } else {
                    $newArray = array();
                    for($k = $slize; $k < $slize + $this->getFactor(); $k++) {
                        $kk = $k;
                        if($k >= $this->getFactor()) {
                            $kk = $k - $this->getFactor() ;
                        }
                        $newArray[] = $this->firstRow[$kk];
                    }
                    $this->table[] = $newArray;
                }
            }
        }
    }

    public function isValid($data) {
        if(empty($this->table)) {
            return false;
        }
        foreach($data as $row => $v) {
            foreach($v as $x => $item) {
                if($item != $this->table[$row][$x]) {
                    return false;
                }
            }
        }
        return true;
    }
    
    public function getEncodedData() {
        if(empty($this->table)) {
            $this->genTable();
        }
        $encoder = new Encryption();
        $input = json_encode($this->table);
        return $encoder->encode($input);
    }
    
    public function decode($input) {
        $encoder = new Encryption();
        $data = $encoder->decode($input);
        $this->table = json_decode($data); 
    }
    
    public function showTable() {
        if(empty($this->table)) {
            $this->genTable();
        }
        $out = '';
        foreach($this->table as $row) {
            $rowItems = array();
            foreach($row as $item) {
                $rowItems[] = $item;
            }
            $out .= implode('; ', $rowItems);
            $out .= '<br />';
        }
        echo $out;
    }
    
    public function getTable() {
        if(empty($this->table)) {
            $this->genTable();
        }
        return $this->table;
    }
}
