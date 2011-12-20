<?php
/**
 * Player
 *
 * Sample player with eight (8) different methods
 *  - HighestScore: Plays highest scoring word
 *  - LowestScore: Plays lowest scoring word
 *  - MostWords: Plays move that creates intersects with most words
 *  - MostLetters: Plays word that uses the most tiles
 *  - LeastLetters: Plays word that uses the least tiles
 *  - LongestWord: Plays word that is the longest
 *  - ShortestWord: Plays word that is the shortest
 *  - Training: 50% chance of trading 1 letter or playing least move
 *
 * @link https://github.com/stjohnjohnson/ScrabblerBot
 * @author St. John Johnson <st.john.johnson@gmail.com>
 */

namespace Scrabbler;

/** Scrabbler Game */
require_once 'game.php';
/** Scrabbler Move */
require_once 'move.php';

use \Exception;

class Player extends Game {
  /**
   * Adds default option of HighestScore
   *
   * @param array $options
   * @return Player
   */
  public function __construct(array $options = array()) {
    $defaults = array(
      'method' => 'HighestScore'
    );

    return parent::__construct(array_merge($defaults, $options));
  }

  /**
   * Determines which move to make based on all possible moves
   *
   * @param array $moves
   * @return Move
   */
  public function chooseAction(array $moves) {
    // No moves, pass
    if (empty($moves)) {
      $this->log(self::LOG_DEBUG, 'No Moves');
      // Return a pass
      return Move::fromTrade();
    }

    // Error out if selecting invalid method
    if (!method_exists($this, $this->options['method'])) {
      throw new Exception('Unknown Player Method: ' . $this->options['method']);
    }

    // Training bot only picks between lowest and a trade
    if (strtolower($this->options['method']) === 'training') {
      // Add trade
      $trade = '';
      if ($this->canTrade()) {
        $trade = implode('', array_slice($this->rack, 0, 2));
      }

      $moves = array(
        array_reduce($moves, 'self::LowestScore', reset($moves)),
        Move::fromTrade($trade)
      );
    }

    // Run reduce method on the list of moves
    $best = array_reduce($moves, 'self::' . $this->options['method'], reset($moves));
    $this->log(self::LOG_DEBUG, 'Best Move: ' . $best);

    return $best;
  }

  /**
   * Returns MoveA 50% of the time, MoveB 50% of the time
   *
   * @param Move $moveA
   * @param Move $moveB
   * @return Move
   */
  public static function Training(Move $moveA, Move $moveB) {
    if (mt_rand(0, 1) === 1) {
      return $moveA;
    } else {
      return $moveB;
    }
  }

  /**
   * Returns Move with the Highest Score
   *
   * @param Move $moveA
   * @param Move $moveB
   * @return Move
   */
  public static function HighestScore(Move $moveA, Move $moveB) {
    if ($moveA->score > $moveB->score) {
      return $moveA;
    } elseif ($moveA->score < $moveB->score) {
      return $moveB;
    } else {
      return $moveA;
    }
  }

  /**
   * Returns Move with the Lowest Score
   *
   * @param Move $moveA
   * @param Move $moveB
   * @return Move
   */
  public static function LowestScore(Move $moveA, Move $moveB) {
    if ($moveA->score < $moveB->score) {
      return $moveA;
    } elseif ($moveA->score > $moveB->score) {
      return $moveB;
    } else {
      return $moveA;
    }
  }

  /**
   * Returns Move with the Most Words created
   *
   * @param Move $moveA
   * @param Move $moveB
   * @return Move
   */
  public static function MostWords(Move $moveA, Move $moveB) {
    if ($moveA->words > $moveB->words) {
      return $moveA;
    } elseif ($moveA->words < $moveB->words) {
      return $moveB;
    } else {
      return $moveA;
    }
  }

  /**
   * Returns Move with the Most Tiles used
   *
   * @param Move $moveA
   * @param Move $moveB
   * @return Move
   */
  public static function MostLetters(Move $moveA, Move $moveB) {
    if ($moveA->used > $moveB->used) {
      return $moveA;
    } elseif ($moveA->used < $moveB->used) {
      return $moveB;
    } else {
      return $moveA;
    }
  }

  /**
   * Returns Move with the Least Tiles used
   *
   * @param Move $moveA
   * @param Move $moveB
   * @return Move
   */
  public static function LeastLetters(Move $moveA, Move $moveB) {
    if ($moveA->used < $moveB->used) {
      return $moveA;
    } elseif ($moveA->used > $moveB->used) {
      return $moveB;
    } else {
      return $moveA;
    }
  }

  /**
   * Returns Move with the Longest Word
   *
   * @param Move $moveA
   * @param Move $moveB
   * @return Move
   */
  public static function LongestWord(Move $moveA, Move $moveB) {
    if ($moveA->len > $moveB->len) {
      return $moveA;
    } elseif ($moveA->len < $moveB->len) {
      return $moveB;
    } else {
      return $moveA;
    }
  }

  /**
   * Returns Move with the Shortest Word
   *
   * @param Move $moveA
   * @param Move $moveB
   * @return Move
   */
  public static function ShortestWord(Move $moveA, Move $moveB) {
    if ($moveA->len < $moveB->len) {
      return $moveA;
    } elseif ($moveA->len > $moveB->len) {
      return $moveB;
    } else {
      return $moveA;
    }
  }
}