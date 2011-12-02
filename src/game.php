<?php
/**
 * Game
 *
 * Abstract class to be extended by Player classes
 * Automatically performs IN/OUT communication, scoring, and move calculation
 * All that is left is to select which move to perform
 *
 * @link https://github.com/stjohnjohnson/ScrabblerBot
 * @author St. John Johnson <st.john.johnson@gmail.com>
 */

namespace Scrabbler;

// Wordlist alone takes ~135mb
ini_set('memory_limit', '1G');

/** Scrabbler Move */
require_once 'move.php';
/** Scrabbler Board */
require_once 'board.php';
/** Scrabbler Lexicon */
require_once 'lexicon.php';

abstract class Game {
  private $_log = LOG_DEBUG;
  private $_start;

  public $options;
  public $rack;
  public $lexicon;
  public $board;
  public $score_mine;
  public $score_opp;
  public $bag;

  // Letter distribution
  public static $DISTRIBUTION = array(
    '?' => 2,
    'A' => 9,
    'B' => 2,
    'C' => 2,
    'D' => 4,
    'E' => 12,
    'F' => 2,
    'G' => 3,
    'H' => 2,
    'I' => 9,
    'J' => 1,
    'K' => 1,
    'L' => 4,
    'M' => 2,
    'N' => 6,
    'O' => 8,
    'P' => 2,
    'Q' => 1,
    'R' => 6,
    'S' => 4,
    'T' => 6,
    'U' => 4,
    'V' => 2,
    'W' => 2,
    'X' => 1,
    'Y' => 2,
    'Z' => 1,
  );

  /**
   * Creates Game Object
   *
   * @param array $options
   */
  public function __construct(array $options = array()) {
    // Step 1: Load Options
    $defaults = array(
          'log' => LOG_DEBUG,
      'lexicon' => '/tmp/lexicon.list'
    );
    $this->options = array_merge($defaults, $options);

    // Step 2: Set Variables
    $this->_start = microtime(true);
    $this->_log = $this->options['log'];

    // Step 3: Load all words in lexicon
    $this->log(LOG_DEBUG, 'Lexicon Loading: ' .
              (memory_get_usage(true) / 1024 / 1024) . 'mb');
    $this->lexicon = Lexicon::fromFile($this->options['lexicon']);
    $this->log(LOG_DEBUG, 'Lexicon Loaded: ' .
              (memory_get_usage(true) / 1024 / 1024) . 'mb');

    // Step 4: Prepare Board
    $this->board = new Board();
    $this->log(LOG_DEBUG, PHP_EOL . $this->board);

    // Step 5: Prepare Variables
    $this->rack = array();
    $this->score_mine = 0;
    $this->score_opp = 0;

    // Step 6: Fill Bag
    $this->bag = array();
    foreach (Game::$DISTRIBUTION as $letter => $count) {
      $this->bag = array_merge($this->bag, array_fill(0, $count, $letter));
    }
    shuffle($this->bag);
    $this->log(LOG_DEBUG, 'Bag filled: ' . implode(',', $this->bag));
  }

  /**
   * Starts IN/OUT conversation
   */
  public function execute() {
    // Announce Ready
    echo 'HELLO' . PHP_EOL;

    // Wait for Commands
    do {
      $this->log(LOG_DEBUG, 'Waiting for Command');
      echo $this->executeCommand(trim(fgets(STDIN))) . PHP_EOL;
    } while (true);
  }

  /**
   * Calculates the best Move to make
   *
   * @param array $moves
   * @return Move
   */
  public abstract function chooseAction(array $moves);

  /**
   * Executes command from Referee
   *
   * @param string $command
   * @return Move
   */
  public function executeCommand($command) {
    if (strlen($command) == 0) {
      // End of game?
      exit();
    }

    // Get data from command
    list($new_letters, $opponent_move) = explode(':', $command);

    // Get new letters
    $new_letters = str_split($new_letters);
    $this->log(LOG_DEBUG, 'New Letters: ' . implode(',', $new_letters));

    // Add to rack
    $this->rack = array_merge($this->rack, $new_letters);
    $this->log(LOG_DEBUG, 'Current Rack: ' . implode(',', $this->rack));

    // Remove letters from bag
    foreach ($new_letters as $letter) {
      unset($this->bag[array_search($letter, $this->bag)]);
    }
    $this->log(LOG_DEBUG, 'Bag: ' . implode(',', $this->bag));

    // Figure out opponent's move
    if (!empty($opponent_move)) {
      $opponent_move = Move::fromString($opponent_move, $this->board);
      $this->board->play($opponent_move, $this->bag);
      $this->score_opp += $opponent_move->score;
      $this->log(LOG_DEBUG, 'Opponent Move: ' . $opponent_move . ' ' . $opponent_move->score);
      $this->log(LOG_DEBUG, PHP_EOL . $this->board);
    }

    // Find words to play
    $moves = $this->lexicon->findWords($this->board, $this->rack);
    $this->log(LOG_DEBUG, 'Number of moves: ' . count($moves));

    // Choose Best Move
    $move = $this->chooseAction($moves);

    // Play internally
    $this->board->play($move, $this->rack);
    $this->score_mine += $move->score;
    $this->log(LOG_DEBUG, 'Self Move: ' . $move . ' ' . $move->score);
    $this->log(LOG_DEBUG, PHP_EOL . $this->board);

    // Output
    return $move;
  }

  /**
   * Calculate all possible trade moves
   *
   * @param string $current
   * @param string $rack
   * @return array of Moves
   */
  public function possibleTrades($current, $rack) {
    $result = array(Move::fromTrade($current));
    if (empty($rack)) {
      return $result;
    }

    foreach ($rack as $i => $item) {
      unset($rack[$i]);
      $result = array_merge($result, $this->possibleTrades($current . $item, $rack));
      $rack[$i] = $item;
    }

    return array_unique($result);
  }

  /**
   * Simple Message Logging
   *
   * @param int $level
   * @param string $message
   */
  public function log($level, $message) {
    if ($level <= $this->_log) {
      error_log(sprintf('%5.2f: %s', microtime(true) - $this->_start, $message));
    }
  }
}