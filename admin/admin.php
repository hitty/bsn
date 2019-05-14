<?
$GLOBALS['css_set'][] = '/admin/css/admin.css';
$GLOBALS['js_set'][] = '/admin/js/jquery.min.js';
$GLOBALS['js_set'][] = '/admin/js/ckeditor/ckeditor.js';
$GLOBALS['js_set'][] = '/admin/js/ckedit_binder.js';
$GLOBALS['js_set'][] = '/admin/js/admin.js';
$GLOBALS['js_set'][]='/js/form.validate.js';
$GLOBALS['js_set'][] = '/js/jquery.form.expand.js';
$GLOBALS['css_set'][] = '/css/autocomplete.css';
$GLOBALS['js_set'][] = '/modules/estate/admin.autocomplette.js';
$GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
$GLOBALS['js_set'][] = '/js/popup.window/script.js';
$GLOBALS['css_set'][] = '/js/popup.window/styles.css';
$GLOBALS['js_set'][] = '/js/lazyload.min.js';


include(dirname(__FILE__).'/functions.php');
//меппинг модуля и меппинг для левого меню
$menu_mapping = $admin_mapping = include(dirname(__FILE__).'/mapping.php');

$_SESSION['CKFinder_UserRole'] = "admin";
// если это псевдомодуль, то сдвигаем URL
if(sizeof($this_page->page_parameters)>1 && !empty($admin_mapping[$this_page->page_parameters[0]]['shift_url'])){
    $backup_parameter_from_pageURL = array_shift($this_page->page_parameters);
    $admin_mapping = $admin_mapping[$backup_parameter_from_pageURL]['childs'];
}
if(!empty($this_page->page_parameters[0]) && !empty($admin_mapping[$this_page->page_parameters[0]])){
    $admin_module = $admin_mapping[$this_page->page_parameters[0]];
    if(!empty($admin_module['module'])){
        $module_file = Host::$root_path.'/modules/'.$admin_module['module'].'/admin.php';
        if(file_exists($module_file)) {
            // запомненные в сессии состояния элементов админки
            $admin_modules_runtime_settings = Session::GetArray('admin_modules_runtime_settings');
            // состояние модуля
            $module_settings = empty($admin_modules_runtime_settings[$admin_module['module']])?array():$admin_modules_runtime_settings[$admin_module['module']];
                        
            // запуск модуля
            include($module_file);
            
            if($ajax_mode){
                header("Content-type: application/json; charset=utf-8");
                    if(!empty($module_template)){
                        $tpl = new Template($module_template, '/modules/'.$admin_module['module']);
                        $module_content = $tpl->Processing();
                        $ajax_result['html'] = $module_content;
                        $ajax_result['module'] = $module_template;
                    } 
                    echo Convert::json_encode($ajax_result);
                    exit(0);    
            }
            $tpl = new Template($module_template, '/modules/'.$admin_module['module']);
            $module_content = $tpl->Processing();                        
            // сохранение состояния модуля в сессию
            $admin_modules_runtime_settings[$admin_module['module']] = $module_settings;
            Session::SetArray('admin_modules_runtime_settings',$admin_modules_runtime_settings);
        }
    }
} 

// Левое меню
$leftmenu = make_menu($this_page, $menu_mapping, $this_page->requested_path);
for($i=sizeof($leftmenu)-1;$i>=0;$i--){
    if($leftmenu[$i]['active']) break;
    if($leftmenu[$i]['opened'] && !$leftmenu[$i]['active']) {
        $leftmenu[$i]['active'] = true;
        break;
    }
}
Response::SetArray('leftmenu', $leftmenu); 
// если был сдвиг URL для псевдомодуля, восстанавливаем URL обратно
if(!empty($backup_parameter_from_pageURL)){
    array_unshift($this_page->page_parameters, $backup_parameter_from_pageURL);
}


//на заглавной странице отображаем список изменений проектов
if(empty($module_content)) {
    $GLOBALS['js_set'][] = '/admin/js/project_changes_actions.js';
    //читаем фильтры
    $get_parameters = array();
    $filters = array();
    $filters['title'] = Request::GetString('f_title',METHOD_GET);
    $filters['date'] = Request::GetString('f_date',METHOD_GET);
    $filters['project'] = Request::GetString('f_project',METHOD_GET);
    if(!empty($filters['title'])) {
        $filters['title'] = urldecode($filters['title']);
        $get_parameters['f_title'] = $filters['title'];
    }
    if(!empty($filters['date']))$get_parameters['f_date'] = $filters['date'];
    if(!empty($filters['project']))$get_parameters['f_project'] = $filters['project'];
    
    //по фильтрам составляем ограничение
    if(!empty($filters['title'])) $conditions[] = " ".$sys_tables['projects_changes'].".`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
    if(!empty($filters['date'])) $conditions[] = " ".$sys_tables['projects_changes'].".`datetime_create` LIKE '%".$db->real_escape_string($filters['date'])."%'";
    if(!empty($filters['project'])) $conditions[] = " ".$sys_tables['projects_changes'].".`id_project`=".$db->real_escape_string($filters['project']);
    $condition = (isset($conditions))?implode(' AND ',$conditions):false;
    
    //читаем список изменений из базы
    $sql = "SELECT ".$sys_tables['projects_changes'].".*,
                                          ".$sys_tables['users'].".name AS author_name,
                                          ".$sys_tables['users'].".email AS author_email,
                                          ".$sys_tables['projects'].".title AS project_title
                                   FROM ".$sys_tables['projects_changes']." 
                                   LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id=".$sys_tables['projects_changes'].".id_user
                                   LEFT JOIN ".$sys_tables['projects']." ON ".$sys_tables['projects'].".id=".$sys_tables['projects_changes'].".id_project";
    if ($condition) $sql .= " WHERE ".$condition." ORDER BY ".$sys_tables['projects_changes'].".datetime_create DESC";
    else $sql .= " ORDER BY ".$sys_tables['projects_changes'].".datetime_create DESC";
    //читаем список изменнеий в проектах
    $changes_list = $db->fetchall($sql);
    Response::SetArray('list',$changes_list);
    //читаем список проектов для фильтра
    $projects_list = $db->fetchall("SELECT * FROM ".$sys_tables['projects']);
    Response::SetArray('projects_list',$projects_list);
    
    // запоминаем для шаблона GET - параметры
    Response::SetArray('get_array', $get_parameters);
    foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gv;
    Response::SetString('get_string', implode('&',$get_parameters));
    
    $module_template = 'admin/templates/admin.info.html';
    if(!empty($admin_module['template'])) $module_template = $admin_module['template'];
    $infotpl = new Template($module_template);
    $module_content = $infotpl->Processing();
}
Response::SetString('module_content',$module_content);
?>