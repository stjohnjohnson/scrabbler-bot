<?php
/**
 * Lexicon
 *
 * Contains a TRE of nodes describing a possible wordlist.
 *  - All letters are uppercase
 *  - All nodes leading out of a previous node are ->[A-Z]
 *  - If a node is final, it contains ->final
 *
 * @link https://github.com/stjohnjohnson/ScrabblerBot
 * @author St. John Johnson <st.john.johnson@gmail.com>
 */

namespace Scrabbler;

/** Scrabbler Board */
require_once 'board.php';

use stdClass;

class Lexicon {
  private $_root;

  public function __construct() {
    $this->_root = new stdClass();
  }

  /**
   * Adds word to Lexicon
   *
   * @param string $word
   * @return bool
   */
  public function addWord($word) {
    $len = strlen($word);

    // Ensure word is uppercased
    if (!ctype_upper($word)) {
      $word = strtoupper($word);
    }

    // Don't put in words less 2 letters or greater than size of board
    if ($len < 2 || $len > BOARD::SIZE) {
      return false;
    }

    // Add to tree
    $node = &$this->_root;
    for ($i = 0; $i < $len; $i++) {
      $letter = $word[$i];

      // If edge doesn't exist, create it
      if (!isset($node->$letter)) {
        $node->$letter = new stdClass();
      }

      // Move onto the next node
      $node = &$node->$letter;
    }

    // End of Word - final
    $node->final = true;

    return true;
  }

  /**
   * Generates a wordlist from a file
   *
   * @param string $filename
   * @return Lexicon
   */
  public static function fromFile($filename) {
    $file = fopen($filename, 'r');
    $lexicon = new Lexicon();

    while ($word = fgets($file)) {
      $lexicon->addWord(rtrim($word));
    }

    fclose($file);

    return $lexicon;
  }

  /**
   * Finds node @ partial
   *
   * @param string $partial
   * @return stdClass
   * @return false if not found
   */
  public function nodeAt($partial) {
    // Ensure only uppercase words
    if (!ctype_upper($partial)) {
      $partial = strtoupper($partial);
    }

    // Start at root
    $node = &$this->_root;
    $len = strlen($partial);

    for ($i = 0; $i < $len; $i++) {
      $letter = $partial[$i];

      // If edge doesn't exist, return false
      if (!isset($node->$letter)) {
        return false;
      }

      // Move onto the next node
      $node = &$node->$letter;
    }

    return $node;
  }

  /**
   * Determines if a String is a word in Lexicon
   *
   * @param string $word
   * @return bool
   */
  public function isWord($word) {
    $node = $this->nodeAt($word);

    return ($node !== false && isset($node->final));
  }

  /**
   * Find all possible Words in a Board given a Rack
   *
   * @param Board $board
   * @param array $rack
   * @return array of Moves
   */
  public function findWords(Board $board, array $rack) {
    $moves = array();

    // Start with Column by Column
    $board->flip();
    for ($row = 0; $row < Board::SIZE; $row++) {
      $moves = array_merge($moves, $this->_findWordsByRow($board, $rack, $row));
    }

    // Reverse the commands
    foreach ($moves as &$move) {
      $move->direction = Move::DIR_DOWN;
      list($move->row, $move->col) = array($move->col, $move->row);
    }

    // Now to Row by Row
    $board->flip();
    for ($row = 0; $row < Board::SIZE; $row++) {
      $moves = array_merge($moves, $this->_findWordsByRow($board, $rack, $row));
    }

    return $moves;
  }

  /**
   * Finds words per each row in a Board
   *
   * @param Board $board
   * @param array $rack
   * @param int $row
   * @return array of Moves
   */
  private function _findWordsByRow(Board $board, array $rack, $row) {
    $return = array();
    $anchor = new stdClass();
    $anchor->row = $row;

    for ($anchor->col = 0; $anchor->col < Board::SIZE; $anchor->col++) {
      if ($board->isAnchor($anchor->row, $anchor->col)) {
        // Two options, if previous is empty or not
        if ($board->isUsed($anchor->row, $anchor->col - 1)) {
          $word = '';
          for ($j = $anchor->col - 1; $j >= 0; $j--) {
            if (!$board->isUsed($anchor->row, $j)) {
              break;
            }
            $word = $board->getAt($anchor->row, $j) . $word;
          }

          $node = $this->nodeAt($word);
          // If we don't have anything at this point, it's not a possible word
          if ($node !== false) {
            $return = array_merge($return,
                  $this->_rightSearch($board, $anchor, "($word)", $rack, $node, $anchor->col));
          }
        } else {
          for ($len = 1; $len < 7 && $anchor->col - $len >= 0; $len++) {
            if ($board->isAnchor($anchor->row, $anchor->col - $len) ||
                $board->isUsed($anchor->row, $anchor->col - $len)) {
              break;
            }
          }
          $len--;

          $return = array_merge($return,
                  $this->_leftSearch($board, $anchor, '', $rack, $this->_root, $len));
        }
      }
    }

    return $return;
  }

  /**
   * Searches left of an Anchor for possible moves given a rack and length
   *
   * @param Board $board
   * @param stdClass $anchor
   * @param string $word
   * @param array $rack
   * @param stdClass $lexicon
   * @param int $limit
   * @return array of Moves
   */
  private function _leftSearch(Board &$board, stdClass $anchor, $word, array $rack, stdClass &$lexicon, $limit) {
    // Search Right
    $return = $this->_rightSearch($board, $anchor, $word, $rack, $lexicon, $anchor->col);

    // If we've reached our limit, end our search
    if ($limit <= 0) {
      return $return;
    }

    // Foreach possible letter, check if it's the end
    foreach ($lexicon as $letter => &$node) {
      //
      if ($letter === 'final') {
        continue;
      }

      // If we have the letter, use it
      if (in_array($letter, $rack)) {
        $index = array_search($letter, $rack);
        unset($rack[$index]);
        $return = array_merge($return, $this->_leftSearch($board, $anchor, $word . $letter, $rack, $node, $limit - 1));
        $rack[$index] = $letter;
      }
      // If we have a blank, use it
      if (in_array('?', $rack)) {
        $index = array_search('?', $rack);
        unset($rack[$index]);
        $return = array_merge($return, $this->_leftSearch($board, $anchor, $word . strtolower($letter), $rack, $node, $limit - 1));
        $rack[$index] = '?';
      }
    }

    return $return;
  }

  /**
   * Searches given a starting part of a word and an anchor, for possible endings
   *
   * @param Board $board
   * @param stdClass $anchor
   * @param string $word
   * @param array $rack
   * @param stdClass $lexicon
   * @param int $col
   * @return array
   */
  private function _rightSearch(Board &$board, stdClass $anchor, $word, array $rack, stdClass &$lexicon, $col) {
    $return = array();

    // If we've passed our bounds, return
    if ($col >= Board::SIZE) {
      return $return;
    }

    // If this space is not used
    if (!$board->isUsed($anchor->row, $col)) {
      if (isset($lexicon->final) && $col > $anchor->col) {
        $move = Move::fromWord($anchor->row, $col - strlen(strtr($word, array(')' => '', '(' => ''))), Move::DIR_ACROSS, $word, $board);

        // Make sure we're using letters
        if ($move->used > 0) {
          $return[] = $move;
        }
      }

      $rowcheck = $board->getXChecks($anchor->row, $col, $this);

      foreach ($lexicon as $letter => &$target) {
        if ($letter === 'final') {
          continue;
        }

        // Skip if it's not possible
        if ($rowcheck !== null && !isset($rowcheck[$letter])) {
          continue;
        }

        // If we have a blank, use it
        if (in_array('?', $rack)) {
          $index = array_search('?', $rack);
          unset($rack[$index]);
          $return = array_merge($return, $this->_rightSearch($board, $anchor, $word . strtolower($letter), $rack, $target, $col + 1));
          $rack[$index] = '?';
        }

        // If we have the letter, use it
        if (in_array($letter, $rack)) {
          $index = array_search($letter, $rack);
          unset($rack[$index]);
          $return = array_merge($return, $this->_rightSearch($board, $anchor, $word . $letter, $rack, $target, $col + 1));
          $rack[$index] = $letter;
        }
      }
    } else {
      // Otherwise, try to use the letter
      $letter = $board->getAt($anchor->row, $col);
      if (isset($lexicon->$letter)) {
        $return = array_merge($return, $this->_rightSearch($board, $anchor, strtr($word . '(' . $letter . ')', array(')(' => '')),
                           $rack, $lexicon->$letter, $col + 1));
      }
    }

    return $return;
  }
}