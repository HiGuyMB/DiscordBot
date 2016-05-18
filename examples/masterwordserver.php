<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

use Discord\Discord;
use Discord\Helpers\Guzzle;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event;
use Discord\WebSockets\WebSocket;
use Masterword\Guess;
use Masterword\Masterword;

// Includes the Composer autoload file
include __DIR__.'/../vendor/autoload.php';

date_default_timezone_set("EST");

if ($argc != 2) {
    echo "You must pass your Token into the cmdline. Example: php {$argv[0]} <token>";
    die(1);
}
// Init the Discord instance.
$discord = new Discord(['token' => $argv[1]]);
// Init the WebSocket instance.
$ws = new WebSocket($discord);

// We use EventEmitters to emit events. They are pretty much
// identical to the JavaScript/NodeJS implementation.
//
// Here we are waiting for the WebSocket client to parse the READY frame. Once
// it has done that it will run the code in the closure.
$ws->on(
    'ready',
    function ($discord) use ($ws) {
        /* @var Discord $discord */

        // In here we can access any of the WebSocket events.
	    $userCache = [];
	    $game = null;

        // Here we will just log all messages.
        $ws->on(
            Event::MESSAGE_CREATE,
            function ($message, $discord, $newdiscord) use (&$game, &$channel, &$userCache) {
                /* @var Message $message */
                /* @var Discord $discord */
                /* @var Discord $newdiscord */

                if ($message->author->id != $discord->getClient()->id) {
	                $lower = strtolower($message->content);
	                if ($lower === "!mwinit") {
		                if ($game === null) {
			                $game = new Masterword($message->getChannelAttribute(), $message->author, "puzzles.txt", "possible.txt", 20);
		                }
	                }

                    if ($game !== null && substr($lower, 0, 4) === "!mw ") {
	                    if (!array_key_exists($message->author->id, $userCache)) {
		                    $userCache[$message->author->id] = $message->author->username;
	                    }

	                    $text = substr($lower, 4);

	                    $guess = new Guess($text, $message->author);
	                    if (!$game->onGuess($guess)) {
		                    $game->sendError($guess->error);
		                    return;
	                    }

	                    //Only show if we have to
	                    if ($game->getDisplayResults()) {
		                    $game->sendResults();
	                    }
                    }
                }

                $reply = $message->timestamp->format('d/m/y H:i:s') . ' - '; // Format the message timestamp.
                $reply .= $message->full_channel->guild->name . ' - ';
                $reply .= $message->author->username . ' ' . $message->author->id .
                          ' - '; // Add the message author's username onto the string.
                $reply .= $message->content; // Add the message content.
                echo $reply . PHP_EOL; // Finally, echo the message with a PHP end of line.

            }
        );
    }
);

$ws->on(
    'error',
    function ($error, $ws) {
        dump($error);
        exit(1);
    }
);

// Now we will run the ReactPHP Event Loop!
$ws->run();