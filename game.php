<!DOCTYPE html>
<html>
<?php
include("./header.php");
?>

<body>
    <style>
        #player-list {
            position: fixed;
            top: 1rem;
            left: 1rem;
        }

        /*Only show player count if in landscape mode*/
        @media (orientation: portrait) {
            #player-list {
                display: none;
            }
        }
    </style>
    <span id="player-list">

    </span>
    <main>

    </main>
    <script type="text/javascript">
        //function to obtain get variables from url
        function get(parameterName) {
            let result = null,
                tmp = [];
            location.search
                .substr(1)
                .split("&")
                .forEach((item) => {
                    tmp = item.split("=");
                    if (tmp[0] === parameterName) result = decodeURIComponent(tmp[1]);
                });
            return result;
        }

        //function to update the board every 500ms
        var previousBoard = "";

        function updateBoard() {
            let ajax = new XMLHttpRequest;
            ajax.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    if (this.responseText != previousBoard) { //this prevents unnecessary replacing DOM elements on the client
                        document.getElementsByTagName('main')[0].innerHTML = this.responseText;
                        previousBoard = this.responseText;
                    }
                }
            }
            ajax.open('GET', './board/main.php?game_id=' + get('game_id'), true);
            ajax.send();
        }

        setInterval(updateBoard, 500);

        //function for the judge to skip unfunny prompt card
        function judgeSkipCard() {
            let ajax = new XMLHttpRequest;

            ajax.open('GET', './board/skip.php?game_id=' + get('game_id'), true);
            ajax.send();
            updateBoard();
        }
        //function to go through the next game phase
        function nextStage() {
            let ajax = new XMLHttpRequest;

            ajax.open('GET', './board/stages.php?game_id=' + get('game_id'), true);
            ajax.send();
            updateBoard();
        }

        //function to toggle card selection
        var selectedCards = [];

        function selectCard(card) {
            if (card.classList.contains('selectable')) {
                selectedCards.push(card.innerHTML);
                card.classList.replace('selectable', 'selected');
            } else if (card.classList.contains('selected')) {
                let index = selectedCards.indexOf(card.innerHTML);
                selectedCards.splice(index, 1);
                card.classList.replace('selected', 'selectable');
            }
        }

        //function to send responses to judge
        function sendCards() {
            let ajax = new XMLHttpRequest;

            let json = {
                game_id: get('game_id'),
                cards: selectedCards
            }

            ajax.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    document.getElementsByTagName('main')[0].innerHTML = this.responseText;
                }
            }


            ajax.open('POST', './board/responses.php', true);
            ajax.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            ajax.send('json=' + JSON.stringify(json));

            delete json.cards;
            selectedCards = [];
            updateBoard();
        }

        function declareBest(player_id) {
            let ajax = new XMLHttpRequest;

            ajax.open('GET', './board/best.php?game_id=' + get('game_id') + '&player_id=' + player_id, true);
            ajax.send();
            updateBoard();
        }

        //Log amount of players in console
        var playerCount;
        var DOMplayerList = document.getElementById('player-list');

        function playerCount() {

            let ajax = new XMLHttpRequest;

            ajax.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    if (this.responseText != playerCount) { //this ensures that loging only happens on change.
                        let formattedResponse = 'Players:<br>' + this.responseText.split(',').join('<br>');
                        DOMplayerList.innerHTML = formattedResponse;
                        playerCount = this.responseText;
                    }
                }
            }

            ajax.open('GET', `./board/playerCount.php?game_id=${get('game_id')}`, true);
            ajax.send();
        }

        setInterval(playerCount, 500);
    </script>
</body>

</html>
