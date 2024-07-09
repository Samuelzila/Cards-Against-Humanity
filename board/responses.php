<?php
//configure game parameters
$json = json_decode($_POST['json'], false);

$game_id = $json->game_id;
$player_id = $_COOKIE['id'];
$selected_cards = $json->cards;

$player_file = fopen("../games/{$game_id}/cards/{$player_id}.json", 'r');
$playerObj = json_decode(fread($player_file, filesize("../games/{$game_id}/cards/{$player_id}.json")));
fclose($player_file);

//Add selected cards to current array
$playerObj->current += $selected_cards;

//remove selected cards from hand
foreach($selected_cards as $card) {
    $index = array_search($card, $playerObj->hand);
    array_splice($playerObj->hand, $index, 1);
}

//write changes to json
$player_file = fopen("../games/{$game_id}/cards/{$player_id}.json", 'w');
fwrite($player_file, json_encode($playerObj));
fclose($player_file);