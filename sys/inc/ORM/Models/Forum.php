<?php
/*---------------------------------------------\
|											   |
| @Author:       Andrey Brykin (Drunya)        |
| @Version:      1.0                           |
| @Project:      CMS                           |
| @package       CMS Fapos                     |
| @subpackege    Forum Model                   |
| @copyright     ©Andrey Brykin 2010-2012      |
| @last mod      2012/05/21                    |
|----------------------------------------------|
|											   |
| any partial or not partial extension         |
| CMS Fapos,without the consent of the         |
| author, is illegal                           |
|----------------------------------------------|
| Любое распространение                        |
| CMS Fapos или ее частей,                     |
| без согласия автора, является не законным    |
\---------------------------------------------*/



/**
 *
 */
class ForumModel extends FpsModel
{
	public $Table = 'forums';

    protected $RelatedEntities = array(
        'themeslist' => array(
            'model' => 'Themes',
            'type' => 'has_many',
            'foreignKey' => 'id_forum',
      	),
        'category' => array(
            'model' => 'ForumCat',
            'type' => 'has_one',
            'foreignKey' => 'id_cat',
        ),
        'last_theme' => array(
            'model' => 'Themes',
            'type' => 'has_one',
            'foreignKey' => 'last_theme_id',
        ),
        'parent_forum' => array(
            'model' => 'Forum',
            'type' => 'has_one',
            'foreignKey' => 'parent_forum_id',
        ),
        'subforums' => array(
            'model' => 'Forum',
            'type' => 'has_many',
            'foreignKey' => 'parent_forum_id',
        ),
    );
	
	
	
	
	public function getStats()
	{
		$result = $this->getDbDriver()->query("
			SELECT `id` as last_user_id
			, (SELECT `name` FROM `" . $this->getDbDriver()->getFullTableName('users') . "` ORDER BY `puttime` DESC LIMIT 1) as last_user_name
			, (SELECT COUNT(*) FROM `" . $this->getDbDriver()->getFullTableName('posts') . "`) as posts_cnt
			, (SELECT COUNT(*) FROM `" . $this->getDbDriver()->getFullTableName('themes') . "`) as themes_cnt
			FROM `" . $this->getDbDriver()->getFullTableName('users') . "` ORDER BY `puttime` DESC LIMIT 1");
		return $result;
	}
	
	
	public function updateForumCounters($fid)
	{
		$this->getDbDriver()->query(
				"UPDATE `" . $this->getDbDriver()->getFullTableName('forums') . "` SET `themes` = 
				(SELECT COUNT(*) FROM `" . $this->getDbDriver()->getFullTableName('themes') . "` 
				WHERE `id_forum` = '" . $fid . "'), `posts` = 
				(SELECT COUNT(b.`id`) FROM `" . $this->getDbDriver()->getFullTableName('themes') . "` a 
				LEFT JOIN `" . $this->getDbDriver()->getFullTableName('posts') . "` b ON a.`id`=b.`id_theme`),
				`last_theme_id`=(SELECT `id` FROM `" . $this->getDbDriver()->getFullTableName('themes') . "` 
				WHERE `id_forum`='" . $fid . "'
				ORDER BY `last_post` DESC  LIMIT 1) WHERE `id` = '" . $fid . "'" );
	}
	
	
	public function deleteThemesPostsCollisions()
	{
		$this->getDbDriver()->query("DELETE FROM `" . $this->getDbDriver()->getFullTableName('themes') . "` WHERE id NOT IN (SELECT DISTINCT id_theme FROM `" . $this->getDbDriver()->getFullTableName('posts') . "`)");
		$this->getDbDriver()->query("DELETE FROM `" . $this->getDbDriver()->getFullTableName('posts') . "` WHERE id_theme NOT IN (SELECT DISTINCT id FROM `" . $this->getDbDriver()->getFullTableName('themes') . "`)");
	}
	
	
	public function upThemesPostsCounters($theme)
	{
		// Обновляем таблицу USERS
		$this->getDbDriver()->query(
			"UPDATE `" . $this->getDbDriver()->getFullTableName('users') . "` SET 
			`themes` = (SELECT COUNT(*) FROM `" . $this->getDbDriver()->getFullTableName('themes') . "` 
			WHERE `id_author` = '" . $theme->getId_author() . "')
			, `posts` = (SELECT COUNT(*) FROM `" . $this->getDbDriver()->getFullTableName('posts') . "` 
			WHERE `id_author` = '" . $theme->getId_author() . "')
			WHERE `id` = '" . $theme->getId_author() . "'");

		//update forum info
		$this->getDbDriver()->query(
			"UPDATE `" . $this->getDbDriver()->getFullTableName('forums') . "` SET `themes` = 
			(SELECT COUNT(*) FROM `" . $this->getDbDriver()->getFullTableName('themes') . "` 
			WHERE `id_forum` = '" . $theme->getId_forum() . "'), `posts` = 
			(SELECT COUNT(b.`id`) FROM `" . $this->getDbDriver()->getFullTableName('themes') . "` a 
			LEFT JOIN `" . $this->getDbDriver()->getFullTableName('posts') . "` b ON a.`id`=b.`id_theme`
			WHERE a.`id_forum` = '" . $theme->getId_forum() . "'),
			`last_theme_id`=(SELECT `id` FROM `" . $this->getDbDriver()->getFullTableName('themes') . "` 
			WHERE `id_forum`='" . $theme->getId_forum() . "'
			ORDER BY `last_post` DESC  LIMIT 1) WHERE `id` = '" . $theme->getId_forum() . "'" );
	}
	
	
	
	public function upLastPost($from_forum, $id_forum)
	{
		$this->getDbDriver()->query("UPDATE `" . $this->getDbDriver()->getFullTableName('forums') . "` as forum SET 
			forum.`last_theme_id` = IFNULL((SELECT `id` FROM `" . $this->getDbDriver()->getFullTableName('themes') . "` 
			WHERE `id_forum` = forum.`id` ORDER BY `last_post` DESC LIMIT 1), 0) 
			WHERE forum.`id` IN ('" . $from_forum . "', '" . $id_forum . "')");
	}
	
	
	public function deleteCollisions()
	{
		$this->getDbDriver()->query("DELETE FROM `" . $this->getDbDriver()->getFullTableName('themes') 
                . "` WHERE id NOT IN (SELECT DISTINCT id_theme FROM `" . $this->getDbDriver()->getFullTableName('posts') . "`)");
		$this->getDbDriver()->query("DELETE FROM `" . $this->getDbDriver()->getFullTableName('posts') 
		. "` WHERE id_theme NOT IN (SELECT DISTINCT id FROM `" . $this->getDbDriver()->getFullTableName('themes') . "`)");
	}
	
	
	public function addLastAuthors($forums)
	{
		$Register = Register::getInstance();
		$uids = array();
		if (!empty($forums)) {
			foreach ($forums as $forum) {
				if (!$forum->getLast_theme()) continue;
				
				$uid = $forum->getLast_theme()->getId_last_author();
				if (0 != $uid) {
					$uids[] = $uid;
				}
			}
			
			
			if (!empty($uids)) {
				$uids = implode(', ', $uids);
				$usersModel = $Register['ModManager']->getModelInstance('Users');
				$users = $usersModel->getCollection(array("`id` IN ({$uids})"));
				
				
				if (!empty($users)) {
					foreach ($forums as $forum) {
						if (!$forum->getLast_theme()) continue;
						foreach ($users as $user) {
							if ( $forum->getLast_theme()->getId_last_author() === $user->getId()) {
								$forum->setLast_author($user);
							}
						}
					}
				}
			}

		}
		return $forums;
	}

	function getUserStatistic($user_id) {
		$result = $this->getDbDriver()->select('users', DB_FIRST, array('cond' => array('`id`' => $user_id), 'limit' => 1));
		if (is_array($result) && count($result) > 0) {
			$res = array();
			if ($result[0]['themes'] > 0) {
				$res[] = array(
					'text' => 'Тем',
					'count' => $result[0]['themes'],
					'url' => get_url('/forum/user_posts/' . $user_id),
				);
			}
			if ($result[0]['posts'] > 0) {
				$res[] = array(
					'text' => 'Сообщений',
					'count' => $result[0]['posts'],
					'url' => get_url('/forum/user_posts/' . $user_id),
				);
			}
			return $res;
		}
		return false;
	}
}
