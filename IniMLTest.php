<?php

class IniMLTest extends \PHPUnit_Framework_TestCase
{
    public static $tests = [
        "key: value",
        [ 'key' => 'value' ],

        "key = value",
        [ 'key' => 'value' ],

        "key  :value",
        [ 'key' => 'value' ],

        "age: 42\nlikes_ice_cream: false",
        [ 'age' => '42', 'likes_ice_cream' => 'false' ],

        "name: frank
         age: 52
         name: vincent
         age: 64",
        [ [ 'name' => 'frank', 'age' => '52' ],
          [ 'name' => 'vincent', 'age' => '64' ] ],

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

    public function testAll()
    {
        for ($i = 0; $i < count(self::$tests); $i++) {
            $input = self::$tests[$i++];
            $output = self::$tests[$i];
            $this->assertSame($output, IniML::load($input));
        }
    }

    public function testEmit()
    {
        $input = "[groceries]\nmilk\napples\nkey: value\n";
        $this->assertSame($input, IniML::emit(IniML::load($input), ': '));

        $input = "\\key: value\n";
        $this->assertSame($input, IniML::emit(IniML::load($input)));
    }
}
