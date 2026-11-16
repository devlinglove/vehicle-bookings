<?php
    
    namespace Framework;
    use Framework\Middleware\Authorize;

    class Router{
        protected $routes = [];

        private function registerRoute(string $method, string $uri, string $action){
            list($controller, $controllerMethod) = explode('@', $action);
            $this->routes[] = [
                'method' => $method,
                'uri' => $uri,
                'controller' => $controller,
                'controllerMethod' => $controllerMethod,
                'middleware' => []
            ];

            return $this;
        }

        private function throwError($httpError = 404){
            http_response_code($httpError);
            loadView('error/404');
            exit();
        }

        public function get(string $uri, string $controller){
            return $this->registerRoute('GET', $uri, $controller);
        }

        public function only(array $roles){
            $this->routes[array_key_last($this->routes)]['middleware'] = $roles;
            return $this;
        
        }

        public function post(string $uri, string $controller){
            $this->registerRoute('POST', $uri, $controller);
        }


    public function route(string $uri, string $method)
    {
        foreach ($this->routes as $route) {

            if ($route['method'] == $method && $route['uri'] == $uri) {

                foreach ($route['middleware'] as $role) {
                    (new Authorize())->handle($role);
                }

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