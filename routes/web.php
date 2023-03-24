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

/*function SetLineupForTeam($teamid, $playerid) {
    //$roster = DB::select("SELECT *
    //FROM roster ON player.player_id = roster.player_id
    //WHERE roster.team_id = :teamid", ['teamid' => $teamid]);

    $playerPositions = DB::select("SELECT position FROM player_games_played WHERE player_id = :playerid",
        ['playerid' => $playerid]);

    $count = $playerPositions->count();
    if($count == 1) {
        //Check the roster for a player with this position. If none, return the position. If one, then
        //check that players other positions. Recursive?
    } else {
        foreach($playerPositions as $position) {
            $playerid = $rosterSpot->player_id;
       }
    }


    return $position;
}*/

$router->get('/', function () use ($router) {
    return getmypid();
});

//Player Routing
$router->get('Players/HitterRankings/{year}/{system}', function($year, $system) {
    $players = DB::select("SELECT
    p.player_id, hproj.fangraphs_id, p.name playerName,
    (select IF(COUNT(*) = 0, 0, 1) from roster where roster.player_id = p.player_id and roster.active = 1) unavailable,
    p.eligible_positions eligible, map.cbsid cbs_id,
    val.Adjusted adjusted, val.Guess guess, roster.salary,
    hproj.adp, hproj.ab, hproj.hr, hproj.r, hproj.rbi, hproj.sb, hproj.avg,
    replace(trim(TRAILING ' Jr.' FROM p.name), ' ', '-') fangraphs_name
    FROM player p
    JOIN player_id_map map ON p.cbs_id = map.cbsid
    LEFT OUTER JOIN hitter_projections hproj ON map.idfangraphs = hproj.fangraphs_id
    LEFT OUTER JOIN rostersforupload ros ON map.cbsid = ros.cbs_id
    left outer join hitter_value val on val.fangraphs_id = hproj.fangraphs_id
    left outer join roster on p.player_id = roster.player_id and roster.active
    WHERE hproj.adp < 700
    AND hproj.year = :year
    AND hproj.projection_system = :system
    ORDER BY hproj.adp ASC", ['year' => $year, 'system' => $system]);
    return $players;
});
$router->get('Players/PitcherRankings/{year}/{system}', function($year, $system) {
    $players = DB::select("SELECT
    p.player_id, pproj.fangraphs_id, p.name playerName, ros.protect, ros.eligible, map.cbsid cbs_id,
    (select IF(COUNT(*) = 0, 0, 1) from roster where roster.player_id = p.player_id and roster.active = 1) unavailable,
    val.Adjusted adjusted, val.Guess guess, roster.salary,
    pproj.adp, pproj.w, pproj.era, pproj.sv, pproj.ip, pproj.so, pproj.hld,
    replace(trim(TRAILING ' Jr.' FROM p.name), ' ', '-') fangraphs_name
    FROM player p
    JOIN player_id_map map ON p.cbs_id = map.cbsid
    LEFT OUTER JOIN pitcher_projections pproj ON map.idfangraphs = pproj.fangraphs_id
    LEFT OUTER JOIN rostersforupload ros ON map.cbsid = ros.cbs_id
    left outer join roster on p.player_id = roster.player_id and roster.active
    left outer join pitcher_value val on val.fangraphs_id = pproj.fangraphs_id
    WHERE pproj.adp < 700
    AND pproj.year = :year
    AND pproj.projection_system = :system
    ORDER BY pproj.adp ASC", ['year' => $year, 'system' => $system]);
    return $players;
});

//USer Routing
$router->get('User/{password}', function($password) {
    $user = DB::select("SELECT team.id team, team.name, team.role permissions FROM team WHERE password = :password",
    ['password' => $password]);
    return $user;
});

//Teams Routing
$router->get('Teams', function() {
    $teams = App\Models\Team::all();
    return $teams;
});

$router->get('Teams/{id}', function($id) {
    $team = App\Models\Team::find($id);
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

$router->get('Rosters/RosterCounts', function() {
    $rostercounts = DB::select("select distinct team_id, team.shortname as teamName,
        GetPositionCountByTeam(roster.team_id, 'C') as 'C',
        GetPositionCountByTeam(roster.team_id, '1B') as 'First',
        GetPositionCountByTeam(roster.team_id, '2B') as 'Second',
        GetPositionCountByTeam(roster.team_id, '3B') as 'Third',
        GetPositionCountByTeam(roster.team_id, 'SS') as 'SS',
        GetPositionCountByTeam(roster.team_id, 'OF') as 'OF',
        GetPositionCountByTeam(roster.team_id, 'UT') as 'UT',
        GetPositionCountByTeam(roster.team_id, 'P') as 'P',
        GetPositionCountByTeam(roster.team_id, 'B') as 'B',
        GetPlayerCountByTeam(roster.team_id) as 'TotalPlayers',
        GetTotalMoneySpentByTeam(roster.team_id) as 'TotalMoney',
        260-GetTotalMoneySpentByTeam(roster.team_id) as 'MoneyLeft',
        (260-GetTotalMoneySpentByTeam(roster.team_id))-(25-GetPlayerCountByTeam(roster.team_id))+1 as 'MaxBid'
        from roster
        join team on roster.team_id = team.id
        where roster.active = 1
        order by teamName;");
    return $rostercounts;
});

$router->get('Rosters/RosterHitterProjections/{year}/{system}', function($year, $system) {
    $rosterhitterprojections = DB::select("select team.name as name, team.id,
    sum(proj.hr) as hr, sum(proj.rbi) as rbi, sum(proj.r) as runs,
    sum(ab) as ab, sum(proj.sb) as sb, sum(h)/sum(AB) as avg
	from hitter_projections proj
	join player_id_map map on proj.fangraphs_id = map.idfangraphs
	join player p on map.cbsid = p.cbs_id
	join roster on p.player_id = roster.player_id and roster.active = 1
	join team on team.id = roster.team_id
    where proj.projection_system = :system
    and proj.year = :year
    and roster.active = 1
    group by proj.projection_system, team.name, team.id
    order by team.name;", ['system' => $system, 'year' => $year]);
    return $rosterhitterprojections;
});

$router->get('Rosters/RosterPitcherProjections/{year}/{system}', function($year, $system) {
    $rosterpitcherprojections = DB::select("select proj.projection_system, team.name as name,
    team.id, sum(proj.w) as wins, sum(proj.sv) as saves, sum(proj.hld) as holds,
    sum(proj.so) as so, sum(ip) as ip, sum(er) as er,
    sum(er)/(sum(ip)/9) as era
	from pitcher_projections proj
	join player_id_map map on proj.fangraphs_id = map.idfangraphs
	join player p on map.cbsid = p.cbs_id
	join roster on p.player_id = roster.player_id and roster.active = 1
	join team on team.id = roster.team_id
    where proj.projection_system = :system
    and proj.year = :year
    and roster.active = 1
    group by proj.projection_system, team.name, team.id
    order by team.name;", ['system' => $system, 'year' => $year]);
    return $rosterpitcherprojections;
});

$router->get('Rosters/GetLastTenAdditions', function() {
    $rosters = DB::select("select
    roster.id, player.name as playerName, team.shortname as teamName, salary
    from roster
    join player on roster.player_id = player.player_id
    join team on roster.team_id = team.id
    where roster.active = 1
    order by time_drafted desc
    limit 10;");

    return $rosters;
});

$router->get('Rosters', function() {
    $seasons = App\Models\Roster::all();
    return $seasons;
});

$router->get('Rosters/{id}', function($id) {
    $roster = App\Models\Roster::find($id);
    return $roster;
});

$router->get('Rosters/ByPlayer/{id}', function($id) {
    $roster = DB::select("SELECT * FROM roster WHERE player_id = :playerid and active = 1",
                           ['playerid' => $id]); // $request->json()->get('player_id')
    return $roster;
});

$router->get('Rosters/ByTeam/{teamid}', function($teamid) {
    console_log("Get Roster By Team");
    $rosters = DB::select("select distinct team_id, team.shortname as teamName,
        GetPlayerAtPositionForTeam(roster.team_id, 'C') as 'C',
        GetPlayerAtPositionForTeam(roster.team_id, '1B') as 'First',
        GetPlayerAtPositionForTeam(roster.team_id, '2B') as 'Second',
        GetPlayerAtPositionForTeam(roster.team_id, '3B') as 'Third',
        GetPlayerAtPositionForTeam(roster.team_id, 'SS') as 'SS',
        GetPlayerAtPositionForTeam(roster.team_id, 'OF') as 'OF',
        GetPlayerAtPositionForTeam(roster.team_id, 'UT') as 'UT',
        GetPlayersAtPositionForTeam(roster.team_id, 'P') as 'P',
        GetPlayersAtPositionForTeam(roster.team_id, 'B') as 'B',
        GetPlayerCountByTeam(roster.team_id) as 'TotalPlayers',
        GetTotalMoneySpentByTeam(roster.team_id) as 'TotalMoney',
        260-GetTotalMoneySpentByTeam(roster.team_id) as 'MoneyLeft',
        (260-GetTotalMoneySpentByTeam(roster.team_id))-(25-GetPlayerCountByTeam(roster.team_id))+1 as 'MaxBid'
        from roster
        join team on roster.team_id = team.id
        where roster.active = 1
        and roster.team_id = :teamid", ['teamid' => $teamid]);
    return $rosters;
});

$router->post('Rosters', function(\Illuminate\Http\Request $request) {
    $player_id = $request->json()->get('player_id');
    DB::table('roster')
    ->where('player_id', $player_id)
    ->update(['active' => 0]);

    $data=array('player_id'=>$player_id,
    "team_id"=>$request->json()->get('team_id'),
    "salary"=>$request->json()->get('salary'),
    "contract_year"=>$request->json()->get('contract_year'),
    "position"=>$request->json()->get('position'),
    "active"=>1,
    "position_locked"=>0);
    DB::table('roster')->insert($data);
    $roster = DB::select("SELECT * FROM roster WHERE player_id = :playerid and active = 1",
                           ['playerid' => $player_id]);
    return($roster);

    /*
    $roster = App\Models\Roster::create();
    $roster->player_id = $request->json()->get('player_id');;
    $roster->team_id = $request->json()->get('team_id');
    $roster->salary = $request->json()->get('salary');
    $roster->contract_year = $request->json()->get('contract_year');
    $roster->position = $request->json()->get('position');
    $roster->active = 1;
    $roster->position_locked = 0;
    $roster->save();
    $rosters = DB::select("SELECT roster.id, roster.team_id, roster.player_id,
        roster.position, team.name AS team_name,
        player.name AS player_name,
        roster.salary, roster.contract_year
        FROM roster
        JOIN team ON roster.team_id = team.id
        JOIN player ON roster.player_id = player.player_id
        WHERE roster.id = :roster", ['roster' => $roster->id]);
    return($roster);
    */
});

$router->post('Rosters/MovePlayer', function(\Illuminate\Http\Request $request) {
    DB::table('roster')
    ->where('player_id', $request->json()->get('id'))
    ->update(['position' => trim($request->json()->get('position'))]);
});

$router->delete('Rosters/{id}', function($id) {
    App\Models\DociRoster::destroy($id);
});

//Position routes
$router->get('Positions', function() {
    $positions = App\Models\Position::all();
    return $positions;
});

$router->get('ProtectionList/{teamid}', function($teamid) {
    //$protectionList = DB::table('rostersforupload')->where('TeamID', $teamid);
    $protectionList = DB::select("select ros.*, adp.adp, p.player_id, map.IDFANGRAPHS as fangraphs_id,
	replace(trim(TRAILING ' Jr.' FROM p.name), ' ', '-') fangraphs_name,
    map.CBSID as cbs_id
    from adp
    join player_id_map map on adp.fangraphs_id = map.idfangraphs
    join player p on map.cbsid = p.cbs_id
    join rostersforupload ros on ros.cbs_id = map.cbsid
    where ros.Team_id = :team
    order by ros.protect desc, adp.adp asc", ['team' => $teamid]);
    return $protectionList;
});
$router->post('ProtectionList/AddPlayer', function(\Illuminate\Http\Request $request) {
    console_log("Team: " . $request->json()->get('Team_id'));
    console_log("Player: " . $request->json()->get('player_id'));
    DB::table('rostersforupload')
    ->where('team_id', $request->json()->get('Team_id'))
    ->where('player_id', $request->json()->get('player_id'))
    ->update(['protect' => 1]);
});

$router->post('ProtectionList/RemovePlayer', function(\Illuminate\Http\Request $request) {
    console_log("Team: " . $request->json()->get('Team_id'));
    console_log("Player: " . $request->json()->get('player_id'));
    DB::table('rostersforupload')
    ->where('team_id', $request->json()->get('Team_id'))
    ->where('player_id', $request->json()->get('player_id'))
    ->update(['protect' => 0]);
    // console_log("Hello, Remove Done!");
});


$router->get('Positions/ByPlayer/{playerid}', function($playerid) {
    $positions = DB::table('player')->where('player_id', $playerid)->pluck('eligible_positions');

    // if($position[0] == "H") {
    //     $positions = DB::select("SELECT position.*
    //         FROM player_games_played
    //         JOIN position on player_games_played.position = position.position
    //         WHERE position.position = 'B'
    //         OR player_games_played.player_id = :playerid
    //         UNION
    //         select position.* from position where position='UT'"
    //         , ['playerid' => $playerid]);
    // } else {
    //     $positions = DB::select("select position.*
    //         from position
    //         where position='P' or position = 'B'");
    // }
    return $positions;
});

// Player routes
$router->get('Players/AtPositionForTeam/{teamid}/{position}', function($teamid, $position) {
    $playerPosition = DB::select("
        select player.name, player.player_id as id, map.idfangraphs as fangraphsId, salary,
        map.cbsid cbs_id, replace(trim(TRAILING ' Jr.' FROM player.name), ' ', '-') fangraphs_name,
        player.eligible_positions
        from roster
        join player on roster.player_id = player.player_id
        JOIN player_id_map map ON player.cbs_id = map.CBSID
        where roster.team_id = :teamid
        and roster.active = 1
        and roster.position = :position;", ['teamid' => $teamid, 'position' => $position]);
    return $playerPosition;
});
$router->get('Players/GetTransactions/{playerid}', function($playerid) {
    $playerPosition = DB::select("
        select player.name as player_name, player.player_id, team.id as team_id,
        team.name as team_name, salary, roster.id, roster.active, roster.time_drafted
        from roster
        join player on roster.player_id = player.player_id
        join team on roster.team_id = team.id
        where roster.player_id = :playerid
        order by time_drafted desc;", ['playerid' => $playerid]);
    return $playerPosition;
});

$router->get('Players/GetSalary/{playerid}', function($playerid) {
    $salary = DB::table('roster')->where('player_id', $playerid)->where('active', 1)->pluck('salary');
    return $salary;
});

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
        $firstName = "%" . strtolower($playerName) . "%";
        $lastName = "%" . strtolower($playerName) . "%";
    }

    if($firstName == $lastName)
    {
        $players = DB::select("SELECT player_id, name, position, fangraphs_id, eligible_positions
                    FROM  player
                    WHERE  name like :lastName", ['lastName' => $lastName]);
    }
    else
    {
        $name = $firstName . " " . $lastName;
        $players = DB::select("SELECT player_id, name, position, fangraphs_id, eligible_positions
                    FROM  player
                    WHERE  name like :name", ['name' => $name]);
    }
    return $players;
});
function console_log($message) {
    $STDERR = fopen("php://stderr", "w");
              fwrite($STDERR, "\n".$message."\n\n");
              fclose($STDERR);
}
