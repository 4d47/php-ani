<?php
namespace Ani;

class AniTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @dataProvider parseProvider
	 */
    public function testParse($actual, $expected)
    {
        $this->assertEquals(eval("return $expected;"), parse($actual));
        $this->assertEquals($actual, emit(parse($actual)));
    }

    public function testSimpleFilter()
    {
        $this->assertSame(
            ['name' => 'Bob', 'age' => 34, 'license' => null, 'likes_ice_cream' => true],
            filter(parse('

		            ;;
		            ;; This is the properties of Bob Flanagan
		            ;;

		            name:            Bob
		            age:             34
		            license:         null
		            likes_ice_cream: true
		        '))
        );
    }

    public function parseProvider()
    {
		return yaml_parse_file('test-data.yaml');
    }
}
