<?php

class IniMLTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->iniML = new IniML();
    }

	/**
	 * @dataProvider parseProvider
	 */
    public function testParse($actual, $expected)
    {
        $this->assertEquals(eval("return $expected;"), $this->iniML->parse($actual));
        $this->assertEquals($actual, $this->iniML->emit($this->iniML->parse($actual)));
    }

    public function testSimpleFilter()
    {
        $result = $this->iniML->parse('

            ;;
            ;; This is the properties of Bob Flanagan
            ;;

            name: Bob
            age:  34
            license: null
            likes_ice_cream: true
        ');
        $this->assertSame(
            ['name' => 'Bob', 'age' => 34, 'license' => null, 'likes_ice_cream' => true],
            IniML::filter($result, 'IniML::simpleFilter')
        );
    }

    public function parseProvider()
    {
		return yaml_parse_file('test-data.yaml');
    }
}
