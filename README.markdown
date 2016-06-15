
# IniML

A format for structured text that is:

- Easy for authoring
- Easy to embed other formats
- s == emit(parse(s))

Differences from INI:
- No parsing errors
- Use colon instead of equal
- Has multiline value
- Has lists

**Context** Built out from a port of ArchieML.
Wanted to replace a bunch of YAML files with a more simple syntax
and serialization that did not affected formatting.


## Usage

    » key: value
    « [ 'key' => 'value' ]

Duplicate keys creates a list of objects:

    » name: frank
      age: 52
      name: vincent
      age: 64
    « [
        [
          'name' => 'frank',
          'age' => 52
        ],
        [
          'name' => 'vincent',
          'age' => 62
        ]
      ]

Text lines are *not* ignored:

    » key: value
      Jane is this working ?
    « [
        'key' => 'value',
        'Jane is this working ?'
      ]

A text line that looks like a key can be escaped with backslash:

    » \key: value
    « [ 'key: value' ]

So simple "flat array" is easy:

    » milk
      cereals
    « [ 'milk', 'cereals' ]

And sections:

    » [groceries]
      milk
      cereals
    « [ 'groceries' => [ 'milk', 'cereals' ] ]

Section plural determine the list type:

    » [groceries]
      name: milk
    « [ 'groceries' => [ [ 'name' => 'milk' ] ] ]

    » [grocery]
      name: milk
    « [ 'grocery' => [ 'name' => 'milk' ] ]

Multiline is started by writing a newline!

    » title: Funky
      content:

      Something along
      the lines
    « [ 'title' => 'Funky', 'content' => '\nSomething along\nthe lines' ]

When the first line of multiline is indented, it embed raw text:

    » content:
        foo: bar
        [doc]
    « [ 'content' => 'foo: bar\n[doc]' ]

By default blank lines are skipped while parsing because if you don't emit back
they just clutter the result. Adding them allows to keep document (pretty much)
intact when emitting back (you'll always loose whitespace around keys).

    »
      key: value

      A line
    « [ '', 'key' => 'value', '', 'A line' ]


## Todo

* Configurable functions, eg ignoreBlankLines, ignoreComments
* Add parsing of dates/time and other objects

## FAQ

Why no section nesting ?
Section plural ... yikes!

