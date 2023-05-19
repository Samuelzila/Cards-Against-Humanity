<?php
//configure game parameters
$game_id = $_GET['game_id'];
$player_id = $_COOKIE['id'];
$best_player_id = $_GET['player_id'];

$game_file = fopen("../games/{$game_id}/main.json", 'r');
$gameObj = json_decode(fread($game_file, filesize("../games/{$game_id}/main.json")), false);
fclose($game_file);

//Verify that request comes from judge
if ($player_id == $gameObj->players[$gameObj->judge]) {
    //get best player file data
    $best_player_file = fopen("../games/{$game_id}/cards/{$best_player_id}.json", 'r');
    $player_data = json_decode(fread($best_player_file, filesize("../games/{$game_id}/cards/{$best_player_id}.json")));
    fclose($best_player_file);
    //increase playe score
    $player_data->score++;

    //store best cards in game object
    $gameObj->winning = $best_player_id;
    $gameObj->stage = 3;

    //write changes to file
    $game_file = fopen("../games/{$gameObj->id}/main.json", 'w');
    fwrite($game_file, json_encode($gameObj));
    fclose($game_file);

    $player_file = fopen("../games/{$game_id}/cards/{$best_player_id}.json", 'w');
    fwrite($player_file, json_encode($player_data));
    fclose($player_file);
}