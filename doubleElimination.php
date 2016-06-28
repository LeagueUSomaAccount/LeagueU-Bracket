<?php
	require_once 'tournamentCommon.php';
	
	function setDoubleBracket($idx, $sequence, $round, $match, 
								$team1, $team2, 
								$score1, $score2, 
								$bracket, $datetime = 0)
	{
		$bracket = [
			'idx' => $idx,
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

	function winnerBracket($teams, $winner_brackets) {
		$round_size = $n_round = count($teams);
		
		$seq = 1;
		$idx = 0;
		
		$brackets = array();
		
		for ($i = 0; $i < $round_size; $i++) {
			$score_idx = 0;
			$match_idx = 1;
			for ($j = $idx; $j < $idx + $n_round; $j++) {
				$bracket = setDoubleBracket(0, $seq, $i + 1, $match_idx, 
							$teams[$j][0], $teams[$j][1], 
							$winner_brackets[$i][$score_idx][0], 
							$winner_brackets[$i][$score_idx][1], 0);
				array_push($brackets, $bracket);
				$match_idx++;
				$score_idx++;
				$seq++;
			}
			$winner = array();
			
			for ($j = $idx; $j < $seq -1; $j++) {
				array_push($winner, $brackets[$j]['winner']);
			}
			
			$winner = array_chunk($winner, 2);
			foreach ($winner as $pair) {
				array_push($teams, $pair);
			}
			unset($winner);
			
			$idx += $n_round;
			$n_round >>= 1;
		}
		return $brackets;
	}
	
	function loserTeam ($brackets)
	{
		$loser = array();
		
		foreach($brackets as $bracket) {
			array_push($loser, $bracket['loser']);
		}
		return $loser;
	}
	
	function loserTeamResize($teams)
	{
		$len = count($teams);
		$static_length = ($len + 1) >> 1;
		$total_team_size = $len + ($len >> 1);
		
		$resized = array();

		for ($i = 0; $i < $static_length; $i++) {
			$resized[$i] = $teams[$i];
		}
		
		$teams_idx = $i;
		for ($i; $i < $total_team_size-2; $i += 2) {
			$resized[$i] = false;
			$resized[$i+1] = $teams[$teams_idx];
			$teams_idx++;
		}
		
		if ($len > 3) {
			$resized[$i] = false;
			$resized[$i+1] = false;
			$resized[$i+2] = false;
			$resized[$i+3] = $teams[$teams_idx];			
		}
		
		return $resized;
	}
	
	function loserBracket($teams, $loser_brackets)
	{
		
		$round_size = log((count($teams)+1)>>1, 2) << 1;
		$n_round = pow(2, ($round_size>>1)) >> 1;
		
		$static_length = (count($teams) + 1) >> 2;

		$total_seq = count($teams)+ 1;
		$seq = 1;
		$idx = 0;
		
		$teams = loserTeamResize($teams);
		
		$teams = array_chunk($teams, 2);
		

		$brackets = array();
		for ($i = 0; $i < $round_size; $i++) {
			$score_idx = 0;
			$match_idx = 1;
			
			for ($j = $idx; $j < $idx + $n_round; $j++) {
				$bracket = setDoubleBracket(0, $total_seq, $i + 1, $match_idx, 
							$teams[$j][0], $teams[$j][1], 
							$loser_brackets[$i][$score_idx][0], 
							$loser_brackets[$i][$score_idx][1], 0);
				array_push($brackets, $bracket);
				$match_idx++;
				$score_idx++;
				$total_seq++;
				$seq++;
			}
			
			for ($j = $idx; ($j < $idx + $n_round) && ($i <> $round_size - 3); $j++) {
				$teams[$static_length][0] = $brackets[$j]['winner'];
				$static_length++;
			}
			
			if ($i == $round_size - 3) {
				$teams[$static_length][0] = $brackets[$idx]['winner'];
				$teams[$static_length][1] = $brackets[$idx + 1]['winner'];
				$static_length++;
			}
			
			$idx += $n_round;
			if ($i & 0x01) {
				$n_round >>= 1;
			}
		}
		//echo $total_team_size;
		//echo $round_size;
		
		//echo json_encode($brackets);
		return $brackets;
	}
	
	/*
		option
			0	: none.
			1	: match for third place.
			2	: secondary final.
			3	: both option 1 and 2.
			4	: loser can't comback.
	*/
	
	function finalBracket($brackets, $final_brackets, $option)
	{
		$winner_brackets = array_reverse($brackets[0]);
		$loser_brackets = array_reverse($brackets[1]);
		
		$winner_team = $winner_brackets[0]['winner'];
		$loser_team = $loser_brackets[0]['winner'];
		$seq = count($brackets[0]) + count($brackets[1]);
		
		$results = array();
		$match_idx = 1;
		for ($i = 0; $i < 2; $i++) {
			$bracket = setDoubleBracket(0, $seq++, 1, $match_idx,
						$winner_team, $loser_team, 
						$final_brackets[0][$i][0], 
						$final_brackets[0][$i][1], 0);
			array_push($results, $bracket);
			$match_idx++;
		}
		
		$match_idx = 1;
		$bracket = setDoubleBracket(0, $seq, 2, $match_idx,
						$loser_brackets[0]['loser'], 
						$loser_brackets[1]['loser'], 
						$final_brackets[1][0][0], 
						$final_brackets[1][0][1], 0);
		array_push($results, $bracket);
		
		return $results;
	}
	
	/*
		$double_elimination = [
			'teams' => [
				['team1', 'team2'],
				['team3', 'team4'],
				['team5', 'team6'],
				['team7', 'team8'],
			],
			'results' => [
				[// winner bracket
					
					[// 1 round
						[1, 2],	
						[3, 4],
						[1, 2],	
						[3, 4],
					],
					[// 2 round
						[5, 6],
						[5, 6],
					],
					[
						[1, 2]
					]
				],
				[// loser bracket
					[ // 1 round
						[7, 8],
						[7, 8]
					],
					[ // 2 round
						[9, 10],
						[9, 10]
					],
					[
						[9, 10]
					],
					[
						[9, 10]
					],
				],
				[// final bracket
					[//1 round
						[11, 12], // final
						[13, 14], // match for third place  
					],
					[//2 round
						[15, 16],
					]
				],
			]
		];
	*/
	function doubleEliminationDecode($double_elimination, $option)
	{
		$team = $double_elimination['teams'];
		
		$winner_brackets = $double_elimination['results'][0];
		$loser_brackets = $double_elimination['results'][1];
		$final_brackets = $double_elimination['results'][2];
		
		$winner_result = winnerBracket($team, $winner_brackets);
		$loser = loserTeam ($winner_result);
		
		$loser_result = loserBracket($loser, $loser_brackets);
		$final_result = finalBracket([$winner_result, $loser_result], $final_brackets, 0);
		
		$results = array_merge($winner_result, $loser_result, $final_result);
		
		return $results;
	}

	function winnerEncode($brackets, $bracket_idx, $winner_size)
	{
		$winner_result = array();
		$round_size = log(($winner_size+1), 2);
		$match_size = $winner_size+1;
		for ($i = 0; $i < $round_size; $i++) {
			$match_size >>= 1;

			array_push($winner_result, []);
			for ($j = 0; $j < $match_size; $j++) {
				array_push($winner_result[$i], 
					[$brackets[$bracket_idx]['score1'], $brackets[$bracket_idx]['score2'], $brackets[$bracket_idx]['idx'], $brackets[$bracket_idx]['datetime']]);
				$bracket_idx++;
			}
			
		}
		return $winner_result;
	}
	
	function loserEncode($brackets, $bracket_idx, $loser_size)
	{
		$loser_result = array();
		$round_size = floor(log($loser_size, 2));
		$match_size = pow(2, $round_size)>>1;
		$round_size <<= 1;		
		for ($i = 0; $i < $round_size; $i++) {
			array_push($loser_result, []);
			for ($j = 0; $j < $match_size; $j++) {
				array_push($loser_result[$i], 
					[$brackets[$bracket_idx]['score1'], $brackets[$bracket_idx]['score2'], $brackets[$bracket_idx]['idx'], $brackets[$bracket_idx]['datetime']]);
				$bracket_idx++;
			}
			if ($i & 0x01) {
				$match_size >>= 1;
			}
		}
		return $loser_result;
	}
	
	function finalOption($option)
	{
		$final_type = [
			'secondary_final' => true,
			'loser_revival' => true,
			
		];
		if ($option & 0x02) {
			$final_type['secondary_final'] = false;
		}
		if ($option & 0x03) {
			$final_type['loser_revival'] = false;
		}
		return $final_type;
	}
	
	function finalEncode($brackets, $bracket_idx, $option)
	{
		$final_result = array();

		//$option = finalOption($option);
		$round_size = 1;
		$match_size = 1;
		
		if ($option & 0x01) {
			$round_size = 2;
			$match_size = 1;
		}
		if ($option & 0x02) {
			$round_size = 2;
			$match_size = 2;
		}
		if ($option & 0x04) {
			return null;
		}
		
		for ($i = 0; $i < $round_size; $i++) {
			array_push($final_result, []);
			for ($j = 0; $j < $match_size; $j++) {
				array_push($final_result[$i], 
					[$brackets[$bracket_idx]['score1'], $brackets[$bracket_idx]['score2'], $brackets[$bracket_idx]['idx'], $brackets[$bracket_idx]['datetime']]);

				$bracket_idx++;
			}
			if ($option & 0x02){
				$match_size >>= 1;
			}
		}
		return $final_result;
	}
	
	function doubleEliminationEncode($brackets, $option, $teams = null)
	{
		if ($teams == null)
			$teams = getTeams($brackets);
		
		$bracket_size = count($brackets);
		$rest = $bracket_size & 0x03;
		if ($rest)
			$padding = 4 - $rest;
		else
			$padding = 0;

		$team_size = ($bracket_size + $padding) >> 1;
		$winner_size = $team_size - 1;
		$loser_size = $winner_size - 1;
		
		$bracket_idx = 0;
		$winner_result = winnerEncode($brackets, $bracket_idx, $winner_size);
		$bracket_idx += $winner_size;
		$loser_result = loserEncode($brackets, $bracket_idx, $loser_size);
		$bracket_idx += $loser_size;
		$final_result = finalEncode($brackets, $bracket_idx, $option);

		return [
			'teams' => $teams,
			'results' => [
				$winner_result,
				$loser_result, 
				$final_result
			]
		];
	}

?>