<?php

class IniMLTest extends \PHPUnit_Framework_TestCase
{
    public static $tests = [

        "key: value",
        [ 'key' => 'value' ],

        "key  :value",
        [ 'key' => 'value' ],

        "age: 42\nlikes_ice_cream: false",
        [ 'age' => 42, 'likes_ice_cream' => false ],

        "key: null",
        [ 'key' => null ],

        "name: frank
         age: 52
         name: vincent
         age: 64",
        [ [ 'name' => 'frank', 'age' => 52 ],
          [ 'name' => 'vincent', 'age' => 64 ] ],

        "\\key: value",
        [ 'key: value' ],

        "key: value\nJanes is this working ?",
        [ 'key' => 'value', 'Janes is this working ?' ],

        "milk\ncereals",
        [ 'milk', 'cereals' ],

        "[groceries]\nmilk\ncereals",
        [ 'groceries' => [ 'milk', 'cereals' ] ],

        "[groceries]\nname:milk",
        [ 'groceries' => [ [ 'name' => 'milk' ] ] ],

        "[grocery]\nname:milk",
        [ 'grocery' => [ 'name' => 'milk' ] ],

        "title: Funky\ncontent:\n\nSomething along\nthe lines",
        [ 'title' => 'Funky', 'content' => "\nSomething along\nthe lines" ],

    ];

    public function setUp()
    {
        $this->iniML = new IniML();
    }

    public function testAll()
    {
        for ($i = 0; $i < count(self::$tests); $i++) {
            $input = self::$tests[$i++];
            $output = self::$tests[$i];
            $this->assertSame($output, $this->iniML->parse($input));
        }
    }

    public function testEmit()
    {
        $input = "[groceries]\nmilk\napples\nkey: value\n";
        $this->assertSame($input, $this->iniML->emit($this->iniML->parse($input), ': '));

        $input = "\\key: value\n";
        $this->assertSame($input, $this->iniML->emit($this->iniML->parse($input)));

        $this->assertSame("enable: false\n", $this->iniML->emit([ 'enable' => false ]));

        $this->assertSame("enable: null\n", $this->iniML->emit([ 'enable' => null ]));
    }
}
