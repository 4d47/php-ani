<?php

class IniML
{
    public $options = [
        'arrayClass' => 'ArrayObject',
        'multilineIndent' => '  ',
        'lineIgnorePredicates' => []
    ];

    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    public function emit($array)
    {
        assert(is_array($array) || $array instanceof Traversable);
        $out = '';
        foreach ($array as $key => $value) {
            if (is_string($key)) {
                if (is_array($value) || $value instanceof Traversable) {
                    $out .= "[$key]\n" . $this->emit($value);
                    continue;
                }
                if (is_bool($value)) {
                    $value = var_export($value, true);
                } else if (is_null($value)) {
                    $value = 'null';
                }
                if (strpos($value, "\n") === false) {
                    $out .= "$key: $value\n";
                } else {
                    $out .= "$key:\n" . preg_replace('/^(.*)/m', $this->options['multilineIndent'] . '$1', $value);
                }
            } else if (is_string($value)) {
                $out .= $this->escape("$value\n");
            } else if (is_array($value) || $value instanceof Traversable) {
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
        $indent = null;
        $listMode = false;

        while ($line = fgets($stream)) {

            if ($multiline && empty($currentObject[$currentKey])) {
                preg_match('/^\s*/', rtrim($line, "\n"), $matches); // FIXME: refactor rtrim out of here
                $indent = $matches[0];
            }

            if ($multiline && ($indent && preg_match('/^' . preg_quote($indent) . '/', $line))) {
               $currentObject[$currentKey] .=  preg_replace('/^' . preg_quote($indent) . '/', '', $line);

            } else if ($match = $this->matchSection($line)) {
                $multiline = false;
                $indent = null;
                $sectionName = trim($match[1]);
                $listMode = static::isPlural($sectionName);
                $currentSection = $data[ $sectionName ] = $this->makeArray();

            } else if ($match = $this->matchProperty($line)) {
                $currentKey = $match[1];
                $multiline = empty($match[2]);
                $indent = null;
                $currentObject = $currentSection;
                if ($listMode) {
                    $last = $currentSection->count() ? $currentSection[$currentSection->count() - 1] : null;
                    $currentObject = $last instanceof ArrayObject ? $last : $currentSection[] = $this->makeArray();
                }
                if (isset($currentObject[$currentKey])) {
                    if (!$listMode) {
                        if ($currentObject->count()) {
                            // convert to a list of objects
                            $currentObject->exchangeArray([ clone $currentObject ]);
                        }
                        $listMode = true;
                    }
                    $currentObject = $currentSection[] = $this->makeArray();
                }
                $currentObject[$currentKey] = $this->fromString($match[2]);


            } else if ($this->matchIgnore($line) === false) {
                $multiline = false;
                $indent = null;
                if ($listMode) {
                    // FIXME: this is kinda duplicated code from matchProperty
                    $last = $currentSection->count() ? $currentSection[$currentSection->count() - 1] : null;
                    $last = $last instanceof ArrayObject ? $last : $currentSection;
                    $last[] = rtrim($this->unescape($line), "\n");
                } else {
                    $currentSection[] = rtrim($this->unescape($line), "\n");
                }
            }
        }
        return $this->toArray($data);
    }

    protected function matchSection($line)
    {
        return $this->match('/^\s*\[(.*)\]\s*$/', $line);
    }

    protected function matchProperty($line)
    {
        return $this->match('/^\s*([^\\\\][^:\s]+)\s*:\s*(.*?)\s*$/', $line);
    }

    protected function matchIgnore($line)
    {
        foreach ($this->options['lineIgnorePredicates'] as $fn) {
            if ($fn($line)) {
                return true;
            }
        }
        return false;
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

    protected function fromString($value)
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
            return +$value;
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

    public static function isBlank($line)
    {
        return (boolean) preg_match('/^\s*$/', $line);
    }

    public static function isComment($line)
    {
        return (boolean) preg_match('/^\s*;/', $line);
    }
}
