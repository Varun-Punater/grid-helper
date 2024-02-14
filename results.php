<?php
session_start();

$host = "";
$user = "";
$pass = "";
$db = "";

// Establish MySQL Connection.
$mysqli = new mysqli($host, $user, $pass, $db);

// Check for any Connection Errors.
if($mysqli->connect_errno) {
    echo $mysqli->connect_error;
    exit();
}

function getUniquePlayersFromBothTeams($team1, $team2) {
    global $mysqli;

    $sql = "SELECT DISTINCT ps.player_id
            FROM punater_nba_db.player_season ps
            INNER JOIN (
                SELECT ps1.player_id
                FROM punater_nba_db.player_season ps1
                WHERE ps1.team_id = ?
            ) Team1Players ON ps.player_id = Team1Players.player_id
            INNER JOIN (
                SELECT ps2.player_id
                FROM punater_nba_db.player_season ps2
                WHERE ps2.team_id = ?
            ) Team2Players ON ps.player_id = Team2Players.player_id;";

    if($stmt = $mysqli->prepare($sql)) {
        // Bind the team IDs to the statement
        $stmt->bind_param("ii", $team1, $team2);

        // Execute the query
        $stmt->execute();

        // Fetch the results
        $result = $stmt->get_result();

        $players = [];
        while($row = $result->fetch_assoc()) {
            array_push($players, $row);
        }

        // Close the statement
        $stmt->close();

        return $players;
    } else {
        echo $mysqli->error;
        exit();
    }
}

// Check if team IDs are provided in GET request or stored in the session
if((isset($_GET["team1_id"]) && trim($_GET["team1_id"]) != '') && (isset($_GET["team2_id"]) && trim($_GET["team2_id"]) != '')) {
    // Store team IDs in session
    $_SESSION['team1_id'] = $_GET["team1_id"];
    $_SESSION['team2_id'] = $_GET["team2_id"];

    // Fetch and store players in session
    $_SESSION['players'] = getUniquePlayersFromBothTeams($_SESSION['team1_id'], $_SESSION['team2_id']);
} elseif(!isset($_SESSION['players'])) {
    // Redirect back to the form page if the teams are not set
    header("Location: homepage.php");
    exit();
}

$players = $_SESSION['players'];


// Now use the team IDs from either GET request or session
$team1_id = $_SESSION['team1_id'];
$team2_id = $_SESSION['team2_id'];


function getPlayerStatistics($playerId) {
    global $mysqli;

    $sql = "SELECT 
                pg.player_id,
                p.player_name,
                p.college, 
                p.country, 
                p.draft_year,
                p.draft_round,
                p.draft_number,
                SUM(pg.gp) AS total_games_played,
                SUM(pg.pts) AS total_points,
                CASE WHEN SUM(pg.gp) > 0 THEN SUM(pg.pts) / SUM(pg.gp) ELSE 0 END AS avg_points_per_game,
                SUM(pg.ast) AS total_assists,
                CASE WHEN SUM(pg.gp) > 0 THEN SUM(pg.ast) / SUM(pg.gp) ELSE 0 END AS avg_assists_per_game,
                SUM(pg.reb) AS total_rebounds,
                CASE WHEN SUM(pg.gp) > 0 THEN SUM(pg.reb) / SUM(pg.gp) ELSE 0 END AS avg_rebounds_per_game,
                SUM(pg.stl) AS total_steals,
                CASE WHEN SUM(pg.gp) > 0 THEN SUM(pg.stl) / SUM(pg.gp) ELSE 0 END AS avg_steals_per_game,
                SUM(pg.blk) AS total_blocks,
                CASE WHEN SUM(pg.gp) > 0 THEN SUM(pg.blk) / SUM(pg.gp) ELSE 0 END AS avg_blocks_per_game
            FROM punater_nba_db.player_general_traditional_total pg
            JOIN punater_nba_db.player p ON pg.player_id = p.player_id
            WHERE pg.player_id = ?
            GROUP BY pg.player_id, p.player_name, p.college, p.country, p.draft_year, p.draft_round, p.draft_number;";

    if($stmt = $mysqli->prepare($sql)) {
        // Bind the player ID to the statement
        $stmt->bind_param("i", $playerId);

        // Execute the query
        $stmt->execute();

        // Fetch the results
        $result = $stmt->get_result();

        $playerStats = [];
        while($row = $result->fetch_assoc()) {
            array_push($playerStats, $row);
        }

        // Close the statement
        $stmt->close();

        return $playerStats;
    } else {
        echo $mysqli->error;
        exit();
    }
}


// We need to initialize a index variable to keep track of the player we are on, so we can display the correct player
// Once we click on switch player, this value should change, so we can display another player

// Let's choose a random number in range 0 to sizeof($players) - 1
$_SESSION['randomIndex'] = rand(0, sizeof($players) - 1);

$randomIndex = $_SESSION['randomIndex'];
$playerId = $players[$randomIndex]["player_id"];
$playerStats = getPlayerStatistics($playerId);
$playerName = $playerStats[0]["player_name"];

function generateHints($playerStats) {
    // Clear existing hints and generate new ones
    $_SESSION['hints'] = [];

    // Let's generate the hints
    array_push($_SESSION['hints'], "This player was born in ".$playerStats[0]["country"]);
    if($playerStats[0]["college"] == "None") {
        array_push($_SESSION['hints'], "This player did not attend college");
    } else {
        array_push($_SESSION['hints'], "This player attended ".$playerStats[0]["college"]);
    }
    if($playerStats[0]["draft_round"] == "Undrafted" || $playerStats[0]["draft_round"] == "None" || $playerStats[0]["draft_round"] == "") {
        array_push($_SESSION['hints'], "This player was undrafted");
    } else {
        array_push($_SESSION['hints'], "This player was drafted in ".$playerStats[0]["draft_year"]);
        array_push($_SESSION['hints'], "This player was drafted in round ".$playerStats[0]["draft_round"]." with pick #".$playerStats[0]["draft_number"]);
    }
    array_push($_SESSION['hints'], "This player has played ".$playerStats[0]["total_games_played"]." games");
    array_push($_SESSION['hints'], "This player has scored ".$playerStats[0]["total_points"]." points");
    array_push($_SESSION['hints'], "This player has ".$playerStats[0]["total_rebounds"]." rebounds");
    array_push($_SESSION['hints'], "This player has ".$playerStats[0]["total_assists"]." assists");
    array_push($_SESSION['hints'], "This player has ".$playerStats[0]["total_steals"]." steals");
    array_push($_SESSION['hints'], "This player has ".$playerStats[0]["total_blocks"]." blocks");
    // round the averages to 2 decimal places
    array_push($_SESSION['hints'], "This player has averaged ".round($playerStats[0]["avg_points_per_game"], 2)." points per game");
    array_push($_SESSION['hints'], "This player has averaged ".round($playerStats[0]["avg_rebounds_per_game"], 2)." rebounds per game");
    array_push($_SESSION['hints'], "This player has averaged ".round($playerStats[0]["avg_assists_per_game"], 2)." assists per game");
    array_push($_SESSION['hints'], "This player has averaged ".round($playerStats[0]["avg_steals_per_game"], 2)." steals per game");
    array_push($_SESSION['hints'], "This player has averaged ".round($playerStats[0]["avg_blocks_per_game"], 2)." blocks per game");
}

// Generate hints only when switching players or during the initial load
if(!isset($_SESSION['hints']) || isset($_GET['switchPlayer'])) {
    generateHints($playerStats);
}

// Close MySQL Connection.
$mysqli->close();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title> Results Page</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description"
        content="This page is to host the information on the results of the search for common players. It contains the features of hints, switching, and revealing the mystery player.">


    <!-- Bootstrap 5.0 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

    <link rel="stylesheet" href="css/main.css">
</head>

<body class="">
    <nav class="navbar navbar-dark navbar-expand-lg sticky-top custom-navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="homepage.php">
                <img src="img/basketball.png" width="30" height="30" class="d-inline-block align-top"
                    alt="Basketball Icon">
                GridHelper
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup"
                aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="fandom.php">Fandom</a>
                    <a class="nav-link" href="info.html">Info</a>
                    <a class="nav-link" href="related.html">Related</a>
                </div>
            </div>
        </div>
    </nav>
    <div class="container">
        <div class="row my-1">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="homepage.php">Home</a></li>
                <li class="breadcrumb-item active">Results</li>
            </ol>
        </div>
        <div class="row mb-3">
            <div class="h4 my-4">Players found:
                <?php echo sizeof($players) ?>
            </div>
            <!-- this should be the size of the results for the two teams we have chosen -->
        </div>
        <div class="row mb-3">
            <div class="h5 text-center" id="mysteryPlayer">Mystery player #
                <?php echo $randomIndex ?>
            </div>
            <!-- When we click reveal player we replace this with the player name that we have stored from the database -->
        </div>
        <div class="row mb-3">
            <img src="img/placeholder.png" alt="Placeholder Image" class="mx-auto d-block custom-image"
                id="playerImage">
            <!-- The endpoint for the player picture for a specific id is: https://cdn.nba.com/headshots/nba/latest/1040x760/{{id}}.png -->
            <!-- So when we click reveal player, we want to use the id that we have stored and quey that endpoint to receive the picture -->
        </div>
        <div class="row mb-3">
            <div class="col text-center">
                <button class="btn btn-lg custom-button px-3" onclick="showNextHint()">
                    <i class="fas fa-ring fa-pulse ms-1 me-2"></i>
                    Hint Me!
                </button>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col" id="hintContainer">
            </div>
        </div>
        <div class="row mt-4 mb-3">
            <div class="row">
                <div class="col-6 text-center">
                    <button class="btn custom-button" onclick="location.href='?switchPlayer=1'">
                        <i class="fas fa-random mx-2"></i>
                        Switch Player
                    </button>
                </div>
                <div class="col-6 text-center">
                    <button class="btn custom-button" onclick="revealAnswer()">
                        <i class="fas fa-magic mx-2"></i>
                        Reveal Answer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts for Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
        crossorigin="anonymous"></script>

    <script>
        // Store PHP-generated hints in a JavaScript array
        var hints = <?php echo json_encode(isset($_SESSION['hints']) ? $_SESSION['hints'] : []); ?>;
        var hintIndex = 0;

        function showNextHint() {
            if (hintIndex < hints.length) {
                var hintContainer = document.getElementById('hintContainer');
                if (hintIndex === 0) {
                    hintContainer.innerHTML = ""; // Clear previous hints
                }
                hintContainer.innerHTML += "<div class='ms-2 mt-2'><strong>Hint #" + (hintIndex + 1) + ":</strong> " + hints[hintIndex] + "</div>";
                hintIndex++;
            }
        }

        var playerName = "<?php echo $playerName; ?>";
        var playerId = "<?php echo $playerId; ?>";

        function revealAnswer() {
            document.getElementById('mysteryPlayer').innerHTML = playerName;
            document.getElementById('mysteryPlayer').style.textDecoration = "underline";
            document.getElementById('mysteryPlayer').style.color = "#912f56ff";

            imageLink = "https://cdn.nba.com/headshots/nba/latest/1040x760/" + playerId + ".png";
            console.log(imageLink);

            let img = new Image();
            img.onload = function () {
                document.getElementById('playerImage').src = imageLink;
            };
            img.onerror = function () {
                // set it to the fallback image:
                document.getElementById('playerImage').src = "https://cdn.nba.com/headshots/nba/latest/1040x760/fallback.png";
                document.getElementById('playerImage').alt = "Image not found";
            };
            img.src = imageLink;
        }
    </script>

</body>

</html>