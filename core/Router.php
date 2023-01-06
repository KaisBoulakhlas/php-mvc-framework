<?php

namespace app\core;

use app\core\exception\NotFoundException;
use app\core\Request;


class Router
{
    
    public function __construct(public Request $request,public Response $response,  private array $routes = [])
    {
    }


    public function get($path, $callback){
        $this->routes['get'][$path] = $callback;
    }

    public function post($path, $callback){
        $this->routes['post'][$path] = $callback;
    }


    public function resolve()
    {
        $path = $this->request->getPath();
        $method = $this->request->method();
        $callback = $this->routes[$method][$path] ?? false;
    
        if($callback == false){
            throw new NotFoundException();
        }
        if(is_string($callback)){
            return $this->renderView($callback);
        }
        
        if(is_array($callback)) {
            /** @var \app\core\controllers Controller */
            $controller = new $callback[0]();
            Application::$app->controller = $controller;
            $controller->action = $callback[1];
            $callback[0] = $controller;
            foreach($controller->getMiddlewares() as $middleware){
                $middleware->execute();
            }
            $callback[0] = Application::$app->controller;
        }
        return call_user_func($callback, $this->request, $this->response);
    }

    public function renderView($view , $params = []) 
    {
        $layoutContent = $this->layoutContent();
        $viewContent = $this->renderOnlyView($view, $params);
        return str_replace('{{content}}', $viewContent, $layoutContent);
    }

    private function layoutContent()
    {
        $layout = Application::$app->layout;
        if(Application::$app->controller){
            $layout = Application::$app->controller->layout;
        }
        ob_start();
        include_once Application::$ROOT_DIR."/views/layouts/$layout.php";
        return ob_get_clean();
    }

    
    private function renderOnlyView($view, $params)
    {
        foreach($params as $key => $value){
            $$key = $value;
        }

        ob_start();
        include_once Application::$ROOT_DIR."/views/$view.php";
        return ob_get_clean();
    }
}