<?php
//configure game parameters
$game_id = $_GET['game_id'];
$player_id = $_COOKIE['id'];

$game_file = fopen("../games/{$game_id}/main.json", 'r');
$gameObj = json_decode(fread($game_file, filesize("../games/{$game_id}/main.json")), false);
fclose($game_file);

$prompts_file = fopen("../games/{$game_id}/cards/prompts.json", 'r');
$prompts = json_decode(fread($prompts_file, filesize("../games/{$game_id}/cards/prompts.json")), false);
fclose($prompts_file);

//Verify that request comes from judge
if ($player_id == $gameObj->players[$gameObj->judge]) {
    //Change current prompt card
    $index = random_int(1, sizeof($prompts->pile))-1;
    $card = $prompts->pile[$index];
    array_splice($prompts->pile, $index, 1);
    $prompts->current = $card;

    //write changes to json
    $prompts_file = fopen("../games/{$game_id}/cards/prompts.json", 'w');
    $prompts = fwrite($prompts_file, json_encode($prompts));
    fclose($prompts_file);
}