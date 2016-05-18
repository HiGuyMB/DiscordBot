<?php
namespace Masterword;

class User {

	/* @var \Discord\Parts\User\User $user */
	protected $user;

	/* @var int score */
	protected $score;

	public function __construct(\Discord\Parts\User\User $user) {
		$this->user = $user;
		$this->score = 0;
	}

	/**
	 * Give the user some points
	 * @param $score
	 */
	public function addScore($score) {
		$this->score += $score;
	}

	/**
	 * Get the user's score
	 * @return int
	 */
	public function getScore() {
		return $this->score;
	}

	/**
	 * @return \Discord\Parts\User\User
	 */
	public function getUser() {
		return $this->user;
	}

	/**
	 * @return string
	 */
	public function getUsername() {
		return $this->user->username;
	}

}