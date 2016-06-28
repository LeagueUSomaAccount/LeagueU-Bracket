<?php
	
	function setRoundBracket($sequence, $round, $match, 
								$team1, $team2, 
								$score1, $score2, 
								$bracket, $datetime=0)
	{
		$bracket = [
			'sequence' => $sequence,
			'round' => $round,
			'match' => $match,
			'team1' => $team1,
			'team2' => $team2,
			'score1' => $score1,
			'score2' => $score2,
			'bracket' => $bracket,
			'datetime' => $datetime
		];
		$bracket['winner'] = matchWinner($bracket);
		$bracket['loser'] = matchLoser($bracket);
		return $bracket;
	}
	
	function setRoundJson($id, $round, $team1, $team2, $score1, $score2, $datetime = 0)
	{
		$json = [
			'id' => $id,
			'round' => $round,
			'datetime' => $datetime,
			'a' => [
				'team' => $team1,
				'score' => $score1
			],
			'b' => [
				'team' => $team2,
				'score' => $score2
			]
		];
		return $json;
	}
	
	function getRRTeams($brackets)
	{
		$teams = array();
		foreach($brackets as $bracket) {
			array_push($teams, $bracket['a']['team']);
			array_push($teams, $bracket['b']['team']);
		}
		$teams = array_unique($teams);
		return $teams;
	}
	
	function getRRJsonTeams($brackets) {
		$teams = [];

		foreach($brackets as $bracket) {
			if ($bracket == null){
				break;
			}
			array_push($teams, $bracket['team1']);
			array_push($teams, $bracket['team2']);
		}
		$teams = array_unique($teams);
		$team_size = count($teams);

		$json_teams = [];
		for($i = 0; $i < $team_size; $i++) {
			array_push($json_teams, [
				'id' => $i,
				'name' => $teams[$i]
			]);
		}

		return $json_teams;
	}
	
	function roundRobinDecode($round_robin)
	{
		$teams = getRRTeams($round_robbin);
		$team_size = count($teams);
		
		if ($team_size & 0x01) {
			$round_size = $team_size;
		} else {
			$round_size = $team_size - 1;
		}
		
		$match_idx = 1;
		$match_size = $team_size >> 1;
		
		$brackets = array();
		foreach ($round_robin as $rr) {
			$bracket = setRoundBracket($rr['id'] + 1, $rr['round'], $match_idx, 
						$rr['a']['team'], $rr['b']['team'], 
						$rr['a']['score'], $rr['b']['score'], 0);
			$match_idx++;

			if ($match_idx > $match_size) {
				$match_idx = 1;
			}
			array_push($brackets, $bracket);
		}
		return $brackets;
	}
	
	function roundRobinEncode($brackets, $teams = null)
	{
		if ($teams == null)
			$teams = getRRJsonTeams($brackets);

		$match = array();
		$team_column = array_column($teams, 'name');

		foreach ($brackets as $bracket) {
			$team1 = array_search($bracket['team1'], $team_column);
			$team2 = array_search($bracket['team2'], $team_column);
			if ($team1 == false)
				$team1 = 0;
			if ($team2 == false)
				$team2 = 0;
			$json = setRoundJson($bracket['sequence'], $bracket['round'], 
						$team1, $team2,
						$bracket['score1'], $bracket['score2'], $bracket['datetime']);
			array_push($match, $json);
		}
		return [
			'teams' => $teams,
			'matches' => $match
		];
	}
?>