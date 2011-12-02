#!/usr/bin/php
<?php
/**
 * Sample Player
 *
 * Provides samples of various simple Move makers
 *
 * @link https://github.com/stjohnjohnson/ScrabblerBot
 * @author St. John Johnson <st.john.johnson@gmail.com>
 */

namespace Scrabbler;

/** Scrabbler Game */
require_once 'src/game.php';

class ScrabblePlayer extends Game {
  public function chooseAction(array $moves) {
    // No moves, pass
    if (empty($moves)) {
      $this->log(LOG_DEBUG, 'No Moves');
      // Return a pass
      return new Move(true);
    }

    $best = array_reduce($moves, 'Scrabbler\\ScrabblePlayer::' . $this->options['bot'], reset($moves));
    $this->log(LOG_DEBUG, 'Best Move: ' . $best);

    return $best;
  }

  public static function HighestScore($moveA, $moveB) {
    if ($moveA->score < $moveB->score) {
      return $moveB;
    } elseif ($moveA->score < $moveB->score) {
      return $moveB;
    } else {
      return $moveA;
    }
  }

  public static function LowestScore($moveA, $moveB) {
    if ($moveA->score < $moveB->score) {
      return $moveA;
    } elseif ($moveA->score < $moveB->score) {
      return $moveB;
    } else {
      return $moveA;
    }
  }

  public static function MostWords($moveA, $moveB) {
    if ($moveA->words > $moveB->words) {
      return $moveA;
    } elseif ($moveA->words < $moveB->words) {
      return $moveB;
    } else {
      return $moveA;
    }
  }

  public static function MostLetters($moveA, $moveB) {
    if ($moveA->used > $moveB->used) {
      return $moveA;
    } elseif ($moveA->used < $moveB->used) {
      return $moveB;
    } else {
      return $moveA;
    }
  }

  public static function LeastLetters($moveA, $moveB) {
    if ($moveA->used < $moveB->used) {
      return $moveA;
    } elseif ($moveA->used > $moveB->used) {
      return $moveB;
    } else {
      return $moveA;
    }
  }

  public static function LongestWord($moveA, $moveB) {
    if ($moveA->len > $moveB->len) {
      return $moveA;
    } elseif ($moveA->len < $moveB->len) {
      return $moveB;
    } else {
      return $moveA;
    }
  }

  public static function ShortestWord($moveA, $moveB) {
    if ($moveA->len < $moveB->len) {
      return $moveA;
    } elseif ($moveA->len > $moveB->len) {
      return $moveB;
    } else {
      return $moveA;
    }
  }
}
if ($argc < 2) {
  die('Usage: scrabbler.php Wordlist.txt [BotName]' . PHP_EOL);
}
$foo = new ScrabblePlayer(array(
    'log' => LOG_INFO,
'lexicon' => $argv[1],
    'bot' => isset($argv[2]) ? $argv[2] : 'HighestScore'
));
$foo->execute();