<?php
/*-----------------------------------------------\
|                                                |
|  @Author:       Alexander Verenik (Wasja)      |
|  @Version:      0.1                            |
|  @Last mod.     2012/12/28                     |
|                                                |
\-----------------------------------------------*/

class Redirect {
	
	private $urls = array(
        '/load' => '/loads/',
        '/publ' => '/stat/',
        '/blog' => '/news/category/2',
        '/faq' => '/news/category/3',
        '/tests' => '/',
        '/dir' => '/',
        '/photo' => '/foto',
    );
	

	public function __construct($params = array()) 
	{
	}
	
	
	public function common($params = array()) 
	{
		$url = trim($_SERVER['REQUEST_URI']);
        if (substr($url, -1) == "/") {
            $url = substr($url, 0, strlen($url)-1);
        }
        if (array_key_exists($url, $this->urls)) {
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: ' . $this->urls[$url]);
            die();
        } else {
            $value = null;
            if (strpos($url, "?")) {
                $record = explode("/", substr($url, 0, strpos($url, "?")));
            } else {
                $record = explode("/", $url);
            }
            if (count($record) > 1) {
                $url_id = (count($record) > 2) ? $record[count($record) - 1] : null;
                if (strcasecmp($record[1], "forum") == 0) {
                    $url_id = (count($record) > 2) ? $record[2] : null;
                    if ($url_id != null) {
                        $index = explode("-", $url_id);
                        if (count($index) > 0 && (strcasecmp($index[0], "0") == 0)) {
                            if (count($index) >= 4) {
                                if (strcasecmp($index[3], "34") == 0) {
                                    // Ленточный форум
                                    $value = "/forum/last_posts/";
                                    if (!empty($index[2]) && (strcasecmp($index[2], "0") != 0) && (strcasecmp($index[2], "1") != 0)) {
                                        $value .= "?page=" . $index[2];
                                    }
                                } else if (strcasecmp($index[3], "35") == 0) {
                                    // Пользователи форума
                                    $value = "/users/index/";
                                } else if (strcasecmp($index[3], "36") == 0) {
                                    // Правила форума
                                    $value = "/forum/";
                                } else if (strcasecmp($index[3], "37") == 0) {
                                    // RSS для форума
                                    // TODO: Дописать ссылку на RSS форума
                                    $value = "/forum/";
                                } else if (strcasecmp($index[3], "6") == 0) {
                                    // Поиск для форума
                                    $value = "/forum/";
                                } else if ((strcasecmp($index[3], "3") == 0) && count($index) >= 5 && !empty($index[4])) {
                                    // Сообщения пользователя
                                    $value = "/forum/user_posts/" . $index[4];
                                    if (!empty($index[2]) && (strcasecmp($index[2], "0") != 0) && (strcasecmp($index[2], "1") != 0)) {
                                        $value .= "?page=" . $index[2];
                                    }
                                } else {
                                    $value = "/forum/";
                                }
                            } else {
                                $value = "/forum/";
                            }
                        } else if (count($index) == 1 || (count($index) > 1 && empty($index[1]))) {
                            // TODO: Здесь должен быть анализ на что идет ссылка - на форум или раздел
                            if (is_numeric($index[0])) {
                                $value = "/forum/view_forum/" . $index[0];
                            }
                        } else if (count($index) == 2 || (count($index) > 2 && empty($index[2]))) {
                            if (strcasecmp($index[1], "0") == 0) {
                                // На разделы форума нет расширеных ссылок (предположение)
                                $value = "/forum/view_forum/" . $index[0];
                            } else {
                                $value = "/forum/view_theme/" . $index[1];
                            }
                        } else if (count($index) == 3 || (count($index) > 3 && empty($index[3]))) {
                            if (strcasecmp($index[1], "0") == 0) {
                                // На разделы форума нет расширеных ссылок (предположение)
                                $value = "/forum/view_forum/" . $index[0];
                            } else {
                                $value = "/forum/view_theme/" . $index[1];
                            }
                            if (!empty($index[2]) && (strcasecmp($index[2], "0") != 0) && !(strcasecmp($index[2], "1") != 0)) {
                                $value .= "?page=" . $index[2];
                            }
                        } else if (count($index) >= 4) {
                            if (empty($index[2]) || (strcasecmp($index[2], "0") == 0)) {
                                if (!empty($index[1]) && (strcasecmp($index[1], "17") == 0)) {
                                    $value = "/forum/view_theme/" . $index[1] . "?page=999";
                                } else if (strcasecmp($index[1], "0") == 0) {
                                    // На разделы форума нет расширеных ссылок (предположение)
                                    $value = "/forum/view_forum/" . $index[0];
                                } else {
                                    $value = "/forum/view_theme/" . $index[1];
                                }
                            } else if (strcasecmp($index[3], "16") == 0) {
                                $value = "/forum/view_post/" . $index[2];
                            }
                        }
                    }
                } else if (strcasecmp($record[1], "load") == 0) {
                    if ($url_id != null) {
                        $index = explode("-", $url_id);
                        if (count($index) > 0 && count($index) < 4) {
                            if (strcasecmp($index[0], "0") == 0) {
                                $value = "/loads/";
                            } else {
                                if (is_numeric($index[0])) {
                                    $value = "/loads/category/" . $index[0];
                                }
                            }
                            if ($value != null && count(index) > 1) {
                                $value .= "?page=" . $index[1];
                            }
                        } else if (count($index) >= 4 && $index[3] != null && !empty($index[3]) && (strcasecmp($index[3], "0") != 0)) {
                            $value = "/loads/view/" . $index[3];
                        } else if (count($index) == 5) {
                            if ($index[4] != null && !empty($index[4])) {
                                if (strcasecmp($index[4], "1") == 0) {
                                    // Добавление материала
                                    $value = "/loads/add_form/";
                                } else if (strcasecmp($index[4], "16") == 0) {
                                    // Неактивные материалы и ТОП-ы
                                    $value = "/loads/";
                                } else if (strcasecmp($index[4], "17") == 0) {
                                    // Материалы пользователя
                                    $value = "/loads/";
                                }
                            }
                        }
                    }
                } else if (strcasecmp($record[1], "publ") == 0) {
                    if ($url_id != null) {
                        $index = explode("-", $url_id);
                        if (count($index) > 0 && count($index) < 4) {
                            if (strcasecmp($index[0], "0") == 0) {
                                $value = "/stat/";
                            } else {
                                if (is_numeric($index[0])) {
                                    $value = "/stat/category/" . $index[0];
                                }
                            }
                            if ($value != null && count(index) > 1) {
                                $value .= "?page=" . $index[1];
                            }
                        } else if (count($index) >= 4 && $index[3] != null && !empty($index[3]) && (strcasecmp($index[3], "0") != 0)) {
                            $value = "/stat/view/" . $index[3];
                        } else if (count($index) == 5) {
                            if ($index[4] != null && !empty($index[4])) {
                                if (strcasecmp($index[4], "1") == 0) {
                                    // Добавление материала
                                    $value = "/stat/add_form/";
                                }
                            }
                        } else {
                            $value = "/stat/";
                        }
                    }
                } else if (strcasecmp($record[1], "news") == 0) {
                    if (count($record) > 2 && (strcasecmp($record[2], "category") != 0) &&
                        (strcasecmp($record[2], "view") != 0) && (strcasecmp($record[2], "add_form") != 0) && $url_id != null) {
                        $index = explode("-", $url_id);
                        if (count($index) > 0 && count($index) < 4) {
                            if (strcasecmp($index[0], "0") == 0) {
                                $value = "/news/";
                            } else {
                                if (is_numeric($index[0])) {
                                    $id = (intval($index[0]) + 1) * 3 + 1;
                                    $value = "/news/category/" . $id;
                                }
                            }
                            if ($value != null && count(index) > 1) {
                                $value .= "?page=" . $index[1];
                            }
                        } else if (count($index) >= 4 && $index[3] != null && !empty($index[3]) && (strcasecmp($index[3], "0") != 0)) {
                            $id = (intval($index[3]) - 1) * 3 + 1;
                            $value = "/news/view/" . $id;
                        } else if (count($index) == 5) {
                            if ($index[4] != null && !empty($index[4])) {
                                if (strcasecmp($index[4], "1") == 0) {
                                    // Добавление материала
                                    $value = "/news/add_form/";
                                }
                            }
                        }
                    }
                } else if (strcasecmp($record[1], "blog") == 0) {
                    if ($url_id != null) {
                        $index = explode("-", $url_id);
                        if (count($index) > 0 && count($index) < 4) {
                            if (strcasecmp($index[0], "0") == 0) {
                                $value = "/news/";
                            } else {
                                if (is_numeric($index[0])) {
                                    $id = (intval($index[0]) + 1) * 3 + 2;
                                    $value = "/news/category/" . $id;
                                }
                            }
                            if ($value != null && count(index) > 1) {
                                $value .= "?page=" . $index[1];
                            }
                        } else if (count($index) >= 4 && $index[3] != null && !empty($index[3]) && (strcasecmp($index[3], "0") != 0)) {
                            $id = (intval($index[3]) - 1) * 3 + 2;
                            $value = "/news/view/" . $id;
                        } else if (count($index) == 5) {
                            if ($index[4] != null && !empty($index[4])) {
                                if (strcasecmp($index[4], "1") == 0) {
                                    // Добавление материала
                                    $value = "/news/add_form/";
                                }
                            }
                        } else {
                            $value = "/news/category/2";
                        }
                    }
                } else if (strcasecmp($record[1], "faq") == 0) {
                    if ($url_id != null) {
                        $index = explode("-", $url_id);
                        if (count($index) > 0 && count($index) < 4) {
                            if (strcasecmp($index[0], "0") == 0) {
                                $value = "/news/";
                            } else {
                                if (is_numeric($index[0])) {
                                    $id = (intval($index[0]) + 1) * 3 + 3;
                                    $value = "/news/category/" . $id;
                                }
                            }
                            if ($value != null && count(index) > 1) {
                                $value .= "?page=" . $index[1];
                            }
                        } else if (count($index) >= 4 && $index[3] != null && !empty($index[3]) && (strcasecmp($index[3], "0") != 0)) {
                            $id = (intval($index[3]) - 1) * 3 + 3;
                            $value = "/news/view/" . $id;
                        } else if (count($index) == 5) {
                            if ($index[4] != null && !empty($index[4])) {
                                if (strcasecmp($index[4], "1") == 0) {
                                    // Добавление материала
                                    $value = "/news/add_form/";
                                }
                            }
                        } else {
                            $value = "/news/category/3";
                        }
                    }
                } else if (strcasecmp($record[1], "photo") == 0) {
                    $value = "/foto/";
                } else if (strcasecmp($record[1], "index") == 0) {
                    if ($url_id != null) {
                        $index = explode("-", $url_id);
                        if (strcasecmp($index[0], "15") == 0) {
                            // Пользователи сайта
                            $value = "/users/index/";
                        } else if (strcasecmp($index[0], "1") == 0) {
                            // Страница входа
                            $value = "/users/login_form/";
                        } else if (strcasecmp($index[0], "3") == 0) {
                            // Страница регистрации
                            $value = "/users/add_form/";
                        } else if (strcasecmp($index[0], "34") == 0) {
                            // Комментарии пользователя index[1]
                        } else if (strcasecmp($index[0], "8") == 0) {
                            // Профиль пользователя с номером index[1] или именем index[2]
                            if (count($index) == 2) {
                                $value = "/users/info/" . $index[1];
                            } else if (count($index) == 3 && (strcasecmp($index[1], "0") == 0)) {
                                $value = "/users/index/";
                            }
                        }
                    }
                }
            }

            if ($value != null) {
                header('HTTP/1.1 301 Moved Permanently');
                header('Location: ' . $value);
                die();
            }
        }
	}
}
