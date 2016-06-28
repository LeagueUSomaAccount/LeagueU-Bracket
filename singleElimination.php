<?php
	require_once 'tournamentCommon.php';
	
	// insert parsed json bracket data
	function setSingleBracket($idx, $sequence, $round, $match, $team1, $team2, $score1, $score2, $datetime = 0)
	{
		$bracket = [
			'idx' => $idx,
			'sequence' => $sequence, 
			'round' => $round, 
			'match'	=> $match,
			'team1' => $team1, 
			'team2' => $team2,
			'score1' => $score1,
			'score2' => $score2,
			'datetime' => $datetime
		];
		
		$bracket['winner'] = matchWinner($bracket);
		return $bracket;
	}
	
	function singleEliminationThirdPlace($brackets, $round, $results, $idx)
	{
		// loser team from semi-final
		$reverse_brackets = array_reverse($brackets);
		$team1 = matchLoser($reverse_brackets[2]);
		$team2 = matchLoser($reverse_brackets[1]);
		$seq = pow(2, $round);
		$bracket = setSingleBracket($idx, $seq, $round, 2,$team1, $team2,
				 $results[0][$round-1][1][0], 
				 $results[0][$round-1][1][1]);
		return $bracket;
	}
	/*
		single elimination parsing algorithm
		sample input json format

		[
			// team name list which is initial bracket for 1 round
			'teams' => [				
				['team1', 'team2'],
				['team3', 'team4'],
				['team5', 'team6'],
				['team7', 'team8'],
			],
			
			// result data is score of each bracket pairs.
			'results' => [
				[
					// 1 round
					[
						[1, 2],
						[3, 4],
						[5, 6],
						[7, 8],
					],
					// 2 round
					[
						[9, 10],
						[11, 12],
					],
					// 3 round 
					[
						[13, 14], // final 
						[15, 16]  // match for third place
					],
				]
			]
		];
	*/
	function singleEliminationDecode($single_elimination, $option)
	{
		// initial bracket pairs and result scores.
		$teams = $single_elimination['teams'];
		$results = $single_elimination['results'];
		
		// number of  total round 
		$round_size = count($teams);
		
		// n'th round
		$n_round = $round_size;

		// temporary variable for indexing and 
		$seq = 1;
		$idx = 0;
		$j = 0;
		
		// result of decoded bracket array
		$brackets = array();
		
		for($i = 0; $i < $round_size; $i++) {
			$score_idx = 0;
			$match_idx = 1;
			// parse n'th round 
			for($j = $idx; $j < $idx + $n_round; $j++) {
				
				$bracket = setSingleBracket(0, $seq, $i + 1, $match_idx,
							$teams[$j][0], $teams[$j][1], 
							$results[0][$i][$score_idx][0], 
							$results[0][$i][$score_idx][1]);

				array_push($brackets, $bracket);
				$match_idx++;
				$score_idx++;
				$seq++;
			}
			
			// get next round teams
			$winner = array();
			for($j = $idx; $j < $seq-1; $j++) {
				array_push($winner, $brackets[$j]['winner']);
			}
			// put next round teams to array
			$winner = array_chunk($winner, 2);
			foreach ($winner as $pairs) {
				array_push($teams, $pairs);
			}
			
			$idx += $n_round;
			$n_round >>= 1;
		}

		// when option is on, determine
		// match for third place
		if($option == 0) {
			// loser team from semi-final
			$bracket = singleEliminationThirdPlace($brackets, $i-1, $results, 0);
			
			array_push($brackets, $bracket);
		}
		return $brackets;
	}
	
	function singleEliminationEncode($brackets, $teams = null)
	{
		if ($teams == null)
			$teams = getTeams($brackets);
		
		$bracket_size = $match_size = count($brackets);
		$is_third = true;
		if ($bracket_size & 0x01) {
			$bracket_size++;
			$match_size = $bracket_size;
			$is_third = false;
		}

		$round_size = log($bracket_size, 2);
		$bracket_idx = 0;
		
		$results = array([]);
		for ($i = 0; $i < $round_size; $i++) {
			$match_size >>= 1;
			
			array_push($results[0], []);
			for ($j = 0; $j < $match_size; $j++)
			{
				array_push($results[0][$i], 
					[$brackets[$bracket_idx]['score1'], $brackets[$bracket_idx]['score2'], $brackets[$bracket_idx]['idx'], $brackets[$bracket_idx]['datetime']]);
				$bracket_idx++;
			}
		}
		if($is_third) {
			array_push($results[0][$i-1], 
					[$brackets[$bracket_idx]['score1'], $brackets[$bracket_idx]['score2'], $brackets[$bracket_idx]['idx'], $brackets[$bracket_idx]['datetime']]);
		}
		
		return [
			'teams' => $teams,
			'results' => $results[0]
		];

	}
	
?>