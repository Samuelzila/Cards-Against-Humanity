<!DOCTYPE html>
<html>
<?php
include("./header.php");
?>
<body>
    <main>
    <?php
        $deck_files = array_diff(scandir("./decks/decks"), [".", ".."]);

        foreach ($deck_files as $deck_file_name) {
            $file = fopen("./decks/decks/".$deck_file_name, 'r');
            $deckObj = json_decode(fread($file, filesize("./decks/decks/".$deck_file_name)));
            fclose($file);

            echo '<a href="decks/edit.php?deck_id='.$deck_file_name.'"><button>'.$deckObj->name.'</button></a>';
        }
    ?>
    </main>
</body>
</html>