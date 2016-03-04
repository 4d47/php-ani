<?php

class IniML
{
    public $options = [
        'delimiter' => ': ',
        'arrayClass' => 'ArrayObject',
        'ignoreBlankLines' => true,
    ];

    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    public function emit(array $array)
    {
        $out = '';
        foreach ($array as $key => $value) {
            if (is_string($key)) {
                if (is_array($value)) {
                    $out .= "[$key]\n" . $this->emit($value);
                    continue;
                }
                if (is_bool($value)) {
                    $value = var_export($value, true);
                } else if (is_null($value)) {
                    $value = 'null';
                }
                $out .= "$key{$this->options['delimiter']}$value\n";
            } else if (is_string($value)) {
                $out .= $this->escape("$value\n");
            } else if (is_array($value)) {
                $out .= $this->emit($value);
            }
        }
        return $out;
    }

    public function parse($stream)
    {
        if (!is_resource($stream)) {
            $stream = static::stringResource($stream);
        }

        $data = $this->makeArray();
        $currentSection = $data;
        $currentObject = null;
        $currentKey = null;
        $multiline = false;
        $listMode = false;

        while ($line = fgets($stream)) {
            if ($match = $this->matchSection($line)) {
                $multiline = false;
                $listMode = static::isPlural($match[1]);
                $currentSection = $data[ $match[1] ] = $this->makeArray();
            } else if ($match = $this->matchProperty($line)) {
                $currentKey = $match[1];
                $multiline = empty($match[2]);
                $currentObject = $currentSection;
                if ($listMode) {
                    $last = $currentSection->count() ?
                        $currentSection[count($currentSection) - 1] : null;
                    if ($last instanceof ArrayObject) {
                        $currentObject = $last;
                    } else {
                        $currentObject = $currentSection[] = $this->makeArray();
                    }
                }
                if (isset($currentObject[$currentKey])) {
                    if (!$listMode) {
                        if (!empty($currentObject->getArrayCopy())) {
                            // convert data currently in map to list of data
                            $new = [];
                            $assoc = $this->makeArray();
                            foreach ($currentObject as $key => $value) {
                                if (is_numeric($key)) {
                                    if (!empty($assoc)) {
                                        $new[] = $assoc;
                                        $assoc = $this->makeArray();
                                    }
                                    $new[] = $value;
                                } else {
                                    $assoc[$key] = $value;
                                }
                            }
                            if (!empty($assoc)) {
                                $new[] = $assoc;
                            }

                            $currentObject->exchangeArray($new);
                        }
                        $listMode = true;
                    }
                    $currentObject = $currentSection[] = $this->makeArray();
                }
                $currentObject[$currentKey] = $this->cast($match[2]);
            } else if ($multiline) {
                $currentObject[$currentKey] .= $this->unescape($line);
            } else if (!$this->options['ignoreBlankLines'] || !preg_match('/^\s*$/', $line)) {
                $currentSection[] = rtrim($this->unescape($line), "\n");
            }
        }
        return $this->toArray($data);
    }

    protected function matchSection($line)
    {
        return $this->match('/^\s*\[\s*([A-Za-z0-9\-_\.]*)\s*\]\s*$/', $line);
    }

    protected function matchProperty($line)
    {
        return $this->match('/^\s*([A-Za-z0-9\-_\.]+)\s*[:=]\s*(.*?)\s*$/', $line);
    }

    protected function match($regex, $line)
    {
        preg_match($regex, $line, $matches);
        return $matches;
    }

    protected function escape($line)
    {
        if ($this->matchSection($line) || $this->matchProperty($line)) {
            return preg_replace('/^(\s*)/', '$1\\', $line);
        };
        return $line;
    }

    protected function unescape($line)
    {
        return preg_replace('/^(\s*)\\\\/', '$1', $line);
    }

    protected function cast($value)
    {
        if (strcasecmp($value, 'true') === 0) {
            return true;
        }
        if (strcasecmp($value, 'false') === 0) {
            return false;
        }
        if (strcasecmp($value, 'null') === 0) {
            return null;
        }
        if (is_numeric($value)) {
            return $value + 0;
        }
        return $value;
    }

    protected function makeArray()
    {
        return new $this->options['arrayClass'];
    }

    protected function toArray($array)
    {
        $result = [];
        foreach ($array as $key => $value) {
            $result[$key] = $value instanceof $this->options['arrayClass'] ? $this->toArray($value) : $value;
        }
        return $result;
    }

    public static function stringResource($string)
    {
        $handle = fopen('php://memory', 'w+');
        fwrite($handle, $string);
        rewind($handle);
        return $handle;
    }

    public static function isPlural($word)
    {
        return $word != \Doctrine\Common\Inflector\Inflector::singularize($word);
    }
}
