<?php
//Verify that all fields are filled
if (!empty($_POST['username']) && !empty($_POST['game']) && isset($_POST['submit'])) {
    
    $username = $_POST['username'];
    $game_id = $_POST['game'];

    //create player id and store it as a cookie
    $player_id = uniqid($username, true);
    setcookie("id", $player_id, time()+7200);

    //parse json game file into php object
    $game_file = fopen("./games/{$game_id}/main.json", 'r');
    $gameObj = json_decode(fread($game_file, filesize("./games/{$game_id}/main.json")));
    fclose($game_file);

    //add player to players array and write to file
    array_push($gameObj->players, $player_id);

    //write added data to json file
    $game_file = fopen("./games/{$game_id}/main.json", 'w');
    fwrite($game_file, json_encode($gameObj));
    fclose($game_file);

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

    $player_file = fopen("./games/{$gameObj->id}/cards/{$player_id}.json", 'w');
    fwrite($player_file, json_encode($playerObj));
    fclose($player_file);

    //redirect to desired game
    header("Location:game.php?game_id={$game_id}");
    exit;
}
//delete files recursively
function delTree($dir) {
    $files = glob( $dir . '*', GLOB_MARK );
    foreach( $files as $file ){
        if( substr( $file, -1 ) == '/' )
            delTree( $file );
        else
        unlink( $file );
    }
    rmdir( $dir );
}
?>
<!DOCTYPE html>
<html>
<?php
include("./header.php");
?>
<body>
    <main>
        <form class="flex-column" method="post">
            <input type="text" name="username" placeholder="Username">
            <select name="game">
                <option value="" disabled selected hidden>Choose a game</option>
                <?php
                    //create option tag for every existing game
                    $games = array_diff(scandir("./games"), [".",".."]);

                    foreach($games as $game) { 
                        //Parse game json file into php object                       
                        $game_file = fopen("./games/{$game}/main.json", 'r');
                        $gameObj = json_decode(fread($game_file, filesize("./games/{$game}/main.json")));
                        fclose($game_file);

                        //delete games older than 1 hour
                        if ((time() - $gameObj->age) > 7200) {
                            delTree("./games/{$gameObj->id}/");
                            continue;
                        }

                        echo "<option value=\"{$gameObj->id}\">{$gameObj->game_name}</option>";
                    }
                ?>
            </select>
            <input type="submit" name="submit" value="Join game">
        </form>
    </main>
</body>
</html>