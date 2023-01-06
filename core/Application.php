<?php

namespace app\core;


use app\core\Router;
use app\core\Request;
use app\core\Database;
use app\core\Response;
use app\core\Session;

class Application 
{
    
    public static string $ROOT_DIR;
    public string $userClass;
    public string $layout = "main";
    public Router $router;
    public Request $request;
    public Response $response;
    public Database $db;
    public Session $session;
    public ?UserModel $user;
    public static Application $app;
    public ?Controller $controller = null;

    public function __construct($rootPath, array $config){
        self::$ROOT_DIR = $rootPath;
        self::$app = $this;
        $this->request = new Request();
        $this->response = new Response();
        $this->session = new Session();
        $this->router = new Router($this->request,$this->response);
        $this->db = new Database($config['db']);
        $this->userClass = $config['userClass'];

        $primaryValue = $this->session->get('user');
        if ($primaryValue) {
            $primaryKey = $this->userClass::primaryKey();
            $this->user = $this->userClass::findOne([$primaryKey => $primaryValue]);
        } else {
            $this->user = null;
        }
    }

    public static function isGuest()
    {
        return !self::$app->user;
    }

    public function run()
    {
        try {
            echo $this->router->resolve();
        } catch(\Exception $e){
            $this->response->setStatusCode((int)$e->getCode());

            echo $this->router->renderView('_error', [
                'exception' => $e
            ]);
        }
    }

    public function getController(): Controller
    {
        return $this->controller;
    }

    public function setController(Controller $controller): void
    {
        $this->controller = $controller;
    }

    public function login(DbModel $user)
    {
        $this->user = $user;
        $primaryKey = $user->primaryKey();
        $primaryValue = $user->{$primaryKey};
        $this->session->set('user', $primaryValue);
        return true;
    }

    public function logout()
    {
        $this->user = null;
        $this->session->remove('user');
    }
}