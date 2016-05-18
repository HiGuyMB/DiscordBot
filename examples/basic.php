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

// Includes the Composer autoload file
include __DIR__.'/../vendor/autoload.php';

date_default_timezone_set("EST");

if ($argc != 2) {
    echo 'You must pass your Token into the cmdline. Example: php basic.php <token>';
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
        //
        // There is a list of event constants that you can
        // find here: https://teamreflex.github.io/DiscordPHP/classes/Discord.WebSockets.Event.html
        //
        // We will echo to the console that the WebSocket is ready.
        echo 'Discord WebSocket is ready!' . PHP_EOL;

        $macros = [];

        // Here we will just log all messages.
        $ws->on(
            Event::MESSAGE_CREATE,
            function ($message, $discord, $newdiscord) use (&$macros) {
                /* @var Message $message */
                /* @var Discord $discord */

                if ($message->author->id != $discord->getClient()->id) {
                    if ($message->content == 'PQ') {
                        $message->getChannelAttribute()->trySendMessage("WHERe");
                    }
                    if ($message->content == 'joj') {
                        $message->getChannelAttribute()->trySendMessage("No. Just no.");
                    }
                    if (strpos($message->content, 'donger') !== false) {
                        $song = array(
                            "I like to raise my Donger I do it all the time ヽ༼ຈل͜ຈ༽ﾉ",
                            "and every time its lowered┌༼ຈل͜ຈ༽┐",
                            "I cry and start to whine ┌༼@ل͜@༽┐",
                            "But never need to worry ༼ ºل͟º༽",
                            "my Donger's staying strong ヽ༼ຈل͜ຈ༽ﾉ",
                            "A Donger saved is a Donger earned so sing the Donger song!"
                        );
                        foreach ($song as $lyric) {
                            $message->getChannelAttribute()->trySendMessage($lyric);
                        }
                    }
                    if (strpos($message->content, "windmill of friendship") !== false) {
                        $message->getChannelAttribute()
                            ->trySendMessage("卐 This is the windmill of friendship 卐 Repost if you also love your friends 卐");
                    }

                    if (substr($message->content, 0, 4) === "say ") {
                        foreach ($discord->getClient()->getChannelsAttribute() as $channel) {
                            if ($channel instanceof Channel) {
                                $sent = $channel->trySendMessage(substr($message->content, 4));
                            }
                        }
                    }
                    if (strpos($message->content, "!randimg") !== false) {
                        $count = 3;
                        $exp = explode(" ", $message->content);
                        if (count($exp) > 1) {
                            $count = (int)$exp[1];
                        }
                        
                        if ($count > 10) {
                            $message->getChannelAttribute()->trySendMessage("ಠ_ಠ");
                            return;
                        }

                        //Make some images
                        $text = "Here are your images:";
                        for ($i = 0; $i < $count; $i ++) {
                            $possible = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
                            $img      = $possible[rand(0, strlen($possible) - 1)] .
                                        $possible[rand(0, strlen($possible) - 1)] .
                                        $possible[rand(0, strlen($possible) - 1)] .
                                        $possible[rand(0, strlen($possible) - 1)] .
                                        $possible[rand(0, strlen($possible) - 1)];
                            $text .= "\nhttp://i.imgur.com/$img.jpg";
                        }

                        $message->getChannelAttribute()->trySendMessage($text);
                    }
                    if (substr($message->content, 0, 3) === "!m " || $message->content === "!m") {
                        if (!array_key_exists($message->author->id, $macros)) {
                            $macros[$message->author->id] = [];
                        }
                        $exp = explode(" ", $message->content);
                        if (count($exp) > 2) {
                            array_splice($exp, 0, 1);
                            $ident = array_splice($exp, 0, 1)[0];
                            $conts = implode(" ", $exp);
                            $macros[$message->author->id][$ident] = $conts;

                            echo("Set macro $ident to $conts for {$message->author->id}\n");
                        } else {
                            $message->getChannelAttribute()->trySendMessage("Usage: !m <identifier> <text>");
                            $message->getChannelAttribute()->trySendMessage("    !<identifier>");
                        }
                    }
                    if (substr($message->content, 0, 1) === "!") {
                        $macro = substr($message->content, 1);
                        if (array_key_exists($message->author->id, $macros) &&
                            array_key_exists($macro, $macros[$message->author->id])) {
                            $message->getChannelAttribute()->trySendMessage($macros[$message->author->id][$macro]);
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