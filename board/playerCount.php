<?php
//configure game parameters
$game_id = $_GET['game_id'];

$game_file = fopen("../games/{$game_id}/main.json", 'r');
$gameObj = json_decode(fread($game_file, filesize("../games/{$game_id}/main.json")), false);
fclose($game_file);

//Create an array of player names
$playerNames = [];
foreach($gameObj->players as $playerID) {
    $fstream = fopen("../games/{$game_id}/cards/".$playerID.".json", 'r');
    $playerObj = json_decode(fread($fstream, filesize("../games/{$game_id}/cards/".$playerID.".json")), false);
    fclose($fstream);

    array_push($playerNames, $playerObj->name);
}

//Return player count
echo implode(",", $playerNames);