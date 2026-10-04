<?php
    function basePath($path = ''){
        return __DIR__ . '/' . $path;
    }


        function loadPartialView(string $name, array $data = [])
        {
            $viewPath = basePath("App/views/partials/$name.php");

            if (!file_exists($viewPath)) {
                echo "File with name $name does not exist";
                return;
            }

            extract($data);
            require $viewPath;
        }

      function loadView(string $name, $data = []){
        $viewPath = basePath("App/views/$name.view.php");
        if(!file_exists($viewPath)){
            echo "File with name $name does not exists";
            return;
        }
        extract($data);
        require $viewPath;
    }


    function inspect(mixed $value){
        echo '<pre>';
        var_dump($value);
        echo '</pre>';
    }

    function formatSalary(int $value){
        return '$' . number_format(floatval($value));
    }



?>

