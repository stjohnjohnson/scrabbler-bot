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

use Exception;

abstract class Game {
  const LOG_DISABLED = 0;
  const LOG_ERROR = 1;
  const LOG_WARNING = 2;
  const LOG_INFO = 3;
  const LOG_DEBUG = 4;
  private $_log_levels = array(
    self::LOG_DISABLED => 'NONE',
       self::LOG_ERROR => 'ERROR',
     self::LOG_WARNING => 'WARNING',
        self::LOG_INFO => 'INFO',
       self::LOG_DEBUG => 'DEBUG'
  );

  private $_log = self::LOG_DISABLED;
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
          'log' => self::LOG_DISABLED,
      'lexicon' => '/usr/share/dict/words'
    );
    $this->options = array_merge($defaults, $options);

    // Step 2: Set Variables
    $this->_start = microtime(true);
    $this->_log = $this->options['log'];

    // Step 3: Load all words in lexicon
    $this->log(self::LOG_DEBUG, 'Lexicon Loading: ' .
              (memory_get_usage(true) / 1024 / 1024) . 'mb');
    // If a Lexicon is passed in, use it
    if (is_a($this->options['lexicon'], 'Scrabbler\\Lexicon')) {
      $this->lexicon = $this->options['lexicon'];
    } else {
      $this->lexicon = Lexicon::fromFile($this->options['lexicon']);
    }
    $this->log(self::LOG_DEBUG, 'Lexicon Loaded: ' .
              (memory_get_usage(true) / 1024 / 1024) . 'mb');

    // Step 4: Prepare Board
    $this->board = new Board();
    $this->log(self::LOG_DEBUG, PHP_EOL . $this->board);

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
    $this->log(self::LOG_DEBUG, 'Bag filled: ' . implode(',', $this->bag));
  }

  /**
   * Starts IN/OUT conversation
   *
   * @param resource $input
   */
  public function execute($input = STDIN) {
    // Announce Ready
    $response = 'HELLO';

    // Wait for Commands
    do {
      echo $response . PHP_EOL;
      $this->log(self::LOG_DEBUG, 'Waiting for Command');
      $response = $this->executeCommand(trim(fgets($input)));
    } while ($response !== null);
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
      return null;
    }

    // Get data from command
    list($new_letters, $opponent_move) = explode(':', $command);

    // Get new letters
    $new_letters = str_split($new_letters);
    $this->log(self::LOG_DEBUG, 'New Letters: ' . implode(',', $new_letters));

    // Add to rack
    $this->rack = array_merge($this->rack, $new_letters);
    $this->log(self::LOG_DEBUG, 'Current Rack: ' . implode(',', $this->rack));

    // Remove letters from bag
    foreach ($new_letters as $letter) {
      unset($this->bag[array_search($letter, $this->bag)]);
    }
    $this->log(self::LOG_DEBUG, 'Bag: ' . implode(',', $this->bag));

    // Figure out opponent's move
    if (!empty($opponent_move)) {
      $opponent_move = Move::fromString($opponent_move, $this->board);
      $this->board->play($opponent_move, $this->bag);
      $this->score_opp += $opponent_move->score;
      $this->log(self::LOG_DEBUG, 'Opponent Move: ' . $opponent_move . ' ' . $opponent_move->score);
      $this->log(self::LOG_DEBUG, PHP_EOL . $this->board);
    }

    // Find words to play
    $moves = $this->lexicon->findWords($this->board, $this->rack);
    $this->log(self::LOG_DEBUG, 'Number of moves: ' . count($moves));

    // Choose Best Move
    $move = $this->chooseAction($moves);

    // Play internally
    $this->board->play($move, $this->rack);
    $this->score_mine += $move->score;
    $this->log(self::LOG_DEBUG, 'Self Move: ' . $move . ' ' . $move->score);
    $this->log(self::LOG_DEBUG, PHP_EOL . $this->board);

    // Remove tiles from rack and into bag
    if ($move->is_trade) {
      $this->bag = array_merge($move->tiles, $this->bag);
      foreach ($move->tiles as $tile) {
        $i = array_search($tile, $this->rack);
        if ($i !== false) {
          unset($this->rack[$i]);
        }
      }
    }

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
   * Simulates an entire game against another Bot
   *
   * @param Game $opponent
   * @param bool $meFirst
   * @return bool
   */
  public function simulate(Game $opponent, $meFirst = true) {
    // Use some internal variables
    $board = new Board();
    $bag = $this->bag;

    $scores = array(0, 0);
    $previous = array();
    $players = array($this, $opponent);
    $trades = 0;

    // Generate letters
    shuffle($bag);
    $previous[0] = array(implode('', array_splice($bag, 0, 7)), '--');
    $previous[1] = array(implode('', array_splice($bag, 0, 7)), '--');
    $racks = array(str_split($previous[0][0]), str_split($previous[1][0]));

    $this->log(self::LOG_INFO, 'Game Starting');

    for ($index = (int)!$meFirst;; $index = (int)!$index) {
      try {
        // Run command on player
        $move = '';
        $move = (string) $players[$index]->executeCommand(implode(':', $previous[$index]));
        $move = Move::fromString($move, $board);

        // Validate move
        $board->isValidMove($move, $this->lexicon, $racks[$index]);

        // Store score
        $scores[$index] += $move->score;

        // Log the move
        $this->log(self::LOG_INFO, ($index === 0 ? 'Me' : 'Opp') . "\t" .
                              str_pad($move, 20) . "\t" .
                              str_pad($scores[$index], 4) . "\t" .
                              implode('', $racks[$index]));

        // Play move
        $board->play($move, $racks[$index]);

        // Store
        $previous[(int)!$index][1] = (string) $move;

        // Pick new letters
        shuffle($bag);
        if ($move->used > count($bag)) {
          $move->used = count($bag);
        }
        $previous[$index][0] = implode('', array_splice($bag, 0, $move->used));

        // Trade letters
        if ($move->is_trade) {
          $bag = array_merge($move->tiles, $bag);
          foreach ($move->tiles as $tile) {
            $i = array_search($tile, $racks[$index]);
            if ($i !== false) {
              unset($racks[$index][$i]);
            }
          }

          $trades++;
        } else {
          $trades = 0;
        }

        // Add letters to internal rack
        if (!empty($previous[$index][0])) {
          $racks[$index] = array_merge($racks[$index], str_split($previous[$index][0]));
        }

        // If we've traded 6 times in a row or our rack is empty, end game
        if ($trades >= 6 || empty($racks[$index])) {
          $this->log(self::LOG_INFO, "Game Ending: " . ($trades >= 6 ? 'No More Moves' : 'Out of Letters'));
          break;
        }
      } catch (Exception $e) {
        $this->log(self::LOG_ERROR, "Error with " . ($index === 0 ? 'Me' : 'Opp'));
        $this->log(self::LOG_ERROR, "P$index\t" . $e->getMessage());
        $this->log(self::LOG_ERROR, "P$index\tCmd: " . implode(':', $previous[$index]));
        $this->log(self::LOG_ERROR, "P$index\tMove: " . $move);
        $this->log(self::LOG_ERROR, "P$index\tRack: " . implode('', $racks[$index]));
        $this->log(self::LOG_ERROR, "P$index\tBoard: " . PHP_EOL . $board);
        return false;
      }
    }

    // Empty all the points
    for ($i = 0; $i < 2; $i++) {
      foreach ($racks[$i] as $letter) {
        $scores[$i] -= $board->getLetterPoints($letter);
        if (empty($previous[$index][0])) {
          $scores[$index] += $board->getLetterPoints($letter);
        }
      }
    }

    // End game
    $this->log(self::LOG_INFO, "__\tScore\tRack");
    $this->log(self::LOG_INFO, "Me\t{$scores[0]}\t" . implode('', $racks[0]));
    $this->log(self::LOG_INFO, "Opp\t{$scores[1]}\t" . implode('', $racks[1]));
    $this->log(self::LOG_INFO, PHP_EOL . $board->toString(false));

    return true;
  }

  /**
   * Determines if we can trade
   *
   * This is only true if we have at least 7 letters in the bag
   * (and not in opp rack)
   *
   * @return bool
   */
  public function canTrade() {
    return (count($this->bag) - 7 >= 7);
  }

  /**
   * Simple Message Logging
   *
   * @param int $level
   * @param string $message
   */
  public function log($level, $message) {
    if ($level <= $this->_log) {
      error_log(sprintf('%5.2f %s: %s', microtime(true) - $this->_start, $this->_log_levels[$level], $message));
    }
  }

  /**
   * Magic method for cloning
   *  - Perform deep clone on board
   */
  public function __clone() {
    $this->board = clone $this->board;
  }
}