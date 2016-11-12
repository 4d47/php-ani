
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
and serialization that did not change formatting.

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

Section plural determine the initial list type:

    » [groceries]
      name: milk
    « [ 'groceries' => [ [ 'name' => 'milk' ] ] ]

    » [grocery]
      name: milk
    « [ 'grocery' => [ 'name' => 'milk' ] ]

Multiline is started by writing a newline and indenting value!

    » title: Funky
      content:
        Something along
          the lines
    « [ 'title' => 'Funky', 'content' => 'Something along\n  the lines' ]

