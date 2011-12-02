<?php
/**
 * Board
 *
 * Represents a 15x15 Scrabble Board
 * Stores values/bonuses, calculates anchors and point values
 *
 * @link https://github.com/stjohnjohnson/ScrabblerBot
 * @author St. John Johnson <st.john.johnson@gmail.com>
 */

namespace Scrabbler;

/** Scrabbler Move */
require_once 'move.php';
/** Scrabbler Lexicon */
require_once 'lexicon.php';

use \Exception;

class Board {
  const SIZE = 15;
  const BONUS_DOUBLE_WORD = '2W';
  const BONUS_DOUBLE_LETTER = '2L';
  const BONUS_TRIPLE_WORD = '3W';
  const BONUS_TRIPLE_LETTER = '3L';
  private $_grid;
  private $_anchors;
  private $_xchecks;
  private static $VALUES = array(
    'A' => 1,
    'B' => 3,
    'C' => 3,
    'D' => 2,
    'E' => 1,
    'F' => 4,
    'G' => 2,
    'H' => 4,
    'I' => 1,
    'J' => 8,
    'K' => 5,
    'L' => 1,
    'M' => 3,
    'N' => 1,
    'O' => 1,
    'P' => 3,
    'Q' => 10,
    'R' => 1,
    'S' => 1,
    'T' => 1,
    'U' => 1,
    'V' => 4,
    'W' => 4,
    'X' => 8,
    'Y' => 4,
    'Z' => 10,
  );

  /**
   * Initialize the game grid
   */
  public function __construct() {
    // Create initial grid
    $this->_grid = array_fill(0, Board::SIZE, array_fill(0, Board::SIZE, ''));
    $this->_anchors = array_fill(0, Board::SIZE, array_fill(0, Board::SIZE, false));
    $this->_xchecks = array_fill(0, Board::SIZE, array_fill(0, Board::SIZE, null));

    $this->setBonus(0, 0, self::BONUS_TRIPLE_WORD);
    $this->setBonus(0, 7, self::BONUS_TRIPLE_WORD);

    $this->setBonus(1, 1, self::BONUS_DOUBLE_WORD);
    $this->setBonus(2, 2, self::BONUS_DOUBLE_WORD);
    $this->setBonus(3, 3, self::BONUS_DOUBLE_WORD);
    $this->setBonus(4, 4, self::BONUS_DOUBLE_WORD);
    $this->setBonus(7, 7, self::BONUS_DOUBLE_WORD);

    $this->setBonus(0, 3, self::BONUS_DOUBLE_LETTER);
    $this->setBonus(2, 6, self::BONUS_DOUBLE_LETTER);
    $this->setBonus(3, 0, self::BONUS_DOUBLE_LETTER);
    $this->setBonus(3, 7, self::BONUS_DOUBLE_LETTER);
    $this->setBonus(6, 2, self::BONUS_DOUBLE_LETTER);
    $this->setBonus(6, 6, self::BONUS_DOUBLE_LETTER);
    $this->setBonus(7, 3, self::BONUS_DOUBLE_LETTER);

    $this->setBonus(1, 5, self::BONUS_TRIPLE_LETTER);
    $this->setBonus(5, 5, self::BONUS_TRIPLE_LETTER);

    $this->findAnchors();
  }

  /**
   * Sets Bonus (basic mirroring)
   *
   * @param int $row
   * @param int $col
   * @param string $value
   */
  private function setBonus($row, $col, $value) {
    $this->setAt($row, $col, $value);
    $this->setAt($col, $row, $value);
    $this->setAt(Board::SIZE - 1 - $row, $col, $value);
    $this->setAt(Board::SIZE - 1 - $col, $row, $value);
    $this->setAt($col, Board::SIZE - 1 - $row, $value);
    $this->setAt($row, Board::SIZE - 1 - $col, $value);
    $this->setAt(Board::SIZE - 1 - $col, Board::SIZE - 1 - $row, $value);
    $this->setAt(Board::SIZE - 1 - $row, Board::SIZE - 1 - $col, $value);
  }

  /**
   * Walks over a Move and returns all steps
   *
   * @param Move $move
   * @return array of coordinates
   */
  public function walk(Move $move) {
    $array = array();
    if ($move->direction === Move::DIR_ACROSS) {
      $len = strlen($move->word);
      for ($i = 0; $i < $len; $i++) {
        $array[] = array($move->row, $move->col + $i);
      }
    } elseif ($move->direction === Move::DIR_DOWN) {
      $len = strlen($move->word);
      for ($i = 0; $i < $len; $i++) {
        $array[] = array($move->row + $i, $move->col);
      }
    }

    return $array;
  }

  /**
   * Places a Move on the board
   *
   * @param Move $move
   * @param array $pool
   * @chainable
   * @return Board
   */
  public function play(Move $move, array &$pool = array()) {
    foreach ($this->walk($move) as $index => $pos) {
      list($row, $col) = $pos;
      // Validate
      if ($this->isUsed($row, $col)) {
        if ($move->word[$index] !== $this->getAt($row, $col)) {
          throw new Exception('Letter is incorrect, have: ' . $this->getAt($row, $col) . ' want: ' . $move->word[$index]);
        }
      } else {
        $this->setAt($row, $col, $move->word[$index]);

        // What letter did we use
        $letter = $move->word[$index];
        if (ctype_lower($letter)) {
          $letter = '?';
        }

        // Cleanup from internal pool (rack or bag)
        unset($pool[array_search($letter, $pool)]);
      }
    }

    $this->findAnchors();

    return $this;
  }

  /**
   * Flips the Board so that up/down words are now left/right
   * while left/right are up/down.
   *
   * This is so we don't have to write two different sets of methods for looking
   * for valid words (row-by-row and col-by-col).  Instead we just need
   * row-by-row.
   *
   * @chainable
   * @return Board
   */
  public function flip() {
    $newGrid = array();
    $newAnchors = array();

    for ($x = 0; $x < Board::SIZE; $x++) {
      for ($y = 0; $y < Board::SIZE; $y++) {
        $newGrid[$x][$y] = $this->_grid[$y][$x];
        $newAnchors[$x][$y] = $this->_anchors[$y][$x];
      }
    }

    $this->_grid = $newGrid;
    $this->_anchors = $newAnchors;

    // Reset xChecks
    $this->_xchecks = array_fill(0, Board::SIZE, array_fill(0, Board::SIZE, null));

    return $this;
  }

  /**
   * Finds all available anchors on the Board.
   *
   * Anchor is defined as a free space connecting to a previously played piece.
   *
   * @chainable
   * @return Board
   */
  private function findAnchors() {
    $has_letters = false;

    // Always clear Center
    $this->_anchors[7][7] = false;

    for ($row = 0; $row < Board::SIZE; $row++) {
      for ($col = 0; $col < Board::SIZE; $col++) {
        if ($this->isUsed($row, $col)) {
          $has_letters = true;
          $this->_anchors[$row][$col] = false;

          if (!$this->isAnchor($row - 1, $col) && !$this->isUsed($row - 1, $col)) {
            $this->_anchors[$row - 1][$col] = true;
          }
          if (!$this->isAnchor($row + 1, $col) && !$this->isUsed($row + 1, $col)) {
            $this->_anchors[$row + 1][$col] = true;
          }
          if (!$this->isAnchor($row, $col - 1) && !$this->isUsed($row, $col - 1)) {
            $this->_anchors[$row][$col - 1] = true;
          }
          if (!$this->isAnchor($row, $col + 1) && !$this->isUsed($row, $col + 1)) {
            $this->_anchors[$row][$col + 1] = true;
          }
        }
      }
    }

    // If no letters, center is anchor
    if (!$has_letters) {
      $this->_anchors[7][7] = true;
    }

    return $this;
  }

  /**
   * Get valid letters to be placed in a specific row/col (checks up/down)
   *
   * @param int $row
   * @param int $col
   * @param Lexicon $lexicon
   * @return array
   * @return null if not anchor
   */
  public function getXChecks($row, $col, Lexicon $lexicon) {
    // Basic caching
    if (isset($this->_xchecks[$row][$col])) {
      return $this->_xchecks[$row][$col];
    }

    // Only calculate for anchors
    if (!$this->isAnchor($row, $col)) {
      return null;
    }

    // Calculate possible values
    $possible = array_combine(range('A', 'Z'), array_fill(0, 26, true));

    // Find letters to the top
    $i = $row;
    $top = '';
    while ($i - 1 >= 0 && $this->isUsed($i - 1, $col)) {
      $i--;
      $top = $this->getAt($i, $col) . $top;
    }
    // Now to the bottom
    $i = $row;
    $bottom = '';
    while ($i + 1 < Board::SIZE && $this->isUsed($i + 1, $col)) {
      $i++;
      $bottom .= $this->getAt($i, $col);
    }

    // Find impossible words
    if (strlen($top.$bottom) !== 0) {
      foreach ($possible as $letter => $value) {
        if (!$lexicon->isWord($top . $letter . $bottom)) {
          unset($possible[$letter]);
        }
      }
    }

    // Cache this check
    $this->_xchecks[$row][$col] = $possible;

    return $possible;
  }

  public function getTopDownPoints($row, $col) {
    // Taken, ignore it
    if ($this->isUsed($row, $col)) {
      return false;
    }

    // Calculate possible values
    $points = 0;
    $has_word = false;

    // Top-Down Word
    // Find letters to the top
    for ($i = $row; $i - 1 > 0 && $this->isUsed($i - 1, $col); $i--) {
      $has_word = true;
      $points += $this->getLetterPoints($this->getAt($i - 1, $col));
    }
    // Now to the bottom
    for ($i = $row; $i + 1 < Board::SIZE && $this->isUsed($i + 1, $col); $i++) {
      $has_word = true;
      $points += $this->getLetterPoints($this->getAt($i + 1, $col));
    }

    // If no word, return false
    if (!$has_word) {
      return false;
    }

    return $points;
  }

  /**
   * Returns the value of a letter
   *
   * @param string $letter
   * @return int
   */
  public function getLetterPoints($letter) {
    // If we have the letter (meaning it's uppercase) return the value
    if (isset(Board::$VALUES[$letter])) {
      return Board::$VALUES[$letter];
    } else {
      return 0;
    }
  }

  /**
   * Returns true if the cell is used
   *
   * @param int $row
   * @param int $col
   * @return bool
   */
  public function isUsed($row, $col) {
    if ($row >= 0 && $row < Board::SIZE &&
        $col >= 0 && $col < Board::SIZE) {
      return strlen($this->_grid[$row][$col]) === 1;
    }

    return false;
  }

  /**
   * Returns true if the spot is an Anchor
   *
   * @param int $row
   * @param int $col
   * @return bool
   */
  public function isAnchor($row, $col) {
    if ($row >= 0 && $row < Board::SIZE &&
        $col >= 0 && $col < Board::SIZE) {
      return $this->_anchors[$row][$col];
    }

    return false;
  }

  /**
   * Sets a cell in the Board
   *
   * @param int $row
   * @param int $col
   * @param string $value
   * @chainable
   * @return Board
   */
  private function setAt($row, $col, $value) {
    if ($row >= 0 && $row < Board::SIZE &&
        $col >= 0 && $col < Board::SIZE) {
      $this->_grid[$row][$col] = $value;
    }

    return $this;
  }

  /**
   * Returns the current value of the cell in the Board
   *
   * @param int $row
   * @param int $col
   * @return string
   * @return false for invalid row/col
   */
  public function getAt($row, $col) {
    if ($row >= 0 && $row < Board::SIZE &&
        $col >= 0 && $col < Board::SIZE) {
      return $this->_grid[$row][$col];
    }

    return false;
  }

  /**
   * Outputs the grid
   *
   * @return string
   */
  public function __toString() {
    $output = '    ';
    for ($col = 0; $col < Board::SIZE; $col++) {
      $output .= '_' . str_pad(chr($col + 65), 3, ' ', STR_PAD_BOTH) . '_';
    }
    $output .= PHP_EOL;
    for ($row = 0; $row < Board::SIZE; $row++) {
      $output .= str_pad($row + 1, 2) . ': ';

      for ($col = 0; $col < Board::SIZE; $col++) {
        $cell = $this->getAt($row, $col);
        $output .= '[';

        if (strlen($cell) === 1) {
          $output .= str_pad($cell, 3, ' ', STR_PAD_BOTH);
        } else {
          $output .= str_pad($cell, 2) . ($this->isAnchor($row, $col) ? '^' : ' ');
        }

        $output .= ']';
      }
      $output .= PHP_EOL;
    }

    return $output;
  }
}