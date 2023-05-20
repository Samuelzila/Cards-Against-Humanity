<?php
//Verify that all fields are filled
if (!empty($_POST['username']) && !empty($_POST['gamename']) && !empty($_POST['deck']) && isset($_POST['submit'])) {
    
    $username = $_POST['username'];
    $game_name = $_POST['gamename'];
    $deck = $_POST['deck'];

    //create player id and store it as a cookie
    $host_id = uniqid($username, true);
    setcookie("id", $host_id, time()+7200);

    //create game object
    class Game {
        public $id;
        public $game_name;
        public $host_id;
        public $deck;
        public $age;
        public $players;
        public $winning;
        public $judge = 0;
        public $stage = 0;
        public $turn = 0;
    }

    class prompts {
        public $current = "";
        public $pile = [];
    }

    $gameObj = new Game;
    $gameObj->game_name = $game_name;
    $gameObj->deck = $deck;
    $gameObj->host_id = $host_id;
    $gameObj->age = time();
    $gameObj->id = uniqid($game_name, true);
    $gameObj->players = [$host_id];

    //store game parameters
    mkdir("./games/{$gameObj->id}/", 0777, true);
    mkdir("./games/{$gameObj->id}/cards");

    $file = fopen("./games/{$gameObj->id}/main.json", 'w');
    fwrite($file, json_encode($gameObj));
    fclose($file);

    //Select cards for the game
    if ($_POST['deck'] == 'all') {
        //Add prompts from all available files
        $promptObj = new prompts;
        $prompt_files = array_diff(scandir("./cards/prompts/"), [".", ".."]);
        foreach($prompt_files as $file) {
            $prompts_file = fopen("./cards/prompts/".$file, 'r');
            $prompts = json_decode(fread($prompts_file, filesize("./cards/prompts/".$file)));
            fclose($prompts_file);

            $promptObj->pile = array_merge($promptObj->pile, $prompts);
        } 
        $prompts_file = fopen("./games/{$gameObj->id}/cards/prompts.json", 'w');
        fwrite($prompts_file, json_encode($promptObj));
        fclose($prompts_file);

        //Add responses from all available files
        $all_responses = [];
        $response_files = array_diff(scandir("./cards/responses/"), [".", ".."]);
        foreach($response_files as $file) {
            $responses_file = fopen("./cards/responses/".$file, 'r');
            $responses = json_decode(fread($responses_file, filesize("./cards/responses/".$file)));
            fclose($responses_file);

            $all_responses = array_merge($all_responses, $responses);
        } 
        $responses_file = fopen("./games/{$gameObj->id}/cards/pile.json", 'w');
        fwrite($responses_file, json_encode($all_responses));
        fclose($responses_file);
    } else {
        //Add cards from chosen deck to game
        $file = fopen("./decks/decks/".$_POST['deck'], 'r');
        $deckObj = json_decode(fread($file, filesize("./decks/decks/".$_POST['deck'])));
        fclose($file);
        
        //Prompts
        $promptObj = new prompts;
        $promptObj->pile = $deckObj->prompts;

        $prompts_file = fopen("./games/{$gameObj->id}/cards/prompts.json", 'w');
        fwrite($prompts_file, json_encode($promptObj));
        fclose($prompts_file);

        //Responses
        $responses = $deckObj->responses;

        $responses_file = fopen("./games/{$gameObj->id}/cards/pile.json", 'w');
        fwrite($responses_file, json_encode($responses));
        fclose($responses_file);
    }

    //create player hand
    Class player {
        public $name;
        public $score = 0;
        public $current = [];
        public $hand = [];
        public $ping; //this variable will store the last connection time, it will be used to ignore players that would be disconnected from the game.
    }

    $playerObj = new player;
    $playerObj->ping = time();
    $playerObj->name = $username;

    $player_file = fopen("./games/{$gameObj->id}/cards/{$host_id}.json", 'w');
    fwrite($player_file, json_encode($playerObj));
    fclose($player_file);

    header("Location:game.php?game_id={$gameObj->id}");
}
?>

<!DOCTYPE html>
<html>
<?php
include("./header.php");
?>
<body>
    <main>
        <form id="gamecreation" class="flex-column" method="post">
            <input type="text" name="username" placeholder="Username">
            <input type="text" name="gamename" placeholder="Game Name">
            <select name="deck">
                <option value="" disabled selected hidden>Choose a deck</option>
                <option value="all">All cards</option>
                <?php
                    //List all decks
                    $deck_files = array_diff(scandir("./decks/decks"), [".", ".."]);

                    foreach ($deck_files as $deck_file_name) {
                        $file = fopen("./decks/decks/".$deck_file_name, 'r');
                        $deckObj = json_decode(fread($file, filesize("./decks/decks/".$deck_file_name)));
                        fclose($file);

                        echo '<option value="'.$deck_file_name.'">'.$deckObj->name.'</option>';
                    }
                ?>
            </select>
            <input type="submit" name="submit" value="Create game">
        </form>
    </main>
</body>
</html>