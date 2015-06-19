
# IniML

IniML is an INI parser and emitter implementation inspired
by [ArchieML](http://archieml.org) (no parsing errors)
and [PHP array](http://php.net/array) (ordered map).

## Usage

Like ArchieML the main goal is to have a syntax that is easy for non-programmers.
Unlike ArchieML it is kept simple and lives in the INI subset.

    → key: value
    ← [ 'key' => 'value' ]

The delimiter defaults to colon but can be changed:

    → key = value
    ← [ 'key' => 'value' ]

Duplicate keys creates new map:

    → name: frank
      age: 52
      name: vincent
      age: 64
    ← [ [ 'name' => 'frank', 'age' => '52' ],
        [ 'name' => 'vincent', 'age' => '62 ] ]

Text lines are *not* ignored (but can be ignored or filtered):

    → key: value
      Jane is this working ?
    ← [ 'key' => 'value', 'Jane is this working ?' ]

A text line that looks like a key can be escaped with backslash:

    → \key: value
    ← [ 'key: value' ]

So simple "flat array" is easy:

    → milk
      cereals
    ← [ 'milk', 'cereals' ]

And sections:

    → [groceries]
      milk
      cereals
    ← [ 'groceries' => [ 'milk', 'cereals' ] ]

Section plural determine the list type:

    → [groceries]
      name: milk
    ← [ 'groceries' => [ [ 'name' => 'milk' ] ] ]

    → [grocery]
      name: milk
    ← [ 'grocery' => [ 'name' => 'milk' ] ]

Multiline is started by writing a newline!

    → title: Funky
      content:
     
      Something along
      the lines
    ← [ 'title' => 'Funky', 'content' => '\nSomething along\nthe lines' ]

By default blank lines are skipped while parsing because if you don't emit back
they just clutter the result. Adding them allows to keep document (pretty much)
intact when emitting back (you'll always loose whitespace around keys and which
key delimiter was used).

    →
      key: value
     
      A line
    ← [ '', 'key' => 'value', '', 'A line' ]

A longer example:

    → headline: head
      intro: intro
      [freeformText]
      para
      para
      image: map1.jpg
      credit: name
      para
      image: chart1.jpg
      credit: name
      para
      para
    ← [ 'headline' => 'head',
        'intro' => 'intro',
        'freeformText' => [
          'para',
          'para',
          [ 'image' => 'map1.jpg', 'credit' => 'name' ],
          'para',
          [ 'image' => 'chart1.png', 'credit' => 'name' ],
          'para',
          'para' ] ]

## Todo

* put options to constructor
* make ArrayObject a dependency, eg. could use a CaseInsensitiveArrayObject
* write a better test suite
* if multiline, must hit a blank line before matching a new keys ?
* should i add support for spaces in section name ? eg. php.ini use them

