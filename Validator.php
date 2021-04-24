<?php
class Validator {
    private $data;
    private $pointer;
    private $constraints;
    private $constraintNamePointer;
    private static $patterns = [
        'email' => '/[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/',
        'digits' => '/[0-9]+$/',
        'text' => '/[A-Za-z0-9_żźćńółęąśŻŹĆĄŚĘŁÓŃ]+$/',
        'whitespace' => '/^[^\s]+$/',
        'password_weak' => '/(?=^.{6,}$)(?=.{0,}[a-z])(?=.{0,}\d)/', //digit, min length 6
        'password_medium' => '/(?=^.{6,}$)(?=.{0,}[A-Z])(?=.{0,}[a-z])(?=.{0,}\d)/', //uppercase, digit, min length 6
        'password_strong' => '/(?=^.{8,}$)(?=.{0,}[A-Z])(?=.{0,}[a-z])(?=.{0,}\W)(?=.{0,}\d)/' //uppercase, digit, specialchar, min length 8
    ];

    private $dataMessages;
    private $messages = [
        'required' => 'Pole jest wymagane.',
        'length' => ['Pole musi zawierać przynajmniej {min} znaków.', 'Pole musi zawierać od {min} do {max} znaków.'],
        'unique' => 'Wartość pola jest już w użyciu.',
        'pattern' => 'Nieprawidłowa wartość pola.',
        'compare' => 'Wartości nie są identyczne.',
        'in' => 'Nieprzewidywana wartość pola.',
        'ext' => 'Nieobsługiwany format pliku.'
    ];
    private $validatedMessages;

    public function __construct($data) {
        $this->data = $data;
        return $this;
    }

    public function data($key) {
        $this->pointer = $key;
        return $this;
    }

    public function get($key) {
        return $this->data[$key];
    }

    public function validate() {
        $messages = [];
        foreach($this->constraints as $dataName => $constraints) {
            foreach($constraints as $cName => $cArgs) {
                if(isset($this->data[$dataName]) && $this->data[$dataName] != null) {
                    if(!$this->checkConstraint($cName, $cArgs, $this->data[$dataName])) {
                        if(!isset($messages[$dataName])) {
                            $messages[$dataName] = [];
                        }
                        array_push($messages[$dataName], $this->getMessage($cName, $cArgs, $dataName));
                    }
                } else if($cName === 'required') {
                    if(!isset($messages[$dataName])) {
                        $messages[$dataName] = [];
                    }
                    array_push($messages[$dataName], $this->getMessage('required', $cArgs, $dataName));
                    break;
                }
            }
        }
        $this->validatedMessages = $messages;
        return empty($messages) ? true : false;
    }

    public function message($msg) {
        $this->dataMessages[$this->pointer][$this->constraintNamePointer] = $msg;
        return $this;
    }

    public function setMessage($constraintName, ...$msgs) {
        $messages = [];
        foreach($msgs as $msg) {
            array_push($messages, $msg);
        }

        if(count($messages) > 1) {
            $this->messages[$constraintName] = $messages;
        } else {
            $this->messages[$constraintName] = $messages[0];
        }
    }

    public function getMessages() {
        return $this->validatedMessages;
    }

    private function getMessage($cName, $cArgs, $dataName) {
        $patterns = [];
        $replacements = [];
        $index = 0;

        $message = $this->messages[$cName];

        if(is_array($cArgs)) {
            foreach($cArgs as $key => $value) {
                $patterns[$index] = '/\{'.$key.'\}/';
                $replacements[$index] = $value;
                $index++;
            }

            $args = -1;
            foreach($cArgs as $arg) {
                if($arg != null) {
                    $args++;
                }
            }

            if(is_array($this->messages[$cName])) {
                $message = $this->messages[$cName][$args];
            }
        }

        
        if(isset($this->dataMessages[$dataName][$cName])) {
            $message = $this->dataMessages[$dataName][$cName];
        }

        return preg_replace($patterns, $replacements, $message);
    }

    private function addConstraint($constraintName, $value = null) {
        $this->constraintNamePointer = $constraintName;
        $this->constraints[$this->pointer][$constraintName] = $value;
    }

    private function checkConstraint($name, $values, $data) {
        switch ($name) {
            case 'required':
                return $data != null ? true : false;
                break;
            case 'length':
                if($values['max'] != null) {
                    return strlen($data) >= $values['min'] && strlen($data) <= $values['max'] ? true : false;
                } else {
                    return strlen($data) >= $values['min'] ? true : false;
                }
                break;
            case 'unique':
                return in_array($data, $values) ? false : true;
                break;
            case 'pattern':
                return $this->checkPattern($values, $data);
                break;
            case 'compare':
                return $data == $values ? true : false;
                break;
            case 'in':
                return in_array($data, $values);
                break;
            case 'ext':
                $exts = explode('.', $data);
                $ext = end($exts);
                return in_array($ext, $values);
            default:
                throw new Exception('Constraint not exists.');
                break;
        }
    }

    public static function setPattern($name, $regex) {
        self::$patterns[$name] = $regex;
    }

    private function checkPattern($type, $data) {
        if(isset(self::$patterns[$type])) {
            return preg_match(self::$patterns[$type], $data);
        } else {
            throw new Exception('Pattern not exists.');
        }
    }

    // CONSTRAINTS FUNCTIONS
    public function required() {
        $this->addConstraint('required');
        return $this;
    }

    public function length($min, $max = null) {
        $this->addConstraint('length', [
            'min' => $min,
            'max' => $max
        ]);
        return $this;
    }

    public function unique($values) {
        $this->addConstraint('unique', $values);
        return $this;
    }

    public function pattern($type) {
        $this->addConstraint('pattern', $type);
        return $this;
    }

    public function compare($value) {
        $this->addConstraint('compare', $value);
        return $this;
    }

    public function in($array) {
        $this->addConstraint('in', $array);
        return $this;
    }

    public function ext($array) {
        $this->addConstraint('ext', $array);
        return $this;
    }
}
