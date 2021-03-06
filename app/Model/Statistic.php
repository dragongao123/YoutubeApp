<?php

class Statistic extends AppModel {

	public $useTable = false;

	public function getMostPopularVideos($countryCode, $type) {
		if(empty($countryCode) || empty($type)) {
			throw new Exception("Empty data");
		}
		$countryCode = strtoupper($countryCode);
		if(!in_array($countryCode, $this->globalConfig['COUNTRIES']) || !in_array($type, $this->globalConfig['TIME_RANGES']['type'])) {
			throw new Exception("Malformed data");
		}

		App::import('Vendor', 'redis/RedisForWorldMostViewed');
		$redis = RedisForWorldMostViewed::Instance();
		foreach($this->globalConfig['TIME_RANGES']['time_shifts'] as $timeshift) {
			$movieList[$timeshift] = json_decode($redis->getMovieList($countryCode, $type, $timeshift), true);
		}

		return $movieList;
	}

	public function parseCategoryXml() {
		$category = array(
			"---",
			"Film",
			"Autos",
			"Music",
			"Animals",
			"Sports",
			"Shortmov",
			"Travel",
			"Games",
			"Videoblog",
			"People",
			"Comedy",
			"Entertainment",
			"News",
			"Howto",
			"Education",
			"Tech",
			"Nonprofit",
			"Movies",
			"Movies anime animation",
			"Movies action adventure",
			"Movies classics",
			"Movies comedy",
			"Movies documentary",
			"Movies drama",
			"Movies family",
			"Movies foreign",
			"Movies horror",
			"Movies sci-fi fantasy",
			"Movies thriller",
			"Movies shorts",
			"Shows",
			"Trailers"
		);
		return array_combine($category, $category);
	}

	public function getLanguages() {
		$lines = file(APP."Vendor".DS."language".DS."languages.txt");
	  	foreach($lines as $line) {
	  		$line = explode("_", $line);
	  		$line[1] = trim($line[1]);
	  		$language[$line[1]] = $line[0];
	  	}
	  	array_unshift($language, "---");
	  	return $language;

	}

	public function getRegion() {
		$region = array_flip($this->globalConfig['COUNTRIES']);
		array_unshift($region, "---");
	  	return $region;
	}

	public function getVideoData($data) {
		foreach($data as $key => &$value) {
			if(empty($value) || $value == "---") {
				unset($data[$key]);
			}
		}
		unset($value);

		$data = json_encode($data);
		$data = str_replace("\"", "'", $data);
		$key = md5($data);
		
		$video = $this->getVideo($key);
		if($video == null) {
			$command = sprintf("%s../env/bin/python %sVendor/cronjobs/find_video.py -c\"%s\" -k%s ", APP, APP, $data, $key);
			$output = shell_exec($command);
			$video = $this->getVideo($key);
		}
		return json_decode($video, true);
	}

	private function getVideo($key) {
		App::import('Vendor', 'redis/RedisForVideo');
		$redis = RedisForVideo::Instance();
		return $redis->getVideo($key);
	}


}

?>