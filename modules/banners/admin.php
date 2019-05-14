<?php
// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
// обработка action-ов
$GLOBALS['css_set'][] = '/modules/banners/admin.css';
$_folder = Host::$root_path.'/modules/banners/uploads/'; // папка для файлов
switch(true){
   	
	case empty($action):
        $post_parameters = Request::GetParameters(METHOD_POST);
        $get_parameters = Request::GetParameters(METHOD_GET);
        if(!empty($post_parameters['submit'])){
            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
            // замена фотографий ТГБ
            if(!empty($_FILES)){
                $is_js = $is_html = false;
                $errors = [];
                foreach ($_FILES as $fname => $data){
                    if ($data['error']==0) {
                        $filename = $data['name'];
                        $fileinfo = pathinfo($filename);
                        if(in_array($fileinfo['extension'], array('html', 'js'))){
                            move_uploaded_file($data['tmp_name'], $_folder . $filename);
                        } else $errors['extensions_error'] = true;
                        //обработка HTML
                        if($fileinfo['extension'] == 'html'){
                            $is_html = true;
                            $content = file_get_contents($_folder . $filename);
                            if(preg_match("!<head>!isU", $content, $match)){
                                $content = preg_replace( '!(<head>)!i', '<head><script src="/html.js"></script>', $content );
                                file_put_contents($_folder . $filename, $content);
                                Response::SetString('html_file', $filename);
                            } else $errors['html'][] = 'В html файле не найден тег head';
                            
                        }  else if($fileinfo['extension'] == 'js') {                    
                            $is_js = true;    
                            $content = file_get_contents($_folder . $filename);
                            $content = preg_replace( '!({src:"images/)!i', '{src:"', $content );
                            //if(preg_match('!({src:"images/)!isU', $content, $match)) $content = preg_replace( '!({src:"images/)!i', '{src:"', $content );
                            // else $errors['js'][] = 'В JS файле не найдена запись {src:"images/)';
                            
                            if(preg_match('!(window.callClick())!isU', $content, $match)) $content = preg_replace( '!(window.callClick())!i', 'return ar_callLink({target: \'_blank\'})', $content );
                            else {
                                if(preg_match('!// timeline functions:(.+)// actions tween:!isU', $content, $match)) $content = preg_replace( '|// timeline functions:(.+)// actions tween:|isU', 'this.frame_0 = function() {this.click_btn.addEventListener("click", function () {return ar_callLink({target: \'_blank\'});} );}', $content );
                                else $errors['js'][] = 'В JS файле не найдены записи (window.callClick()) и конструкция // timeline functions и // actions tween';
                            }
                            file_put_contents($_folder . $filename, $content);
                            Response::SetString('js_file', $filename);
                        }
                    }
                }
                if(empty($is_js) || empty($is_html)) $errors['extensions_error'] = true;
                Response::SetArray('errors', $errors);
            }   
            
        //скачивание файлов
        } else if(!empty($get_parameters) && !empty($get_parameters['filename'])){
                $fileinfo = pathinfo($get_parameters['filename']);
                $filename = $get_parameters['filename'];
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header("Content-Disposition: attachment; filename=\"$filename\"");
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');                
                @readfile($_folder.$get_parameters['filename']);
                exit(0);
        }
        //удаление файлов
        else  {
            $dh = opendir($_folder);
            while($filename = readdir($dh)){           
                if($filename!='..' && $filename!='.') unlink($_folder.$filename);
            }
        }
		$module_template = 'admin.convert.html';
		break;
}


?>