<?php
Response::SetString('title', empty($this->page_seo_title) ? $this->page_title : $this->page_seo_title);

Response::SetString('requested_url', $this->requested_url);
Response::SetString('real_url', $this->real_url);
Response::SetArray('page_parameters', $this->page_parameters);
Response::SetArray('gets', $_GET);
Response::SetArray('get_params', Request::GetParameters(METHOD_GET));
?>
