<?php
namespace Masterword;

use Discord\Parts\Channel\Channel;
use Discord\Parts\User\User;
use Masterword\Guess;

class Masterword {
	/* @var array $guesses */
	protected $guesses;

	/* @var array $scores */
	protected $scores;

	/* @var string $word */
	protected $word;

	/* @var array $puzzleList */
	protected $puzzleList;

	/* @var array $possibleList */
	protected $possibleList;

	/* @var boolean $needNext */
	protected $needNext;

	/* @var int $roundNum */
	protected $roundNum;
	
	/* @var int $maxRounds */
	protected $maxRounds;

	/* @var Channel $channel */
	protected $channel;

	/* @var User $admin */
	protected $admin;

	/**
	 * Start up the game
	 */
	public function __construct($channel, $admin, $puzzlesWordsFile, $possibleWordsFile, $maxRounds) {
		$this->channel      = $channel;
		$this->admin        = $admin;
		$this->guesses      = [];
		$this->scores       = [];
		$this->puzzleList   = explode("\n", file_get_contents($puzzlesWordsFile));
		$this->possibleList = explode("\n", file_get_contents($possibleWordsFile));
		$this->roundNum     = 0;
		$this->maxRounds    = $maxRounds;

		$this->sendInitMessage();
		$this->nextWord();
	}

	/**
	 * Have a player guess a word
	 * @param string $guess Their guess
	 * @return bool If there was an issue
	 */
	public function onGuess(Guess $guess) {
		if (!array_key_exists($guess->user->id, $this->scores)) {
			$this->scores[$guess->user->id] = new \Masterword\User($guess->user);
		}

		//Correct
		if ($guess->text === $this->word) {
			//Winner
			$guess->points = [10];
			$this->scores[$guess->user->id]->addScore(15);
			$this->guesses[] = $guess;

			$this->sendResults();
			$this->nextWord();
			return true;
		}

		//Error checking
		if (strlen($guess->text) !== strlen($this->word)) {
			$guess->error = "Invalid length. Should be " . strlen($this->word) . " chars.";
			return false;
		}
		if (!in_array($guess->text, $this->possibleList)) {
			$guess->error = "\"{$guess->text}\" is not a word in my dictionary.";
			return false;
		}

		//Already guessed it before
		if (count(array_filter($this->guesses, function ($tguess) use ($guess) {
			/* @var Guess $tguess */
			return $tguess->text === $guess->text;
		}))) {
			$guess->error = "\"{$guess->text}\" has already been guessed.";
			return false;
		}

		$tchars = str_split($this->word);
		$gchars = str_split($guess->text);
		$counts = count_chars($this->word, 1);

		$points = [];
		for ($i = 0; $i < count($gchars); $i ++) {
			$tchar = $tchars[$i];
			$gchar = $gchars[$i];

			if ($tchar === $gchar) {
				$points[] = 2;
				$counts[ord($gchar)] --;
			} else {
				if ($counts[ord($gchar)] > 0) {
					$points[] = 1;
					$counts[ord($gchar)] --;
				}
			}
		}
		
		rsort($points);
		$guess->points = $points;

		$this->scores[$guess->user->id]->addScore(array_sum($points));
		$this->guesses[] = $guess;

		return true;
	}

	/**
	 * Get the game's results
	 * @return array
	 */
	public function getGuesses() {
		return $this->guesses;
	}

	/**
	 * Get the game's results
	 * @return array
	 */
	public function getScores() {
		return $this->scores;
	}

	/**
	 * Get the current round number
	 * @return int
	 */
	public function getRoundNum() {
		return $this->roundNum;
	}

	/**
	 * Get the maximum number of rounds
	 * @return int
	 */
	public function getMaxRounds() {
		return $this->maxRounds;
	}

	/**
	 * Get if the results should be displayed
	 * @return bool
	 */
	public function getDisplayResults() {
		return count($this->guesses) > 0 && count($this->guesses) % 3 === 0;
	}

	/**
	 * Get the next word
	 */
	protected function nextWord() {
		$this->word = strtolower($this->puzzleList[array_rand($this->puzzleList)]);
		$this->guesses = [];
		$this->roundNum ++;

		echo("New word: {$this->word}\n");
		$this->sendResults();
	}

	protected function sendInitMessage() {
		$this->channel->trySendMessage("Init Masterword in channel " . $this->channel->name);
		$this->channel->trySendMessage("Admin is " . $this->admin->username);
	}

	public function sendError($error) {
		$this->channel->trySendMessage("Error in guess: {$error}");
	}

	public function sendResults() {
		$reply = "Round " . $this->getRoundNum() . " / " . $this->getMaxRounds();
		$reply .= "\nGuesses:";

		foreach ($this->getGuesses() as $guess) {
			/* @var Guess $guess */
			$reply .= "\n" . $guess->format();
		}

		$reply .= "\n\nScores:";

		$scores = $this->getScores();
		usort($scores, function ($user1, $user2) {
			/* @var \Masterword\User $user1 */
			/* @var \Masterword\User $user2 */
			if ($user1->getScore() > $user2->getScore())
				return -1;
			if ($user1->getScore() < $user2->getScore())
				return 1;
			return 0;
		});
		foreach ($scores as $userId => $user) {
			/* @var int $userId */
			/* @var \Masterword\User $user */

			$reply .= "\n" . $user->getUsername() . " " . $user->getScore();
		}

		$this->channel->trySendMessage($reply);
	}
}