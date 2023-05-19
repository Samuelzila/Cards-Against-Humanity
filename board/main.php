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

$pile_file = fopen("../games/{$game_id}/cards/pile.json", 'r');
$pile = json_decode(fread($pile_file, filesize("../games/{$game_id}/cards/pile.json")));
fclose($pile_file);

//Create player hands if they don't already exist (they should, but just in case)
$player_files = scandir("../games/{$game_id}/cards/");
if (!in_array("{$player_id}.json", $player_files)) {
    Class player {
        public $name;
        public $score = 0;
        public $current = [];
        public $hand = [];
        public $ping; //this variable will store the last connection time, it will be used to ignore players that would be disconnected from the game.
    }

    $playerObj = new player;
    $playerObj->ping = time();
    $playerObj->name = $player_id;

    $player_file = fopen("../games/{$game_id}/cards/{$player_id}.json", 'w');
    fwrite($player_file, json_encode($playerObj));
    fclose($player_file);
}

//update player ping
$player_file = fopen("../games/{$game_id}/cards/{$player_id}.json", 'r');
$playerObj = json_decode(fread($player_file, filesize("../games/{$game_id}/cards/{$player_id}.json")));
fclose($player_file);

$playerObj->ping = time();

$player_file = fopen("../games/{$game_id}/cards/{$player_id}.json", 'w');
fwrite($player_file, json_encode($playerObj));
fclose($player_file);

//reopen player file
$player_file = fopen("../games/{$game_id}/cards/{$player_id}.json", 'r');
$playerObj = json_decode(fread($player_file, filesize("../games/{$game_id}/cards/{$player_id}.json")));
fclose($player_file);

/*
send different board configurations depending on game stage, where
0: The judge chooses a prompt
1: The players choose responses
2: The judge chooses the best response
3: Results are displayed
*/

if ($gameObj->stage == 0) {
    //discirminate judge from players and send different info
    if ($player_id == $gameObj->players[$gameObj->judge]) {
        
        //Pick a prompt card if none is already selected
        if (empty($prompts->current)) {
            
            //Move card from pile to judge
            $index = random_int(1, sizeof($prompts->pile))-1;
            $card = $prompts->pile[$index];
            array_splice($prompts->pile, $index, 1);
            $prompts->current = $card;
            
            //write changes to json
            $prompts_file = fopen("../games/{$game_id}/cards/prompts.json", 'w');
            fwrite($prompts_file, json_encode($prompts));
            fclose($prompts_file);
        } else {
            echo '
            <div class="prompt card">'.$prompts->current.'</div>
            <div class="flex-row">
                <button onclick="nextStage()">Accept Card</button>
                <button onclick="judgeSkipCard()">Skip Card</button>
            </div>
            ';
        }
    }
    else {
        echo '<h2>Waiting for judge to choose a prompt...</h2>';
    }
}
else if ($gameObj->stage == 1) {
    //Go through each player one by one to prevent writing into pile file at the same time
    if ($gameObj->turn == array_search($player_id, $gameObj->players)) {
        if (count($playerObj->hand) < 10) {
            //Pick cards until hand reaches 10
            for ($hand_size = sizeof($playerObj->hand); $hand_size < 10; $hand_size++) {
                $index = random_int(1, sizeof($pile));
                $card = $pile[$index];
                array_push($playerObj->hand, $card);
                array_splice($pile, $index, 1);
            }

            $pile_file = fopen("../games/{$game_id}/cards/pile.json", 'w');
            fwrite($pile_file, json_encode($pile));
            fclose($pile_file);
            
            $player_file = fopen("../games/{$game_id}/cards/{$player_id}.json", 'w');
            fwrite($player_file, json_encode($playerObj));
            fclose($player_file);

            if ($gameObj->turn == sizeof($gameObj->players)-1) {
                $gameObj->turn = 0;
            }else{
                $gameObj->turn++;
            }

            $game_file = fopen("../games/{$gameObj->id}/main.json", 'w');
            fwrite($game_file, json_encode($gameObj));
            fclose($game_file);
        }
    }

    //discirminate players from judge and do different things
    if ($player_id != $gameObj->players[$gameObj->judge]) {    
        //send different info if cards have already been selected
        if (count($playerObj->current) == 0) {
            echo '<div class="prompt card">'.$prompts->current.'</div>';
            echo '<div class="hand">';
            foreach ($playerObj->hand as $card) {
                echo '<div class="selectable response card" onclick="selectCard(this)">'.$card.'</div>';
            }
            echo '</div>';
            echo '<button onclick="sendCards()">Send Cards</button>';
        }
        else {
            echo '<div class="prompt card">'.$prompts->current.'</div>';
            echo '<h2>Waiting for other players to select their cards...</h2>';
        }
    }
    else {
        //judge stuff happens here
        echo '<div class="prompt card">'.$prompts->current.'</div>';
        echo '<h2>Waiting for players to choose cards...</h2>';

        //check if all players have chosen their cards
        $proceed = true; //if $proceed remains true, the game will go to next stage
        foreach($gameObj->players as $player) {
            $player_file = fopen("../games/{$game_id}/cards/{$player}.json", 'r');
            $player_data = json_decode(fread($player_file, filesize("../games/{$game_id}/cards/{$player}.json")));
            fclose($player_file);
            
            if (count($player_data->current) == 0 && $player != $gameObj->players[$gameObj->judge]) {
                //If at least one player has not chosen their cards, $proceed will be set to false, preventing the game from continuing to next stage.
                $proceed = false;
            }
        }
        //Go to next stage if $proceed is true
        if ($proceed) {
            //go to next stage
            $gameObj->stage = 2;
            
            //write changes to json
            $game_file = fopen("../games/{$game_id}/main.json", 'w');
            fwrite($game_file, json_encode($gameObj));
            fclose($game_file);
        }
    }
}
else if ($gameObj->stage == 2) {
    //Tell the judge they are the judge
    if ($player_id == $gameObj->players[$gameObj->judge]) {
        echo '<h3>You are the judge. Choose wisely.</h3>';
    }
    //Send each response set to everyone
    echo '<div class="results">';
    foreach($gameObj->players as $player) {
        $player_file = fopen("../games/{$game_id}/cards/{$player}.json", 'r');
        $player_data = json_decode(fread($player_file, filesize("../games/{$game_id}/cards/{$player}.json")));
        fclose($player_file);
        
        if ($player != $gameObj->players[$gameObj->judge]) {
            echo '<div class="selectable result" onclick="declareBest(\''.$player.'\')">';
            echo '<div class="prompt card">'.$prompts->current.'</div>';
            foreach ($player_data->current as $card) {
                echo '<div class="response card">'.$card.'</div>';
            }
            echo '</div>';
        }
    }
    echo '</div>';
}
else if ($gameObj->stage == 3) {
    //Send each response set to everyone
    //Send winner first
    $player_file = fopen("../games/{$game_id}/cards/{$gameObj->winning}.json", 'r');
    $player_data = json_decode(fread($player_file, filesize("../games/{$game_id}/cards/{$gameObj->winning}.json")));
    fclose($player_file);

    echo '<h3>'.$player_data->name.'👑 ('.$player_data->score.')</h3>';
    echo '<div class="result winner">';
    echo '<div class="prompt card">'.$prompts->current.'</div>';
    foreach ($player_data->current as $card) {
       echo '<div class="response card">'.$card.'</div>';
    }
    echo '</div>';

    foreach($gameObj->players as $player) {
        $player_file = fopen("../games/{$game_id}/cards/{$player}.json", 'r');
        $player_data = json_decode(fread($player_file, filesize("../games/{$game_id}/cards/{$player}.json")));
        fclose($player_file);
        
        if ($player != $gameObj->players[$gameObj->judge] && $player != $gameObj->winning) { //remove judge and winning player from listing
            echo '<h3>'.$player_data->name.' ('.$player_data->score.'):</h3>';
            echo '<div class="result">';
            echo '<div class="prompt card">'.$prompts->current.'</div>';
            foreach ($player_data->current as $card) {
                echo '<div class="response card">'.$card.'</div>';
            }
            echo '</div>';
        }
    }
    //Send proceed button to judge
    if ($player_id == $gameObj->players[$gameObj->judge]) {
        echo '<button onclick="nextStage()">Proceed</button>';
    }
}
?>