<?php

// beging the application with session management
session_start();


// prepare array of 13 values of the card game
$cardValues = [
    "2" => 2,
    "3" => 3,
    "4" => 4,
    "5" => 5,
    "6" => 6,
    "7" => 7,
    "8" => 8,
    "9" => 9,
    "10" => 10,
    "J" => 11,
    "Q" => 12,
    "K" => 13,
    "A" => 14
];

//   4 Symboles of Card gmae
$cardSymbols = ['♠', '♥', '♦', '♣'];


//  Create deck by running loop over values and sysmbols to create the array.
//  We shuffle the card to make sure we get the random order every time 
function createDeck()
{
    global $cardValues, $cardSymbols;
    $deck = [];
    foreach ($cardValues as $value => $rank) {
        foreach ($cardSymbols as $cardSymbol) {
            $deck[] = ['value' => $value, 'cardSymbol' => $cardSymbol];
        }
    }
    shuffle($deck);
    return $deck;
}

// Intialize the game by creating a new deck,selection first and second card and then retun the gameState object
// incase of the new game  we retrive wins and losses from session , in fresh load we set to '0'
function initializeGame()
{
    $deck = createDeck();
    // pop out the card from the deck
    $firstCard = array_pop($deck);
    $secondCard = array_pop($deck);
    return [
        'deck' => $deck,
        'currentCard' => $firstCard,
        'nextCard' => $secondCard,
        'guessedCards' => [$firstCard],
        'score' => 0,
        'gameOver' => false,
        'message' => 'Lets start',
        'wins' => isset($_SESSION['wins']) ? $_SESSION['wins'] : 0,
        'losses' => isset($_SESSION['losses']) ? $_SESSION['losses'] : 0,
        'lastGuess' => null,
    ];
}

/// ENTRY POINT of the Applicion on Fresh Session / Fresh Load.  GET request

if (!isset($_SESSION['gameState'])) {
    $_SESSION['gameState'] = initializeGame();
}

/// Stores all the status of game in this variable
$gameState = &$_SESSION['gameState'];

/// ENTRY POINT of the Applicion during active game .  POST  request wtih  action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /// handling Guessing
    if ($action === 'guess') {
        $guess = $_POST['guess'];
        $currentCardValue = $cardValues[$gameState['currentCard']['value']];
        $nextCardValue = $cardValues[$gameState['nextCard']['value']];

        $correct = false;
        //check if the next card is hihger or not and set correct value
        if ($guess === 'higher' && $nextCardValue > $currentCardValue)
            $correct = true;
        if ($guess === 'lower' && $nextCardValue < $currentCardValue)
            $correct = true;
        //check this guess details  and store in tin gamestate variable
        $gameState['lastGuess'] = [
            'current' => $gameState['currentCard'],
            'next' => $gameState['nextCard'],
            'guess' => $guess
        ];

        // based on current guess

        if ($correct) {

            // update score and also calculate correct guesses if completes target set game as won. Otherwise keep guessing

            $gameState['score']++;
            if ($gameState['score'] === 5) {
                $gameState['gameOver'] = true;
                $gameState['message'] = 'Congratulations! You won!';
                // since its 5 right guesses , we close the game and add game to wins
                $_SESSION['wins']++;
            } else {
                $gameState['message'] = 'Correct! Keep going!';
                //nextcard becomes current card and we get new nextcard by poping out of the array 
                $gameState['currentCard'] = $gameState['nextCard'];
                $gameState['nextCard'] = array_pop($gameState['deck']);
                // we stored guessed cards in the displayed Cards array
                $gameState['guessedCards'][] = $gameState['currentCard'];
            }
        } else {
            // since its wrong guess , we close the game and add game to losses
            $gameState['gameOver'] = true;
            $gameState['message'] = 'Sorry, you lost.';
            $_SESSION['losses']++;
        }
    } elseif ($action === 'newGame') {
        // Reintialize the game state
        $gameState = initializeGame();
    } elseif ($action === 'clearCache') {
        // Clear the all the stats from the cache / Sessions
        session_unset();
        session_destroy();
        session_start();
        //reinitialize the game state
        $gameState = initializeGame();
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

//  Render the Cardboard by joinging the value and the symbol aslo add color to the cardSymbols
function renderCard($card)
{
    $class = in_array($card['cardSymbol'], ['♥', '♦']) ? 'text-danger' : 'text-warning';
    return "<span class=\"$class\">{$card['value']}{$card['cardSymbol']}</span>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guess High-Low</title>
    <!--use bootstrap for desing -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card-display {
            width: 16rem;
            height: 24rem;
        }

        .small-card {
            width: 3rem;
            height: 4rem;
        }
    </style>
</head>

<body class="d-flex flex-column  min-vh-100 bg-light">
    <div class="container  ">

        <div class="row mt-5 pb-5   bg-white">

            <!--  Create Card and dispy Current Card and All Guessed Cards -->

            <div class="col-4 mt-4 ">
                <div class="card card-display mb-2 mx-auto">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center">
                        <?php if ($gameState['currentCard']): ?>
                            <div class="display-1">
                                <?= renderCard($gameState['currentCard']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex mt-4 overflow-auto">
                        <?php foreach ($gameState['guessedCards'] as $card): ?>
                            <div
                                class="small-card border border-secondary d-flex justify-content-center align-items-center me-2 small">
                                <?= renderCard($card) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-8    p-5  ">
                <h3> Guess Next Card !!</h3>
                <div class="px-8 ">
                    <!--  Show Stats of Game History -->
                    <div class="mb-4 text-secondary my-4">
                        <span class="me-4">Wins: <?= $gameState['wins'] ?></span>
                        <span>Losses: <?= $gameState['losses'] ?></span>
                    </div>
                    <div class="mb-4 text-secondary">Score: <?= $gameState['score'] ?></div>
                    <?php if ($gameState['message']): ?>
                        <div class="mb-4 fw-bold text-primary"><?= $gameState['message'] ?></div>
                    <?php endif; ?>
                    <!--  Show Guess Result  -->
                    <?php if ($gameState['lastGuess']): ?>
                        <div class="d-flex mb-4">
                            <div class="h4"><?= renderCard($gameState['lastGuess']['current']) ?></div>
                            <div class="d-flex flex-column align-items-center mx-2">
                                → <span class="small"><?= $gameState['lastGuess']['guess'] ?></span>
                            </div>
                            <div class="h4"><?= renderCard($gameState['lastGuess']['next']) ?></div>
                        </div>
                    <?php endif; ?>
                    <!--  Play Guess by Eihter submitting Higher o rLower button  -->
                    <form method="POST" class="mb-4">
                        <input type="hidden" name="action" value="guess">
                        <div class="btn-group" role="group">
                            <button type="submit" name="guess" value="lower" class="btn btn-outline-primary"
                                <?= $gameState['gameOver'] ? 'disabled' : '' ?>>
                                ↓ Lower
                            </button>
                            <button type="submit" name="guess" value="higher" class="btn btn-primary"
                                <?= $gameState['gameOver'] ? 'disabled' : '' ?>>
                                ↑ Higher
                            </button>
                        </div>
                    </form>
                    <!--  Start New Game -->
                    <?php if ($gameState['gameOver']): ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="newGame">
                            <button type="submit" class="btn btn-success">
                                New Game
                            </button>
                        </form>
                    <?php endif; ?>
                    <!--  Clear all Session & Unset gameState -->
                    <form method="POST" class="my-3">
                        <input type="hidden" name="action" value="clearCache">
                        <button type="submit" class="btn btn-small border-outline">
                            Clear Session
                        </button>
                    </form>

                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>