<?php
	// determine who is winner
	function matchWinner($bracket)
	{
		if ($bracket['score1'] > $bracket['score2']) {
			return $bracket['team1'];
		} else {
			return $bracket['team2'];
		}
	}
	
	// determine who is loser
	function matchLoser($bracket)
	{
		if ($bracket['score1'] > $bracket['score2']) {
			return $bracket['team2'];
		} else {
			return $bracket['team1'];
		}
	}
	function getTeams($brackets)
	{
		$teams = array();
		foreach ($brackets as $bracket) {
			array_push($teams, $bracket['team1']);
			array_push($teams, $bracket['team2']);
		}
		$teams = array_unique($teams);
		$teams = array_chunk($teams, 2);
		
		return $teams;
	}


	// analogous to functions in roundRobin.php
	function setTournamentJson($id, $team1, $team2, $score1, $score2, $datetime = 0)
	{
		$json = [
			'id' => $id,
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

	
?>
