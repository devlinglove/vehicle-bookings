<?php
    
    namespace Framework;

    class Router{
        protected $routes = [];

        private function registerRoute(string $method, string $uri, string $action){
            list($controller, $controllerMethod) = explode('@', $action);
            $this->routes[] = [
                'method' => $method,
                'uri' => $uri,
                'controller' => $controller,
                'controllerMethod' => $controllerMethod
            ];
        }

        private function throwError($httpError = 404){
            http_response_code($httpError);
            loadView('error/404');
            exit();
        }

        public function get(string $uri, string $controller){
            $this->registerRoute('GET', $uri, $controller);
        }

        public function route(string $uri, string $method){
            foreach($this->routes as $route){
                if($route['method'] == $method && $route['uri'] == $uri){
                    
                    $controller = 'App\\Controllers\\' . $route['controller'];
                    $controllerMethod = $route['controllerMethod'];

                    $controllerInstance = new $controller();
                    $controllerInstance->$controllerMethod();

                    return;
                }
            }
           
           $this->throwError(404); 
        }

    }



?>