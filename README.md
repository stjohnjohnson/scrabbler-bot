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
$ scrabbler path/to/wordlist.txt [BotAlgorithm]
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

To build your own PHP bot, simply extend the `Scrabbler\Game` class, create your
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
    'log' => Game::LOG_INFO,
'lexicon' => $argv[1]
));
$foo->execute();
```

Simulations
-----------

Once you made your own bot, it's best to test out it's ability against other
bots before submitting it to Scrabbler.  Luckily, it's quite easy to do so.

Simply call the `simulate` method with another `Scrabbler\Game` object passed
in.  The play-by-play of the game will be output to the screen as type `Game::LOG_INFO`

In this example, we assume we have the bot from above.  Here we'll test the `DummyBot`
against the built in `HighestScore` Player bot.

```php
<?php
/** Scrabbler Player */
require_once 'src/player.php';

$foo = new DummyBot(array(
    'log' => Game::LOG_INFO,
'lexicon' => $argv[1]
));
$foo->simulate(new Player(array(
    'lexicon' => $foo->lexicon,
     'method' => 'HighestScore'
)));
```

### Sample Output:
```
 6.14 INFO: Game Starting
 6.23 INFO: Me	M --                	0   	MPUNNOO
 6.33 INFO: Opp	FURZE H4            	42  	ZNEUFVR
 8.03 INFO: Me	eN G4               	4   	PUNNOO?
 8.12 INFO: Opp	GON(Z)O 7E          	59  	NVGOTOG
 8.29 INFO: Me	FO(G) E5            	16  	PUNOOTF
... 28 Lines Removed ...
14.29 INFO: Me	(TAJ)Es B2          	93  	YUEEII?
14.46 INFO: Opp	RIELS A5            	437 	RSLREII
14.58 INFO: Me	Y --                	93  	YUEIIKP
14.68 INFO: Opp	(MAX)I K5           	450 	RINI
14.79 INFO: Me	E(T) E12            	99  	UEIIKP
14.90 INFO: Opp	(E)RN I1            	458 	RNIY
15.00 INFO: Me	I(C)K G12           	115 	UIIKP
15.09 INFO: Opp	(K)I 14G            	464 	IY
15.19 INFO: Me	U --                	115 	UIP
15.28 INFO: Opp	--                  	464 	Y
15.38 INFO: Me	P(ICK) G11          	139 	IP
15.47 INFO: Opp	--                  	464 	Y
15.56 INFO: Me	I --                	139 	IU
15.66 INFO: Opp	--                  	464 	Y
15.78 INFO: Me	--                  	139 	U
15.87 INFO: Opp	--                  	464 	Y
15.97 INFO: Me	--                  	139 	U
15.97 INFO: Game Ending: No More Moves
15.97 INFO: __	Score	Rack
15.97 INFO: Me	143	U
15.97 INFO: Opp	460	Y
15.97 INFO:
    _ A __ B __ C __ D __ E __ F __ G __ H __ I __ J __ K __ L __ M __ N __ O _
1 : [   ][   ][   ][ G ][   ][ O ][ G ][ L ][ E ][ D ][   ][   ][   ][   ][   ]
2 : [   ][ T ][ O ][ R ][ A ][ H ][   ][   ][ R ][ A ][ P ][ E ][ R ][ S ][   ]
3 : [   ][ A ][ N ][ A ][ L ][   ][   ][   ][ N ][   ][   ][ Q ][   ][ T ][   ]
4 : [   ][ J ][   ][ V ][   ][   ][ e ][ F ][   ][   ][   ][ U ][   ][ O ][   ]
5 : [ R ][ E ][   ][ E ][ F ][   ][ N ][ U ][   ][   ][ M ][ A ][   ][ U ][   ]
6 : [ I ][ s ][   ][ D ][ O ][ M ][   ][ R ][ I ][ V ][ A ][ L ][   ][ T ][   ]
7 : [ E ][   ][   ][   ][ G ][ O ][ N ][ Z ][ O ][   ][ X ][ I ][   ][ E ][   ]
8 : [ L ][   ][   ][   ][   ][ W ][ E ][ E ][ N ][   ][ I ][ T ][   ][ S ][   ]
9 : [ S ][   ][ C ][   ][ A ][ E ][   ][   ][   ][   ][   ][ Y ][   ][ T ][   ]
10: [   ][ B ][ O ][ W ][ E ][ D ][   ][   ][   ][   ][   ][   ][   ][   ][   ]
11: [   ][ O ][ B ][ A ][   ][   ][ P ][   ][   ][   ][   ][   ][   ][   ][   ]
12: [   ][ R ][ I ][ D ][ E ][   ][ I ][   ][   ][   ][   ][   ][   ][   ][   ]
13: [   ][ N ][ A ][ S ][ T ][ I ][ C ][   ][   ][   ][   ][   ][   ][   ][   ]
14: [ H ][ E ][   ][   ][   ][   ][ K ][ I ][   ][   ][   ][   ][   ][   ][   ]
15: [   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ]
```


Testing
-------

If you want to modify the core code, you're more than welcome to.  There is a
decent series of PHPUnit tests you can run to check that everything is working:

```bash
$ phpunit --coverage-html=.coverage/
```
