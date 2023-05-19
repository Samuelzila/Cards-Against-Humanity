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

/*
do different actions depending on game stage, where
0: The judge chooses a prompt
1: The players choose responses
2: The judge chooses the best response
*/
if ($gameObj->stage == 0) {
    //Verify that request comes from judge
    if ($player_id == $gameObj->players[$gameObj->judge]) {
        //go to next stage
        $gameObj->stage = 1;
        
        //write changes to json
        $game_file = fopen("../games/{$game_id}/main.json", 'w');
        fwrite($game_file, json_encode($gameObj));
        fclose($game_file);
        
    }
}
else if ($gameObj->stage == 3) {
    //Verify that request comes from judge
    if ($player_id == $gameObj->players[$gameObj->judge]) {
        //go to next stage
        $gameObj->stage = 0;
        //Reset round variables
        $gameObj->winner = null;
        
        //Change judge
        if ($gameObj->judge == count($gameObj->players)-1) {
            $gameObj->judge = 0;
        }
        else {$gameObj->judge++; }

        //reset player cards
        foreach ($gameObj->players as $player) {
            $player_file = fopen("../games/{$game_id}/cards/{$player}.json", 'r');
            $player_data = json_decode(fread($player_file, filesize("../games/{$game_id}/cards/{$player}.json")));
            fclose($player_file);

            $player_data->current = [];
            
            $player_file = fopen("../games/{$game_id}/cards/{$player}.json", 'w');
            fwrite($player_file, json_encode($player_data));
            fclose($player_file);
        }

        //reset prompt
        $prompts->current = "";
        
        //write changes to json
        $game_file = fopen("../games/{$game_id}/main.json", 'w');
        fwrite($game_file, json_encode($gameObj));
        fclose($game_file);

        $prompts_file = fopen("../games/{$game_id}/cards/prompts.json", 'w');
        fwrite($prompts_file, json_encode($prompts));
        fclose($prompts_file);
        
    }
}
?>