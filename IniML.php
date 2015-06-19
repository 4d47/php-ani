<?php

class IniML
{
    private $data;
    private $currentSection;
    private $currentObject;
    private $currentKey;
    private $multiline;
    private $listMode;

    public function __construct()
    {
        $this->data = new ArrayObject();
        $this->currentSection = $this->data;
        $this->multiline = false;
        $this->listMode = false;
    }

    public static function load($stream, $skipBlanks = true)
    {
        if (!is_resource($stream)) {
            $stream = static::string_to_resource($stream);
        }
        return (new static())->parse($stream, $skipBlanks);
    }

    public static function emit(array $array, $delimiter = ' = ')
    {
        $out = '';
        foreach ($array as $key => $value) {
            if (is_string($key) && is_array($value)) {
                $out .= "[$key]\n" . static::emit($value, $delimiter);
            } else if (is_string($key) && is_string($value)) {
                $out .= "$key$delimiter$value\n";
            } else if (is_string($value)) {
                $out .= static::escape("$value\n");
            } else if (is_array($value)) {
                $out .= static::emit($value, $delimiter);
            }
        }
        return $out;
    }

    public function parse($stream, $skipBlanks = true)
    {
        while ($line = fgets($stream)) {
            if ($match = static::matchSection($line)) {
                $this->multiline = false;
                $this->listMode = static::is_plural($match[1]);
                $this->currentSection = $this->data[ $match[1] ] = new ArrayObject();
            } else if ($match = static::matchProperty($line)) {
                $this->currentKey = $match[1];
                $this->multiline = empty($match[2]);
                $this->currentObject = $this->currentSection;
                if ($this->listMode) {
                    $last = $this->currentSection->count() ? 
                        $this->currentSection[count($this->currentSection) - 1] : null;
                    if ($last instanceof ArrayObject) {
                        $this->currentObject = $last;
                    } else {
                        $this->currentObject = $this->currentSection[] = new ArrayObject();
                    }
                }
                if (isset($this->currentObject[$this->currentKey])) {
                    if (!$this->listMode) {
                        if (!empty($this->currentObject->getArrayCopy())) {
                            // convert data currently in map to list of data
                            $new = [];
                            $assoc = new ArrayObject();
                            foreach ($this->currentObject as $key => $value) {
                                if (is_numeric($key)) {
                                    if (!empty($assoc)) {
                                        $new[] = $assoc;
                                        $assoc = new ArrayObject();
                                    }
                                    $new[] = $value;
                                } else {
                                    $assoc[$key] = $value;
                                }
                            }
                            if (!empty($assoc)) {
                                $new[] = $assoc;
                            }

                            $this->currentObject->exchangeArray($new);
                        }
                        $this->listMode = true;
                    }
                    $this->currentObject = $this->currentSection[] = new ArrayObject();
                }
                $this->currentObject[$this->currentKey] = $match[2];
            } else if ($this->multiline) {
                $this->currentObject[$this->currentKey] .= static::unescape($line);
            } else if (!$skipBlanks || !preg_match('/^\s*$/', $line)) {
                $this->currentSection[] = rtrim(static::unescape($line), "\n");
            }
        }
        return $this->toArray($this->data);
    }


    protected static function matchSection($line)
    {
        return static::match('/^\s*\[\s*([A-Za-z0-9\-_\.]*)\s*\]\s*$/', $line);
    }

    protected static function matchProperty($line)
    {
        return static::match('/^\s*([A-Za-z0-9\-_\.]+)\s*[:=]\s*(.*?)\s*$/', $line);
    }

    protected static function match($regex, $line)
    {
        preg_match($regex, $line, $match);
        return $match;
    }

    protected static function escape($line)
    {
        if (static::matchSection($line) || static::matchProperty($line)) {
            return preg_replace('/^(\s*)/', '$1\\', $line);
        };
        return $line;
    }

    protected static function unescape($line)
    {
        return preg_replace('/^(\s*)\\\\/', '$1', $line);
    }

    protected function toArray($array)
    {
        $result = [];
        foreach ($array as $key => $value) {
            $result[$key] = $value instanceof ArrayObject ? $this->toArray($value) : $value;
        }
        return $result;
    }

    public static function string_to_resource($string)
    {
        $handle = fopen('php://memory', 'w+');
        fwrite($handle, $string);
        rewind($handle);
        return $handle;
    }

    public static function is_plural($word)
    {
        return $word != \Doctrine\Common\Inflector\Inflector::singularize($word);
    }
}
