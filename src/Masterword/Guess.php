<?php
namespace Masterword;

use Discord\Parts\User\User;

class Guess {
	/* @var string $text */
	public $text;

	/* @var User $user */
	public $user;

	/* @var string $error */
	public $error;

	/* @var array $points */
	public $points;

	/**
	 * Guess constructor.
	 * @param string $text
	 * @param int $userId
	 */
	public function __construct($text, $user) {
		$this->text = strtolower($text);
		$this->user = $user;
		$this->error = "";
	}

	/**
	 * Return a string representation of the guess
	 * @return string
	 */
	public function format() {
		return strtoupper($this->text) . array_reduce($this->points, function ($carry, $item) {
			return $carry . " +" . $item;
		});
	}
}
