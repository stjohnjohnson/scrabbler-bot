<?php
/**
 * Move
 *
 * Represents a Scrabble Move (trade or play)
 * Calculates points, tiles used, etc.
 *
 * @link https://github.com/stjohnjohnson/ScrabblerBot
 * @author St. John Johnson <st.john.johnson@gmail.com>
 */

namespace Scrabbler;

/** Scrabbler Board */
require_once 'board.php';

use \Exception;

class Move {
  const DIR_ACROSS = 1;
  const DIR_DOWN = -1;

  public $is_trade;
  public $score = 0;
  public $rank = 0;
  public $used = 0;
  public $row = -1;
  public $col = -1;
  public $direction;
  public $tiles = array();
  public $multiples = array();

  public function __construct($trade) {
    $this->is_trade = $trade;
  }

  /**
   * Returns position of the move with direction (5A vs A5)
   *
   * @return string
   * @return false if a trade
   */
  public function position() {
    if ($this->is_trade) {
      return false;
    }

    $row = $this->row + 1;
    $col = chr(ord('A') + $this->col);

    if ($this->direction === self::DIR_ACROSS) {
      return $row . $col;
    } else {
      return $col . $row;
    }
  }

  /**
   * Assigns a score to the Move
   *
   * @param Board $board
   * @return Move
   * @chainable
   */
  private function assignScore(Board $board) {
    $this->score = 0;

    $wordscore = 0;
    $multiplier = 1;

    // Loop over each letter
    for ($i = 0; $i < $this->len; $i++) {
      $updown = 0;
      $cellscore = 0;

      if ($board->isUsed($this->row, $this->col + $i)) {
        // Always add local points
        $cellscore = $board->getLetterPoints($board->getAt($this->row, $this->col + $i));
      } else {
        // Always add local points
        $cellscore = $board->getLetterPoints($this->word[$i]);

        // Get existing points up and down
        $updown = $board->getTopDownPoints($this->row, $this->col + $i);

        if ($updown !== false) {
          // Keep track of number of words used
          $this->words += 1;
          $updown += $cellscore;
        }

        switch ($board->getAt($this->row, $this->col + $i)) {
          case Board::BONUS_DOUBLE_LETTER:
            $cellscore *= 2;
            $this->multiples[] = Board::BONUS_DOUBLE_LETTER;
            break;
          case Board::BONUS_TRIPLE_LETTER:
            $cellscore *= 3;
            $this->multiples[] = Board::BONUS_TRIPLE_LETTER;
            break;
          case Board::BONUS_DOUBLE_WORD:
            $updown *= 2;
            $multiplier *= 2;
            $this->multiples[] = Board::BONUS_DOUBLE_WORD;
            break;
          case Board::BONUS_TRIPLE_WORD:
            $updown *= 3;
            $multiplier *= 3;
            $this->multiples[] = Board::BONUS_TRIPLE_WORD;
            break;
        }
      }

      $wordscore += $cellscore;
      $this->score += $updown;
    }

    // BINGO
    if ($this->used === 7) {
      $this->score += 50;
    }

    sort($this->multiples);
    $this->score += ($wordscore * $multiplier);

    return $this;
  }

  public function __toString() {
    if ($this->is_trade) {
      return trim(implode('', $this->tiles) . ' --');
    } else {
      return $this->raw . ' ' . $this->position();
    }
  }

  /**
   * Creates a Move based on a word and position on the board
   *
   * Passing Board will assign score.
   *
   * @param int $row
   * @param int $col
   * @param int $direction
   * @param string $word
   * @param Board $board
   * @return Move
   */
  public static function fromWord($row, $col, $direction, $word, $board = null) {
    $move = new Move(false);

    $move->row = $row;
    $move->col = $col;
    $move->direction = $direction;

    $move->raw = $word;
    $move->words = 1;
    $move->word = strtr($word, array(')' => '', '(' => ''));
    $move->len = strlen($move->word);
    $move->tiles = str_split(preg_replace(array('/[a-z]+/','/\([A-Za-z?]+\)/'), array('?',''), $move->raw));
    sort($move->tiles);
    $move->used = count($move->tiles);
    $move->score = 0;
    $move->multiples = array();

    if ($board !== null) {
      $move->assignScore($board);
    }

    return $move;
  }

  /**
   * Creates a Move from Trade
   *
   * @param string $letters
   * @return Move
   */
  public static function fromTrade($letters = '') {
    $move = new Move(true);
    $move->tiles = ($letters === '' ? array() : str_split($letters));
    $move->used = count($move->tiles);
    $move->score = 0;
    sort($move->tiles);

    return $move;
  }

  /**
   * Creates a Move object from a String
   *
   * @param string $string
   * @param Board $board
   * @return Move
   */
  public static function fromString($string, $board = null) {
    // Is Trade
    if (strstr($string, '--') !== false) {
      $move = Move::fromTrade(trim($string, ' -'));
    } else {
      list($word, $pos) = explode(' ', $string);

      // Starts with LETTER then NUMBER - Down move
      if (preg_match('/^([A-Z])([0-9]+)$/', $pos, $matches)) {
        $direction = self::DIR_DOWN;
        $col = ord($matches[1]) - ord('A');
        $row = $matches[2] - 1;
      } else

      // Starts with NUMBER then LETTER - Across move
      if (preg_match('/^([0-9]+)([A-Z])$/', $pos, $matches)) {
        $direction = self::DIR_ACROSS;
        $col = ord($matches[2]) - ord('A');
        $row = $matches[1] - 1;
      } else {
        throw new Exception('Invalid Placement');
      }

      $move = Move::fromWord($row, $col, $direction, $word, $board);
    }

    return $move;
  }
}