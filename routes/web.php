<?php
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return getmypid();
});

//Teams Routing
$router->get('Teams', function() {
    $teams = App\Models\Team::all();
    return $teams;
});

$router->get('Teams/{id}', function($id) {
    $team = App\Models\Team::find($id)::with('owner');
    return $team;
});

$router->post('Teams', function(\Illuminate\Http\Request $request) {
    $team = App\Models\Team::create();
    $team->name = $request->json()->get('name');
    $team->owner = $request->json()->get('owner');
    $team->save();
    return($team);
});

$router->put('Teams', function(\Illuminate\Http\Request $request) {
    $team = App\Models\Team::find($request->json()->get('id'));
    $team->name = $request->json()->get('name');
    $team->owner = $request->json()->get('owner')->get('id');
    $team->save();
    return($team);
});

$router->delete('Teams/{id}', function($id) {
    //App\Models\DociTeam::destroy($id);
});

//Rosters Routing
$router->get('Rosters', function() {
    $seasons = App\Models\Roster::all();
    return $seasons;
});

$router->get('Rosters/{id}', function($id) {
    $roster = App\Models\Roster::find($id);
    return $season;
});
$router->get('Rosters/ByTeam/{teamid}', function($teamid) {
    $roster = DB::table('docilineup')->where([['team_id', $teamid]])->get();
    return $roster;
});

$router->post('Rosters', function(\Illuminate\Http\Request $request) {
    $roster = App\Models\Roster::create();
    $roster->player_id = $request->json()->get('player_id');
    $roster->team_id = $request->json()->get('team_id');
    $roster->position = $request->json()->get('position');
    $roster->salary = $request->json()->get('salary');
    $roster->contractYear = $request->json()->get('contractYear');
    $roster->save();
    $rosters = DB::select("SELECT roster.id, roster.team_id, roster.player_id,
                    roster.position, team.name AS team_name, 
                    CONCAT(daflplayer.firstName, ' ', daflplayer.lastName) AS player_name
                    FROM docilineup 
                    JOIN team ON roster.team_id = team.id
                    JOIN player ON roster.player_id = player.id
                    WHERE roster.id = :rosterid", ['rosterid' => $roster->id]);
    return($rosters);
});

$router->put('Rosters', function(\Illuminate\Http\Request $request) {
    $roster = App\Models\Roster::find($request->json()->get('id'));
    $roster->save();
    return($roster);
});

$router->delete('Rosters/{id}', function($id) {
    App\Models\DociRoster::destroy($id);
});

// Player routes
$router->get('Players/SearchByName/{searchTerm}', function($searchTerm) {
    $playerName = urldecode($searchTerm);
    $lastName = '%';
    $firstName = '%';

    if(strrchr($playerName, ','))
    {
        $names = explode(",", $playerName);
        $lastName = trim($names[0]) . "%";
        $firstName = trim($names[1]) . "%";
    }
    elseif(strrchr($playerName, ' '))
    {
        $names = explode(" ", $playerName);
        $firstName = trim($names[0]) . "%";
        $lastName = trim($names[1]) . "%";
}
    else
    {
        $firstName = strtolower($playerName) . "%";
        $lastName = strtolower($playerName) . "%";
    }

    if($firstName == $lastName)
    {
        $players = DB::select("SELECT id, 
                    CONCAT(player.firstName, ' ', player.lastName) AS name,
                    CASE player.pitcher_ind WHEN 1 THEN 'P' WHEN 0 THEN 'H' ELSE 'H' END AS position
                    FROM  player
                    WHERE firstName like :firstName or lastName like :lastName", ['lastName' => $lastName, 'firstName' => $firstName]);
    }
    else
    {
        $players = DB::select("SELECT DAFLID as id, 
                    CONCAT(player.firstName, ' ', player.lastName) AS name,
                    CASE player.pitcher_ind 
                    WHEN 1 THEN 'P' 
                    WHEN 0 THEN 'H' 
                    ELSE 'H' 
                    END AS position
                    FROM  player
                    WHERE firstName like :firstName and lastName like :lastName", ['lastName' => $lastName, 'firstName' => $firstName]);
    }
    return $players;
});
