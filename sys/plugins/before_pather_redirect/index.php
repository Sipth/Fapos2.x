<?phpclass Redirect {		// How match comments to view	private $limit = 10;		// Wraper for comments	//private $wrap = '<li class="point"><b>%s</b> <span style="color:#D6C39B;">Написал в</span><br /> %s</li>';	private $wrap;		private $urls = array(        '/load' => 'http://worldonline.com.ua/loads/',        '/blog' => 'http://worldonline.com.ua/news/category/2',        '/tests' => 'http://worldonline.com.ua/',        '/dir' => 'http://worldonline.com.ua/',        '/photo' => 'http://worldonline.com.ua/foto',        '/news/1-0-9' => 'http://worldonline.com.ua/news/category/31',        '/load/' => 'http://worldonline.com.ua/loads/',        '/blog/' => 'http://worldonline.com.ua/news/category/2',        '/tests/' => 'http://worldonline.com.ua/',        '/dir/' => 'http://worldonline.com.ua/',        '/photo/' => 'http://worldonline.com.ua/foto',        '/news/1-0-9' => 'http://worldonline.com.ua/news/category/31',        '/news/1-0-1' => 'http://worldonline.com.ua/news/category/7',        '/load/1' => 'http://worldonline.com.ua/loads/category/1',        '/index/0-56"' => 'http://worldonline.com.ua/news/manual_po_ustanovke_yava_java__servera.htm',        '/load/17-1' => 'http://worldonline.com.ua/loads/category/1',        '/load/3' => 'http://worldonline.com.ua/loads/category/3',        '/load/175' => 'http://worldonline.com.ua/loads/category/175',        '/load/31' => 'http://worldonline.com.ua/loads/category/31',        '/news/1-0-5' => 'http://worldonline.com.ua/news/category/19',        '/news/1-0-6' => 'http://worldonline.com.ua/news/category/22',        '/load/4' => 'http://worldonline.com.ua/loads/category/4',        '/load/48' => 'http://worldonline.com.ua/loads/category/48',        '/load/8' => 'http://worldonline.com.ua/loads/category/8',        '/tests/1' => 'http://worldonline.com.ua/',        '/news/1-0-10' => 'http://worldonline.com.ua/news/category/34',        '/photo/3' => 'http://worldonline.com.ua/foto',        '/photo/13' => 'http://worldonline.com.ua/foto',        '/dir/1-1' => 'http://worldonline.com.ua/',        '/load/9' => 'http://worldonline.com.ua/load/9',        '/news/1-0-2' => 'http://worldonline.com.ua/news/category/10',        '/news/1-0-4' => 'http://worldonline.com.ua/news/category/16',        '/news/1-0-7' => 'http://worldonline.com.ua/news/category/25',        '/load/2' => 'http://worldonline.com.ua/loads/category/2',        '/photo/1' => 'http://worldonline.com.ua/foto',        '/photo/14' => 'http://worldonline.com.ua/foto',        '/load/49' => 'http://worldonline.com.ua/loads/category/49',        '/news/1-0-42' => 'http://worldonline.com.ua/news/category/130',        '/photo/dota_2/35' => 'http://worldonline.com.ua/foto',        '/load/63' => 'http://worldonline.com.ua/loads/category/63',        '/news/1-0-3' => 'http://worldonline.com.ua/news/category/13',        '/news/1-0-15' => 'http://worldonline.com.ua/news/category/49',        '/news/1-0-16' => 'http://worldonline.com.ua/news/category/52',        '/load/26' => 'http://worldonline.com.ua/loads/category/26',        '/load/30' => 'http://worldonline.com.ua/loads/category/30',        '/load/29' => 'http://worldonline.com.ua/loads/category/29',        '/photo/4/' => 'http://worldonline.com.ua/foto',        '/photo/18/' => 'http://worldonline.com.ua/foto',        '/load/51' => 'http://worldonline.com.ua/loads/category/27',        '/news/1-0-8' => 'http://worldonline.com.ua/news/category/28',        '/photo/7' => 'http://worldonline.com.ua/foto',        '/news/1-0-11' => 'http://worldonline.com.ua/news/category/37',        '/news/1-0-41' => 'http://worldonline.com.ua/news/category/127',        '/load/32' => 'http://worldonline.com.ua/loads/category/32',        '/load/222' => 'http://worldonline.com.ua/loads/category/222',        '/load/228' => 'http://worldonline.com.ua/loads/category/228',        '/load/16' => 'http://worldonline.com.ua/loads/category/16',        '/photo/12' => 'http://worldonline.com.ua/foto',        '/load/35' => 'http://worldonline.com.ua/loads/category/35',        '/load/11' => 'http://worldonline.com.ua/loads/category/11',        '/load/38' => 'http://worldonline.com.ua/loads/category/38',        '/load/15' => 'http://worldonline.com.ua/loads/category/15',        '/news/1-0-17' => 'http://worldonline.com.ua/news/category/55',        '/news/1-0-12' => 'http://worldonline.com.ua/news/category/40',        '/tests/2-3-0' => 'http://worldonline.com.ua/',        '/load/14' => 'http://worldonline.com.ua/loads/category/14',        '/load/39' => 'http://worldonline.com.ua/loads/category/39',        '/load/40' => 'http://worldonline.com.ua/loads/category/40',        '/load/41' => 'http://worldonline.com.ua/loads/category/41',        '/load/115' => 'http://worldonline.com.ua/loads/category/115',        '/load/116' => 'http://worldonline.com.ua/loads/category/116',        '/load/42' => 'http://worldonline.com.ua/loads/category/42',        '/load/43' => 'http://worldonline.com.ua/loads/category/43',        '/load/46' => 'http://worldonline.com.ua/loads/category/46',        '/load/34' => 'http://worldonline.com.ua/loads/category/34',        '/load/44' => 'http://worldonline.com.ua/loads/category/33',        '/load/45' => 'http://worldonline.com.ua/loads/category/45',        '/load/37' => 'http://worldonline.com.ua/loads/category/37',        '/load/66' => 'http://worldonline.com.ua/loads/category/66',        '/load/67' => 'http://worldonline.com.ua/loads/category/67',        '/load/68' => 'http://worldonline.com.ua/loads/category/68',        '/load/69' => 'http://worldonline.com.ua/loads/category/69',        '/load/70' => 'http://worldonline.com.ua/loads/category/70',        '/load/71' => 'http://worldonline.com.ua/loads/category/71',        '/load/72' => 'http://worldonline.com.ua/loads/category/72',        '/load/73' => 'http://worldonline.com.ua/loads/category/73',        '/load/74' => 'http://worldonline.com.ua/loads/category/74',        '/load/75' => 'http://worldonline.com.ua/loads/category/75',        '/load/76' => 'http://worldonline.com.ua/loads/category/76',        '/load/77' => 'http://worldonline.com.ua/loads/category/77',        '/load/137' => 'http://worldonline.com.ua/loads/category/137',        '/news/1-0-13' => 'http://worldonline.com.ua/news/1-0-13',        '/load/53' => 'http://worldonline.com.ua/loads/category/53',        '/load/56' => 'http://worldonline.com.ua/loads/category/56',        '/load/55' => 'http://worldonline.com.ua/loads/category/55',        '/load/59' => 'http://worldonline.com.ua/loads/category/59',        '/load/57' => 'http://worldonline.com.ua/loads/category/57',        '/load/58' => 'http://worldonline.com.ua/loads/category/58',        '/news/1-0-18' => 'http://worldonline.com.ua/news/category/58',        '/load/64' => 'http://worldonline.com.ua/loads/category/64',        '/photo/20' => 'http://worldonline.com.ua/foto',        '/load/256' => 'http://worldonline.com.ua/loads/category/256',        '/load/257' => 'http://worldonline.com.ua/loads/category/257',        '/load/258' => 'http://worldonline.com.ua/loads/category/258',        '/load/259' => 'http://worldonline.com.ua/loads/category/259',        '/load/260' => 'http://worldonline.com.ua/loads/category/260',        '/load/139' => 'http://worldonline.com.ua/loads/category/139',        '/news/1-0-25' => 'http://worldonline.com.ua/news/category/79',        '/load/140' => 'http://worldonline.com.ua/loads/category/140',        '/load/141' => '	http://worldonline.com.ua/loads/category/141',        '/load/231' => 'http://worldonline.com.ua/loads/category/231',        '/news/1-0-43' => 'http://worldonline.com.ua/news/category/133',        '/news/1-0-44' => 'http://worldonline.com.ua/news/category/136',        '/load/232' => 'http://worldonline.com.ua/loads/category/232',        '/load/233' => 'http://worldonline.com.ua/loads/category/233',        '/load/234' => 'http://worldonline.com.ua/loads/category/234',        '/photo/perfect_world/34' => 'http://worldonline.com.ua/foto',        '/load/95' => 'http://worldonline.com.ua/loads/category/95',        '/news/28' => 'http://worldonline.com.ua/news/category/88',        '/load/85' => 'http://worldonline.com.ua/loads/category/85',        '/load/86' => 'http://worldonline.com.ua/loads/category/86',        '/load/87' => 'http://worldonline.com.ua/loads/category/87',        '/load/94' => 'http://worldonline.com.ua/loads/category/94',        '/load/88' => 'http://worldonline.com.ua/loads/category/88',        '/load/89' => 'http://worldonline.com.ua/loads/category/89',        '/load/90' => 'http://worldonline.com.ua/loads/category/90',        '/load/91' => 'http://worldonline.com.ua/loads/category/91',        '/load/92' => 'http://worldonline.com.ua/loads/category/92',        '/load/93' => 'http://worldonline.com.ua/loads/category/93',        '/load/96' => 'http://worldonline.com.ua/loads/category/96',        '/load/97' => 'http://worldonline.com.ua/loads/category/97',        '/load/99' => 'http://worldonline.com.ua/loads/category/99',        '/news/29' => 'http://worldonline.com.ua/news/category/91',        '/load/100' => 'http://worldonline.com.ua/loads/category/100',        '/load/101' => 'http://worldonline.com.ua/loads/category/101',        '/load/102' => 'http://worldonline.com.ua/loads/category/102',        '/load/103' => 'http://worldonline.com.ua/loads/category/103',        '/load/104' => 'http://worldonline.com.ua/loads/category/104',        '/load/105' => 'http://worldonline.com.ua/loads/category/105',        '/load/143' => 'http://worldonline.com.ua/loads/category/143',        '/news/29' => 'http://worldonline.com.ua/news/29',        '/load/145' => 'http://worldonline.com.ua/loads/category/145',        '/load/147' => 'http://worldonline.com.ua/loads/category/147',        '/load/146' => 'http://worldonline.com.ua/loads/category/146',        '/load/144' => 'http://worldonline.com.ua/loads/category/144',        '/load/107' => 'http://worldonline.com.ua/loads/category/107',        '/news/30' => 'http://worldonline.com.ua/news/category/94',        '/load/108' => 'http://worldonline.com.ua/loads/category/108',        '/load/109' => 'http://worldonline.com.ua/loads/category/109',        '/load/110' => 'http://worldonline.com.ua/loads/category/110',        '/load/111' => 'http://worldonline.com.ua/loads/category/111',        '/load/112' => 'http://worldonline.com.ua/loads/category/112',        '/load/113' => 'http://worldonline.com.ua/loads/category/113',        '/load/114' => 'http://worldonline.com.ua/loads/category/114',        '/load/80' => 'http://worldonline.com.ua/loads/category/80',        '/news/1-0-21' => 'http://worldonline.com.ua/news/category/67',        '/news/1-0-20' => 'http://worldonline.com.ua/news/category/64',        '/load/81' => 'http://worldonline.com.ua/loads/category/81',        '/photo/22/' => 'http://worldonline.com.ua/foto',        '/load/123' => 'http://worldonline.com.ua/loads/category/123',        '/news/1-0-24' => 'http://worldonline.com.ua/news/category/76',        '/load/124' => 'http://worldonline.com.ua/loads/category/124',        '/load/125' => 'http://worldonline.com.ua/loads/category/125',        '/load/187' => 'http://worldonline.com.ua/loads/category/187',        '/load/126' => 'http://worldonline.com.ua/loads/category/126',        '/load/127' => 'http://worldonline.com.ua/loads/category/127',        '/load/128' => 'http://worldonline.com.ua/loads/category/128',        '/load/129' => 'http://worldonline.com.ua/loads/category/129',        '/load/131' => 'http://worldonline.com.ua/loads/category/131',        '/news/31' => 'http://worldonline.com.ua/news/category/97',        '/load/132' => 'http://worldonline.com.ua/loads/category/132',        '/load/133' => 'http://worldonline.com.ua/loads/category/133',        '/load/134' => 'http://worldonline.com.ua/loads/category/134',        '/load/135' => 'http://worldonline.com.ua/loads/category/135',        '/load/136' => 'http://worldonline.com.ua/loads/category/136',        '/load/83' => 'http://worldonline.com.ua/loads/category/83',        '/news/1-0-22' => 'http://worldonline.com.ua/news/category/70',        '/photo/nfs_world_online/oboi_nfs_world_online/24' => 'http://worldonline.com.ua/foto',        '/load/150' => 'http://worldonline.com.ua/loads/category/150',        '/news/1-0-26' => 'http://worldonline.com.ua/news/category/82',        '/load/151' => 'http://worldonline.com.ua/loads/category/151',        '/load/152' => 'http://worldonline.com.ua/loads/category/152',        '/news/1-0-27' => 'http://worldonline.com.ua/news/category/85',        '/load/153' => 'http://worldonline.com.ua/loads/category/153',        '/photo/guild_wars_2/27' => 'http://worldonline.com.ua/foto',        '/load/155' => 'http://worldonline.com.ua/loads/category/155',        '/news/32' => 'http://worldonline.com.ua/news/category/100',        '/load/161' => 'http://worldonline.com.ua/loads/category/161',        '/photo/mafia_2/30' => 'http://worldonline.com.ua/foto',        '/load/157' => 'http://worldonline.com.ua/loads/category/157',        '/news/33' => 'http://worldonline.com.ua/news/category/103',        '/load/158' => 'http://worldonline.com.ua/loads/category/158',        '/load/159' => 'http://worldonline.com.ua/loads/category/159',        '/load/163' => 'http://worldonline.com.ua/loads/category/163',        '/news/34' => 'http://worldonline.com.ua/news/category/106',        '/load/164' => 'http://worldonline.com.ua/loads/category/164',        '/load/165' => 'http://worldonline.com.ua/loads/category/165',        '/load/166' => 'http://worldonline.com.ua/loads/category/166',        '/load/167' => 'http://worldonline.com.ua/loads/category/167',        '/load/168' => 'http://worldonline.com.ua/loads/category/168',        '/load/169' => 'http://worldonline.com.ua/loads/category/169',        '/load/170' => 'http://worldonline.com.ua/loads/category/170',        '/load/171' => 'http://worldonline.com.ua/loads/category/171',        '/load/172' => 'http://worldonline.com.ua/loads/category/172',        '/load/173' => 'http://worldonline.com.ua/loads/category/173',        '/load/174' => 'http://worldonline.com.ua/loads/category/174',        '/load/177' => 'http://worldonline.com.ua/loads/category/177',       '/news/35' => 'http://worldonline.com.ua/news/category/109',        '/load/178' => 'http://worldonline.com.ua/loads/category/178',        '/load/179' => 'http://worldonline.com.ua/loads/category/179',        '/load/180' => 'http://worldonline.com.ua/loads/category/180',        '/load/182' => 'http://worldonline.com.ua/loads/category/182',        '/load/183' => 'http://worldonline.com.ua/loads/category/183',        '/load/184' => 'http://worldonline.com.ua/loads/category/184',        '/load/185' => 'http://worldonline.com.ua/loads/category/185',        '/load/186' => 'http://worldonline.com.ua/loads/category/186',        '/load/190' => 'http://worldonline.com.ua/loads/category/190',        '/load/189' => 'http://worldonline.com.ua/loads/category/189',        '/load/191' => 'http://worldonline.com.ua/loads/category/191',        '/load/192' => 'http://worldonline.com.ua/loads/category/192',        '/load/193' => 'http://worldonline.com.ua/loads/category/193',    );		public function __construct($params = array()) 	{	}			public function common($params = array()) 	{		$url = trim($_SERVER['REQUEST_URI']);        if (array_key_exists($url, $this->urls)) {            header('HTTP/1.1 301 Moved Permanently');            header('Location: ' . $this->urls[$url]);            die();        }	}}