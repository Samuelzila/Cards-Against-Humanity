<?php
define("TIMEOUT_TIME", 5);
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

$turn_file = fopen("../games/{$game_id}/turn.json", 'r');
$turn = json_decode(fread($turn_file, filesize("../games/{$game_id}/turn.json")));
fclose($turn_file);

//Create player hands if they don't already exist (they should, but just in case)
$player_files = scandir("../games/{$game_id}/cards/");
if (!in_array("{$player_id}.json", $player_files)) {
    class player
    {
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

//Add new player to main.json if not already in
if (!in_array($player_id, $gameObj->players)) {
    array_push($gameObj->players, $player_id);

    writeJsonToFile("../games/{$game_id}/main.json", $gameObj);
}

//update player ping
$player_file = fopen("../games/{$game_id}/cards/{$player_id}.json", 'r');
$playerObj = json_decode(fread($player_file, filesize("../games/{$game_id}/cards/{$player_id}.json")));
fclose($player_file);

$playerObj->ping = time();

//store player ping
$player_file = fopen("../games/{$game_id}/cards/{$player_id}.json", 'w');
fwrite($player_file, json_encode($playerObj));
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
            $index = random_int(1, sizeof($prompts->pile)) - 1;
            $card = $prompts->pile[$index];
            array_splice($prompts->pile, $index, 1);
            $prompts->current = $card;

            //write changes to json
            $prompts_file = fopen("../games/{$game_id}/cards/prompts.json", 'w');
            fwrite($prompts_file, json_encode($prompts));
            fclose($prompts_file);
        } else {
            echo '
            <div class="prompt card">' . $prompts->current . '</div>
            <div class="flex-row">
                <button onclick="nextStage()">Accept Card</button>
                <button onclick="judgeSkipCard()">Skip Card</button>
            </div>
            ';
        }
    } else {
        echo '<h2>Waiting for judge to choose a prompt...</h2>';

        //Replace judge if they are missing
        checkJudgePing();
    }
} else if ($gameObj->stage == 1) {
    //Go through each player one by one to prevent writing into pile file at the same time
    if ($turn == array_search($player_id, $gameObj->players)) {

        //Ensures that files are only written into if necessary
        if (sizeof($playerObj->hand) < 10) {

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
        }

        $turn = ($turn + 1) % sizeof($gameObj->players);

        //Change turn file
        $turn_file = fopen("../games/{$gameObj->id}/turn.json", 'w');
        fwrite($turn_file, json_encode($turn));
        fclose($turn_file);
    }

    //discirminate players from judge and do different things
    if ($player_id != $gameObj->players[$gameObj->judge]) {
        //send different info if cards have already been selected
        if (count($playerObj->current) == 0) {
            echo '<div class="prompt card">' . $prompts->current . '</div>';
            echo '<div class="hand">';
            foreach ($playerObj->hand as $card) {
                echo '<div class="selectable response card" onclick="selectCard(this)">' . $card . '</div>';
            }
            echo '</div>';
            echo '<button onclick="sendCards()">Send Cards</button>';
        } else {
            echo '<div class="prompt card">' . $prompts->current . '</div>';
            echo '<h2>Waiting for other players to select their cards...</h2>';
        }

        checkJudgePing();
    } else {
        //judge stuff happens here
        echo '<div class="prompt card">' . $prompts->current . '</div>';
        echo '<h2>Waiting for players to choose cards...</h2>';

        //check if all players have chosen their cards
        $proceed = true; //if $proceed remains true, the game will go to next stage
        foreach ($gameObj->players as $player) {
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

        checkPlayerPings();
    }
} else if ($gameObj->stage == 2) {
    //Tell the judge they are the judge
    if ($player_id == $gameObj->players[$gameObj->judge]) {
        echo '<h3>You are the judge. Choose wisely.</h3>';

        checkPlayerPings();
    }
    //Send each response set to everyone
    echo '<div class="results">';
    foreach ($gameObj->players as $player) {
        $player_file = fopen("../games/{$game_id}/cards/{$player}.json", 'r');
        $player_data = json_decode(fread($player_file, filesize("../games/{$game_id}/cards/{$player}.json")));
        fclose($player_file);

        if ($player != $gameObj->players[$gameObj->judge]) {
            echo '<div class="selectable result" onclick="declareBest(\'' . $player . '\')">';
            echo '<div class="prompt card">' . $prompts->current . '</div>';
            foreach ($player_data->current as $card) {
                echo '<div class="response card">' . $card . '</div>';
            }
            echo '</div>';
        }

        checkJudgePing();
    }
    echo '</div>';
} else if ($gameObj->stage == 3) {
    //Send each response set to everyone
    //Send winner first
    $player_file = fopen("../games/{$game_id}/cards/{$gameObj->winning}.json", 'r');
    $player_data = json_decode(fread($player_file, filesize("../games/{$game_id}/cards/{$gameObj->winning}.json")));
    fclose($player_file);

    echo '<h3>' . $player_data->name . '👑 (' . $player_data->score . ')</h3>';
    echo '<div class="result winner">';
    echo '<div class="prompt card">' . $prompts->current . '</div>';
    foreach ($player_data->current as $card) {
        echo '<div class="response card">' . $card . '</div>';
    }
    echo '</div>';

    foreach ($gameObj->players as $player) {
        $player_file = fopen("../games/{$game_id}/cards/{$player}.json", 'r');
        $player_data = json_decode(fread($player_file, filesize("../games/{$game_id}/cards/{$player}.json")));
        fclose($player_file);

        if ($player != $gameObj->players[$gameObj->judge] && $player != $gameObj->winning) { //remove judge and winning player from listing
            echo '<h3>' . $player_data->name . ' (' . $player_data->score . '):</h3>';
            echo '<div class="result">';
            echo '<div class="prompt card">' . $prompts->current . '</div>';
            foreach ($player_data->current as $card) {
                echo '<div class="response card">' . $card . '</div>';
            }
            echo '</div>';
        }
    }
    //Send proceed button to judge
    if ($player_id == $gameObj->players[$gameObj->judge]) {
        echo '<button onclick="nextStage()">Proceed</button>';
    }

    checkJudgePing();
}

function checkPlayerPings()
{
    $gameObj = $GLOBALS['gameObj'];
    $game_id = $GLOBALS['game_id'];
    foreach ($gameObj->players as $player) {
        $playerObj = readJsonFromFile("../games/{$game_id}/cards/{$player}.json");

        if (time() - $playerObj->ping > TIMEOUT_TIME) {
            unlink("../games/{$game_id}/cards/{$player}.json");
            array_splice($gameObj->players, array_search($player, $gameObj->players), 1);

            //Gives judge back their judge title
            //FIXME: I don't think maths work like that
            $gameObj->judge = $gameObj->judge % sizeof($gameObj->players);
            //Ensures that turn variable remains scoped
            writeJsonToFile("../games/{$game_id}/turn.json", 0);

            //write changes to json
            writeJsonToFile("../games/{$game_id}/main.json", $gameObj);
        }
    }
}

function checkJudgePing()
{
    $game_id = $GLOBALS['game_id'];
    $gameObj = $GLOBALS['gameObj'];

    //Open judge file
    $judgeFilePath = "../games/" . $game_id . "/cards/" . $gameObj->players[$gameObj->judge] . ".json";
    $judgeObj = readJsonFromFile($judgeFilePath);

    //Replace judge if missing
    if (time() - $judgeObj->ping > TIMEOUT_TIME) {
        unlink($judgeFilePath);
        array_splice($gameObj->players, array_search($gameObj->players[$gameObj->judge], $gameObj->players), 1);
        $gameObj->judge = ($gameObj->judge + 1) % sizeof($gameObj->players);

        //write changes to json
        writeJsonToFile("../games/{$game_id}/main.json", $gameObj);
    }
}

function readJsonFromFile($path)
{
    $fstream = fopen($path, 'r');
    $data = json_decode(fread($fstream, filesize($path)), false);
    fclose($fstream);

    return $data;
}
function writeJsonToFile($path, $data)
{
    $fstream = fopen($path, 'w');
    fwrite($fstream, json_encode($data));
    fclose($fstream);
}
