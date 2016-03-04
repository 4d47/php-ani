<?php

class IniMLTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->iniML = new IniML();
    }

    public function testBasicKeyValue()
    {
        $this->assertEquals(['key' => 'value'],
                            $this->iniML->parse("key: value"));
    }

    public function testDelimiterOption()
    {
        $this->iniML->options['delimiter'] = '=';
        $this->assertEquals(
            [ 'key' => 'value' ],
            $this->iniML->parse('key = value')
        );
    }

    public function testDuplicateKeys()
    {
        $this->assertEquals(
            [ [ 'name' => 'frank', 'age' => 52 ],
              [ 'name' => 'vincent', 'age' => 64 ] ],
            $this->iniML->parse('name: frank
age: 52
name: vincent
age: 64')
        );
    }

    public function testLine()
    {
        $this->assertEquals(
            [ 'key' => 'value', 'Jane is this working ?' ],
            $this->iniML->parse('key: value
Jane is this working ?
')
        );
    }

    public function testLineEscape()
    {
        $this->assertEquals(
            [ 'key: value' ],
            $this->iniML->parse('\\key: value')
        );
    }

    public function testMultipleLines()
    {
        $this->assertEquals(
            [ 'milk', 'cereals' ],
            $this->iniML->parse('milk
cereals')
        );
    }

    public function testSections()
    {
        $this->assertEquals(
            [ 'groceries' => [ 'milk', 'cereals' ] ],
            $this->iniML->parse('[groceries]
milk
cereals')
        );
    }

    public function testSectionPlural()
    {
        $this->assertEquals(
            [ 'groceries' => [ [ 'name' => 'milk' ] ] ],
            $this->iniML->parse('[groceries]
name: milk')
        );
    }

    public function testSectionSingular()
    {
        $this->assertEquals(
            [ 'grocery' => [ 'name' => 'milk' ] ],
            $this->iniML->parse('[grocery]
name: milk')
        );
    }

    public function testMultiline()
    {
        $this->assertEquals(
            [ 'title' => 'Funky',
              'content' => 'Something along
the lines'
            ],
            $this->iniML->parse('title: Funky
content:
Something along
the lines')
        );
    }

    public function testEmitSection()
    {
        $input = "[groceries]\nmilk\napples\nkey: value\n";
        $this->assertSame($input,
                          $this->iniML->emit($this->iniML->parse($input)));
    }

    public function testEmitEscape()
    {
        $input = "\\key: value\n";
        $this->assertSame($input,
                          $this->iniML->emit($this->iniML->parse($input)));
    }

    public function testEmitBoolean()
    {
        $this->assertSame("enable: false\n",
                          $this->iniML->emit([ 'enable' => false ]));
    }

    public function testEmitNull()
    {
        $this->assertSame("enable: null\n",
                          $this->iniML->emit([ 'enable' => null ]));
    }
}
