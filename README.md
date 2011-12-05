Scrabbler Bot (PHP)
===================

This is a sample PHP bot for the Scrabbler project.  It was designed so that
it can easily be modified for different bot algorithms.

It is based on the paper ["The World’s Fastest Scrabble Program"](http://gtoal.com/wordgames/jacobson+appel/aj.pdf)
by Andrew W. Appel and Guy J. Jacobson.

Follows Input/Output Specifications as described at [Scrabbler Wiki](https://github.com/stjohnjohnson/Scrabbler/wiki/Bot-Specifications)


Usage
-----

To run the bot:

```bash
$ php scrabbler.php path/to/wordlist.txt [BotAlgorithm]
```

The Scrabble Bot has build in support for seven (7) different simple algorithms:

* `HighestScore` (default) - Plays the highest scoring word
* `LowestScore` - Plays the lowest scoring word (not skipping)
* `MostWords` - Plays the word that creates the most words
* `MostLetters` - Plays the word that uses the most letters/tiles
* `LeastLetters` - Plays the word that uses the least letters/tiles
* `LongestWord` - Plays the longest word (including existing titles)
* `ShortestWord` - Plays the shortest word (including existing titles)


Extending
---------

To build your own PHP bot, simply extend the `Scrabbler\game` class, create your
own `chooseAction` method, and don't forget to instantiate an instance of the class.

Here is a simple bot that will trade 1 letter 50% of the time and play a random
move the other 50%.

```php
<?php
namespace Scrabbler;
/** Scrabbler Game */
require_once 'src/game.php';

class DummyBot extends Game {
  public function chooseAction(array $moves) {
    // 50% chance of trading 1 letter (or if there are no moves)
    if (empty($moves) || mt_rand(0, 1) === 1) {
      return Move::fromTrade(reset($this->rack));
    } else {
      return reset($moves);
    }
  }
}
$foo = new DummyBot(array(
    'log' => LOG_INFO,
'lexicon' => $argv[1]
));
$foo->execute();
```


Testing
-------

If you want to modify the core code, you're more than welcome to.  There is a
decent series of PHPUnit tests you can run to check that everything is working:

```bash
$ phpunit --coverage-html=.coverage/
```
