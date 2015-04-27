
# IniML

IniML is an INI parser and emitter implementation inspired
by [ArchieML](http://archieml.org) (no parsing errors)
and [PHP array](http://php.net/array) (ordered map).

## Usage

Like ArchieML the main goal is to have a syntax that is easy for non-programmer.
It removes a whole lot of syntax and add support for unstructured text.

    > key = value
    < [ 'key' => 'value' ]

Also supports the colon as a key value delimiter.

    > key: value
    < [ 'key' => 'value' ]

Values are always strings:

    > age: 42
    > likes_ice_cream: false
    < [ 'age' => '42', 'likes_ice_cream' => 'false' ]

Duplicate keys creates new map:

    > name: frank
    > age: 52
    > name: vincent
    > age: 64
    < [ [ 'name' => 'frank', 'age' => '52' ],
    <   [ 'name' => 'vincent', 'age' => '62 ] ]

Text lines are *not* ignored (but you can ignore or remove them):

    > key: value
    > Jane is this working ?
    < [ 'key' => 'value', 'Jane is this working ?' ]

So simple "flat array" is easy

    > milk
    > cereals
    < [ 'milk', 'cereals' ]

You can also use sections

    > [groceries]
    > milk
    > cereals
    < [ 'groceries' => [ 'milk', 'cereals' ] ]

Multiline is started by writing a newline!

    > title: Funky
    > content:
    >
    > Something along
    > the lines
    < [ 'title' => 'Funky', 'content' => '\nSomething along\nthe lines' ]

By default blank lines are skipped while parsing because if you don't emit back
they just clutter the result (bunch of `0 => ""`). Adding them allows to keep
document (pretty much) intact when emitting back (you'll always loose whitespace
around keys and which key delimiter was used).

    >
    > key: value
    >
    > A line
    < [ '', 'key' => 'value', '', 'A line' ]

A longer example:

    > headline: head
    > intro: intro
    > [freeformText]
    > para
    > para
    > image: map1.jpg
    > credit: name
    > para
    > image: chart1.jpg
    > credit: name
    > para
    > para
    < [ 'headline' => 'head',
    <   'intro' => 'intro',
    <   'freeformText' => [
    <     'para',
    <     'para',
    <     [ 'image' => 'map1.jpg', 'credit' => 'name' ],
    <     'para',
    <     [ 'image' => 'chart1.png', 'credit' => 'name' ],
    <     'para',
    <     'para' ] ]

## Todo

* implement using regular array not ArrayObject
* write a better test suite
* use dot-notation to create nested objects. (still not convince it's worth it)
* if multiline, must hit a blank line before matching a new keys ?
* should i add support for spaces in section name ? eg. php.ini use them
* end a section with `[]` to return to global namespace ? bah

## Does not support

* comments
* empty values
* array of a single map

