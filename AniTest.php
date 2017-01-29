<?php

class AniTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->ani = new Ani();
    }

	/**
	 * @dataProvider parseProvider
	 */
    public function testParse($actual, $expected)
    {
        $this->assertEquals(eval("return $expected;"), $this->ani->parse($actual));
        $this->assertEquals($actual, $this->ani->emit($this->ani->parse($actual)));
    }

    public function testSimpleFilter()
    {
        $result = $this->ani->parse('

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
            Ani::filter($result, 'Ani::simpleFilter')
        );
    }

    public function parseProvider()
    {
		return yaml_parse_file('test-data.yaml');
    }
}
