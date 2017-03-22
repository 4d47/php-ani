<?php
namespace Ani;

use \Doctrine\Common\Inflector\Inflector;
use \ArrayObject;


function emit($array)
{
    assert(is_array($array) || $array instanceof Traversable);
    $out = '';
    foreach ($array as $key => $value) {
        if (is_string($key)) {
            if (is_array($value) || $value instanceof Traversable) {
                $out .= "[$key]\n" . emit($value);
                continue;
            }
            if (is_bool($value)) {
                $value = var_export($value, true);
            } else if (is_null($value)) {
                $value = 'null';
            }
            if (empty($value)) {
                $out .= "$key:\n";
            } elseif (strpos($value, "\n") === false) {
                $out .= "$key: $value\n";
            } else {
                $out .= "$key:\n" . preg_replace('/^(.*)/m', '  $1', $value);
            }
        } else if (is_string($value)) {
            $out .= escape("$value\n");
        } else if (is_array($value) || $value instanceof Traversable) {
            $out .= emit($value);
        }
    }
    return $out;
}

function parse($stream)
{
    if (!is_resource($stream)) {
        $stream = stringResource($stream);
    }

    $data = new ArrayObject();
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

        } else if ($match = matchSection($line)) {
            $multiline = false;
            $indent = null;
            $sectionName = trim($match[1]);
            $listMode = isPlural($sectionName);
            $currentSection = $data[ $sectionName ] = new ArrayObject();

        } else if ($match = matchProperty($line)) {
            $currentKey = $match[1];
            $multiline = empty($match[2]);
            $indent = null;
            $currentObject = $currentSection;
            if ($listMode) {
                $last = $currentSection->count() ? $currentSection[$currentSection->count() - 1] : null;
                $currentObject = $last instanceof ArrayObject ? $last : $currentSection[] = new ArrayObject();
            }
            if (isset($currentObject[$currentKey])) {
                if (!$listMode) {
                    if ($currentObject->count()) {
                        // convert to a list of objects
                        $currentObject->exchangeArray([ clone $currentObject ]);
                    }
                    $listMode = true;
                }
                $currentObject = $currentSection[] = new ArrayObject();
            }
            $currentObject[$currentKey] = $match[2];


        } else {
            $multiline = false;
            $indent = null;
            if ($listMode) {
                // FIXME: this is kinda duplicated code from matchProperty
                $last = $currentSection->count() ? $currentSection[$currentSection->count() - 1] : null;
                $last = $last instanceof ArrayObject ? $last : $currentSection;
                $last[] = rtrim(unescape($line), "\n");
            } else {
                $currentSection[] = rtrim(unescape($line), "\n");
            }
        }
    }
    return toArray($data);
}

function filter($array, $callback = '\Ani\simpleFilter')
{
    foreach ($array as $key => &$value) {
        if (is_array($value)) {
            $array[$key] = filter($value, $callback);
        } else if ($callback($value, $key) == false) {
            unset($array[$key]);
        }
    }
    return $array;
}

function simpleFilter(&$value, $key)
{
    if (is_int($key)) { // line value
        if (preg_match('/^\s*$/', $value)) {
            return false;
        }
        if (preg_match('/^\s*;/', $value)) {
            return false;
        }
    }
    $value = trim($value);
    if (strcasecmp($value, 'true') === 0) {
        $value = true;
    }
    if (strcasecmp($value, 'false') === 0) {
        $value = false;
    }
    if (strcasecmp($value, 'null') === 0) {
        $value = null;
    }
    if (is_numeric($value)) {
        $value = +$value;
    }
    return true;
}

function matchSection($line)
{
    return match('/^\s*\[(.*)\]\s*$/', $line);
}

function matchProperty($line)
{
    return match('/^\s*([^\\\\:\s][^:\s]*):[ \n](.*?)$/', $line);
}

function match($regex, $line)
{
    preg_match($regex, $line, $matches);
    return $matches;
}

function escape($line)
{
    if (matchSection($line) || matchProperty($line)) {
        return preg_replace('/^(\s*)/', '$1\\', $line);
    };
    return $line;
}

function unescape($line)
{
    return preg_replace('/^(\s*)\\\\/', '$1', $line);
}

function toArray($array)
{
    $result = [];
    foreach ($array as $key => $value) {
        $result[$key] = $value instanceof ArrayObject ? toArray($value) : $value;
    }
    return $result;
}

function stringResource($string)
{
    $handle = fopen('php://memory', 'w+');
    fwrite($handle, $string);
    rewind($handle);
    return $handle;
}

function isPlural($word)
{
    return $word != Inflector::singularize($word);
}
