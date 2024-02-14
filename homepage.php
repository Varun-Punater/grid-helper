<!DOCTYPE html>
<html lang="en">

<head>
    <title> Home Page</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description"
        content="This page is to host the filters for the search. It contains two drop down listts that can also be filtered through using the text box. The results of the search will be on a separate page.">


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
        <div class="row my-4">
            <h2 class="border-bottom">Choose your teams:</h2>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="teamFilter1" class="h6 form-label">Search for Team 1:</label>
                    <input type="text" class="form-control" id="teamFilter1" placeholder="Type to filter...">
                </div>

                <div class="mb-3">
                    <select class="form-select" id="selectedTeam1">
                    </select>
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-3">
                    <label for="teamFilter2" class=" h6 form-label">Search for Team 2:</label>
                    <input type="text" class="form-control" id="teamFilter2" placeholder="Type to filter...">
                </div>

                <div class="mb-3">
                    <select class="form-select" id="selectedTeam2">
                    </select>
                </div>
            </div>
        </div>
        <div class="row my-4">
            <form action="results.php" method="GET" id="teamForm">
                <input type="hidden" name="team1_id" id="team1_id">
                <input type="hidden" name="team2_id" id="team2_id">
                <div class="col text-center">
                    <button type="submit" class="btn custom-button" id="searchButton">Search Teams</button>
                    <div class="alert alert-danger mt-5" id="errorMessage" style="display: none;"></div>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts for Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
        crossorigin="anonymous"></script>

    <script>

        const nbaTeams = [
            "Atlanta Hawks", "Boston Celtics", "Brooklyn Nets", "Charlotte Hornets", "Chicago Bulls",
            "Cleveland Cavaliers", "Dallas Mavericks", "Denver Nuggets", "Detroit Pistons", "Golden State Warriors",
            "Houston Rockets", "Indiana Pacers", "Los Angeles Clippers", "Los Angeles Lakers", "Memphis Grizzlies",
            "Miami Heat", "Milwaukee Bucks", "Minnesota Timberwolves", "New Orleans Pelicans", "New York Knicks",
            "Oklahoma City Thunder", "Orlando Magic", "Philadelphia 76ers", "Phoenix Suns", "Portland Trail Blazers",
            "Sacramento Kings", "San Antonio Spurs", "Toronto Raptors", "Utah Jazz", "Washington Wizards"
        ];

        const teamIds = {
            "Atlanta Hawks": 1610612737,
            "Boston Celtics": 1610612738,
            "Cleveland Cavaliers": 1610612739,
            "New Orleans Pelicans": 1610612740,
            "Chicago Bulls": 1610612741,
            "Dallas Mavericks": 1610612742,
            "Denver Nuggets": 1610612743,
            "Golden State Warriors": 1610612744,
            "Houston Rockets": 1610612745,
            "Los Angeles Clippers": 1610612746,
            "Los Angeles Lakers": 1610612747,
            "Miami Heat": 1610612748,
            "Milwaukee Bucks": 1610612749,
            "Minnesota Timberwolves": 1610612750,
            "Brooklyn Nets": 1610612751,
            "New York Knicks": 1610612752,
            "Orlando Magic": 1610612753,
            "Indiana Pacers": 1610612754,
            "Philadelphia 76ers": 1610612755,
            "Phoenix Suns": 1610612756,
            "Portland Trail Blazers": 1610612757,
            "Sacramento Kings": 1610612758,
            "San Antonio Spurs": 1610612759,
            "Oklahoma City Thunder": 1610612760,
            "Toronto Raptors": 1610612761,
            "Utah Jazz": 1610612762,
            "Memphis Grizzlies": 1610612763,
            "Washington Wizards": 1610612764,
            "Detroit Pistons": 1610612765,
            "Charlotte Hornets": 1610612766
        };

        const teamFilterInput1 = document.getElementById("teamFilter1");
        const selectedTeamDropdown1 = document.getElementById("selectedTeam1");

        const teamFilterInput2 = document.getElementById("teamFilter2");
        const selectedTeamDropdown2 = document.getElementById("selectedTeam2");

        const errorMessage = document.getElementById("errorMessage");

        const populateDropdown = (input, dropdown) => {
            dropdown.innerHTML = "";

            const filterValue = input.value.toLowerCase();
            const filteredTeams = nbaTeams.filter(team => team.toLowerCase().includes(filterValue));

            filteredTeams.forEach(team => {
                const option = document.createElement("option");
                option.text = team;
                dropdown.add(option);
            });
        };

        const searchTeams = () => {
            const team1Name = selectedTeamDropdown1.value.trim();
            const team2Name = selectedTeamDropdown2.value.trim();

            const team1Id = teamIds[team1Name];
            const team2Id = teamIds[team2Name];

            if (team1Id && team2Id) {
                // Here you can use team1Id and team2Id for further processing or displaying
                alert(`You have selected: ${team1Name} (ID: ${team1Id}) and ${team2Name} (ID: ${team2Id})`);
                errorMessage.style.display = "none";
            } else {
                errorMessage.innerText = "Please select a team for both searches.";
                errorMessage.style.display = "block";
            }
        };

        populateDropdown(teamFilterInput1, selectedTeamDropdown1);
        populateDropdown(teamFilterInput2, selectedTeamDropdown2);

        teamFilterInput1.addEventListener("input", () => {
            populateDropdown(teamFilterInput1, selectedTeamDropdown1);
        });

        teamFilterInput2.addEventListener("input", () => {
            populateDropdown(teamFilterInput2, selectedTeamDropdown2);
        });

        searchButton.addEventListener('click', function (e) {

            const team1Name = selectedTeamDropdown1.value.trim();
            const team2Name = selectedTeamDropdown2.value.trim();

            const team1Id = teamIds[team1Name];
            const team2Id = teamIds[team2Name];

            if (team1Id && team2Id) {
                team1_id.value = team1Id;
                team2_id.value = team2Id;
            } else {
                e.preventDefault();
                alert("Please select a team for both searches.");
            }
        });
    </script>
</body>

</html>